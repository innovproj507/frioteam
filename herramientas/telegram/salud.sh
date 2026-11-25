#!/bin/bash
# Revisión de salud del servidor, cada 5 minutos por cron. Avisa por Telegram SOLO cuando algo falla,
# cuando se recupera, y repite el aviso cada hora mientras siga mal. También guarda un "latido" para saber
# cuánto tiempo estuvo apagado el servidor (lo usa boot.sh).
#   SALUD_PRUEBA_FALLO=db salud.sh    -> simula que falla esa revisión (web, db, contenedores, disco, errores, backup)
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
. /home/soporte/.config/ip_monitor.env 2>/dev/null
NOMBRE="${NOMBRE:-servidor}"
D=/home/soporte/.salud; mkdir -p "$D"
NOTIF=/opt/pos/scripts/notificar.sh
AHORA=$(date +%s)
echo "$AHORA" > "$D/latido"

# estado <clave> <ok|fallo> "<detalle>" : avisa en los cambios y recuerda cada hora
estado() {
    local clave="$1" nuevo="$2" detalle="$3" f="$D/$1" previo="ok" ult=0
    [ -f "$f" ] && read -r previo ult < "$f"
    if [ "$nuevo" = "fallo" ]; then
        if [ "$previo" != "fallo" ] || [ $((AHORA - ${ult:-0})) -ge 3600 ]; then
            "$NOTIF" "🔴 <b>FALLO en ${NOMBRE}</b>
${detalle}
📅 $(date '+%d/%m/%Y %H:%M')"
            echo "fallo $AHORA" > "$f"
        fi
    else
        if [ "$previo" = "fallo" ]; then
            "$NOTIF" "🟢 <b>Recuperado en ${NOMBRE}</b>
${detalle}
📅 $(date '+%d/%m/%Y %H:%M')"
        fi
        echo "ok $AHORA" > "$f"
    fi
}
prueba() { [ "${SALUD_PRUEBA_FALLO:-}" = "$1" ]; }

# 1) Contenedores en marcha
CAIDOS=""
for c in pos-php-ci4 pos-mysql; do
    [ "$(docker inspect -f '{{.State.Running}}' "$c" 2>/dev/null)" = "true" ] || CAIDOS="$CAIDOS $c"
done
prueba contenedores && CAIDOS=" pos-prueba"
if [ -n "$CAIDOS" ]; then estado contenedores fallo "Contenedor detenido:${CAIDOS}"; else estado contenedores ok "Los contenedores volvieron a estar en marcha"; fi

# 2) El POS responde (pantalla de login, sin errores de PHP)
if [ -z "$CAIDOS" ]; then
    RESP=$(docker exec pos-php-ci4 curl -s -m 15 -w '\n%{http_code}' http://localhost/login 2>/dev/null)
    COD=$(echo "$RESP" | tail -1)
    if [ "$COD" = "200" ] && ! echo "$RESP" | grep -qE 'Fatal error|Parse error|ErrorException' && ! prueba web; then
        estado web ok "El POS vuelve a responder"
    else
        estado web fallo "El POS no responde bien (código ${COD:-sin respuesta})"
    fi
    # 3) Base de datos
    if docker exec pos-mysql sh -c 'mysqladmin ping -uroot -p"$MYSQL_ROOT_PASSWORD" --silent' >/dev/null 2>&1 && ! prueba db; then
        estado db ok "La base de datos vuelve a responder"
    else
        estado db fallo "La base de datos (MySQL) no responde"
    fi
fi

# 4) Disco
USO=$(df --output=pcent / | tail -1 | tr -dc '0-9')
prueba disco && USO=96
if [ "${USO:-0}" -ge 85 ]; then
    estado disco fallo "Disco al ${USO}% (libre: $(df -h --output=avail / | tail -1 | tr -d ' '))"
else
    estado disco ok "Disco al ${USO}%"
fi

# 5) Errores nuevos graves en el log de la aplicación (CRITICAL)
LOG="/opt/pos/ci4/writable/logs/log-$(date +%F).log"
if [ -f "$LOG" ]; then
    TOT=$(grep -c 'CRITICAL' "$LOG")
    read -r ULTLOG ULTN < "$D/errores" 2>/dev/null
    [ "$ULTLOG" = "$LOG" ] || ULTN=0
    if [ "${TOT:-0}" -gt "${ULTN:-0}" ] || prueba errores; then
        EJ=$(grep 'CRITICAL' "$LOG" | tail -2 | cut -c1-200 | sed -E 's/[0-9]{8,}:[A-Za-z0-9_-]{30,}/<TOKEN>/g; s/</\&lt;/g')
        [ -n "$EJ" ] || EJ="(prueba)"
        "$NOTIF" "🟠 <b>Errores graves en ${NOMBRE}</b>
$((TOT - ${ULTN:-0})) nuevo(s) en el log de hoy:
<code>${EJ}</code>"
    fi
    echo "$LOG $TOT" > "$D/errores"
fi

# 6) Respaldo reciente (el automático de la noche; el servidor se apaga algunas noches, por eso 72 h)
ULT=$(ls -1t /opt/pos/backups/pos_empresa_*.sql.gz 2>/dev/null | head -1)
if [ -n "$ULT" ]; then EDAD=$(( (AHORA - $(stat -c %Y "$ULT")) / 3600 )); else EDAD=9999; fi
prueba backup && EDAD=100
if [ "$EDAD" -ge 72 ]; then
    estado backup fallo "Hace ${EDAD} horas que no hay un respaldo automático de la base"
else
    estado backup ok "Ya hay un respaldo reciente"
fi
