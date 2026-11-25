#!/bin/bash
# Envía un mensaje a Telegram con el mismo bot que el monitor de IP.
#   notificar.sh "texto"          (admite HTML básico: <b>, <code>)
#   echo "texto" | notificar.sh
# Nunca falla el script que lo llama: si Telegram no responde, solo lo anota en ~/notificar.log.
CONF="${IP_MONITOR_CONF:-/home/soporte/.config/ip_monitor.env}"
[ -r "$CONF" ] || exit 0
# shellcheck disable=SC1090
. "$CONF"
[ -n "${BOT_TOKEN:-}" ] && [ -n "${CHAT_ID:-}" ] || exit 0
MSG="${1:-}"
[ -n "$MSG" ] || MSG="$(cat)"
[ -n "$MSG" ] || exit 0
RESP=$(curl -s --max-time 20 -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" \
    --data-urlencode "chat_id=${CHAT_ID}" --data-urlencode "text=${MSG}" --data-urlencode "parse_mode=HTML" 2>&1)
if ! echo "$RESP" | grep -q '"ok":true'; then
    echo "[$(date '+%F %T')] no se pudo enviar: $(echo "$RESP" | tr -d '\n' | cut -c1-120 | sed -E 's/[0-9]{8,}:[A-Za-z0-9_-]{30,}/<TOKEN>/g')" >> /home/soporte/notificar.log
fi
exit 0
