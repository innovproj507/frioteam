#!/bin/bash
# Monitor de IP pública -> aviso por Telegram cuando cambia.
# Se ejecuta con cron cada 5 minutos y al arrancar el equipo. La configuración (token del bot, chat, puerto SSH,
# nombre del servidor y ruta del log) está en ~/.config/ip_monitor.env (permisos 600): el token NO va en este archivo.
#
# Mejoras sobre la versión anterior: la última IP se guarda en un lugar fijo (no en /tmp), así que si el equipo se
# apaga y la IP cambió, al volver avisa "CAMBIO DE IP" con la anterior; el aviso incluye el puerto SSH; los saltos
# de línea se envían bien; solo da la IP por avisada cuando Telegram respondió ok (si falla, reintenta a los 5 min);
# y el log se recorta solo.

CONF="${IP_MONITOR_CONF:-$HOME/.config/ip_monitor.env}"
[ -r "$CONF" ] || { echo "Falta $CONF" >&2; exit 1; }
# shellcheck disable=SC1090
. "$CONF"
: "${BOT_TOKEN:?}" "${CHAT_ID:?}"
NOMBRE="${NOMBRE:-$(hostname)}"
SSH_PORT="${SSH_PORT:-22}"
LOG_FILE="${LOG_FILE:-$HOME/ip_monitor.log}"
STATE_FILE="${STATE_FILE:-$HOME/.ip_monitor_ip}"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"; }

# El log no crece sin límite: pasado 1 MB se queda con las últimas 2000 líneas
if [ -f "$LOG_FILE" ] && [ "$(stat -c %s "$LOG_FILE" 2>/dev/null || echo 0)" -gt 1048576 ]; then
    tail -n 2000 "$LOG_FILE" | tr -d '\000' > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
fi

# Devuelve 0 solo si Telegram contestó "ok":true
send_telegram() {
    local resp
    resp=$(curl -s --max-time 20 -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" \
        --data-urlencode "chat_id=${CHAT_ID}" \
        --data-urlencode "text=$1" \
        --data-urlencode "parse_mode=HTML" 2>&1)
    if echo "$resp" | grep -q '"ok":true'; then
        return 0
    fi
    log "ERROR al enviar a Telegram: $(echo "$resp" | tr -d '\n' | cut -c1-160 | sed -E 's/[0-9]{8,}:[A-Za-z0-9_-]{30,}/<TOKEN>/g')"
    return 1
}

# IP pública (con respaldo en otro servicio)
NEW_IP=""
for url in http://ifconfig.me/ip https://api.ipify.org https://ipv4.icanhazip.com; do
    NEW_IP=$(curl -s --max-time 10 "$url" 2>/dev/null | tr -d '[:space:]')
    echo "$NEW_IP" | grep -Eq '^[0-9]{1,3}(\.[0-9]{1,3}){3}$' && break
    NEW_IP=""
done
if [ -z "$NEW_IP" ]; then
    log "ERROR: no se pudo obtener la IP pública"
    exit 1
fi

NL=$'\n'
ACCESO="🔗 <b>Acceso:</b>${NL}<code>ssh -p ${SSH_PORT} soporte@${NEW_IP}</code>"

if [ ! -s "$STATE_FILE" ]; then
    log "Primera ejecución - IP inicial: $NEW_IP"
    MSG="🖥️ <b>Monitor de IP iniciado</b>${NL}🔥 <b>Servidor:</b> ${NOMBRE}${NL}📡 <b>IP:</b> <code>${NEW_IP}</code>${NL}📅 $(date)${NL}${NL}${ACCESO}"
    if send_telegram "$MSG"; then
        echo "$NEW_IP" > "$STATE_FILE"
        log "Mensaje inicial enviado a Telegram"
    fi
    exit 0
fi

OLD_IP=$(tr -d '[:space:]' < "$STATE_FILE")
if [ "$NEW_IP" != "$OLD_IP" ]; then
    log "CAMBIO DETECTADO: nueva IP $NEW_IP, anterior $OLD_IP"
    MSG="⚠️ <b>CAMBIO DE IP PÚBLICA</b> ⚠️${NL}${NL}🔥 <b>Servidor:</b> ${NOMBRE}${NL}📡 <b>Nueva IP:</b> <code>${NEW_IP}</code>${NL}📡 <b>IP anterior:</b> <code>${OLD_IP}</code>${NL}📅 $(date)${NL}${NL}${ACCESO}"
    if send_telegram "$MSG"; then
        echo "$NEW_IP" > "$STATE_FILE"
        log "Aviso de cambio enviado a Telegram"
    else
        log "No se pudo avisar: se reintenta en 5 minutos"
    fi
else
    log "IP sin cambios: $NEW_IP"
fi
