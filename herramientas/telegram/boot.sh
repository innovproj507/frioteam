#!/bin/bash
# Aviso de "servidor encendido", se ejecuta una vez al arrancar (cron @reboot, con una espera para que levanten los contenedores).
# Usa el último "latido" de salud.sh para decir cuánto tiempo estuvo apagado el equipo.
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
. /home/soporte/.config/ip_monitor.env 2>/dev/null
NOMBRE="${NOMBRE:-servidor}"
AHORA=$(date +%s)
ARRANQUE=$(date -d "$(uptime -s)" +%s 2>/dev/null || echo "$AHORA")
LAT=$(cat /home/soporte/.salud/latido 2>/dev/null || echo 0)
if [ "${LAT:-0}" -gt 0 ]; then
    SEG=$((ARRANQUE - LAT)); [ "$SEG" -lt 0 ] && SEG=0
    if [ "$SEG" -lt 900 ]; then
        APAG="Reinicio rápido (menos de 15 min sin servicio)."
    else
        APAG="Estuvo apagado desde $(date -d "@$LAT" '+%d/%m %H:%M') hasta $(date -d "@$ARRANQUE" '+%d/%m %H:%M') ($((SEG / 3600)) h $(((SEG % 3600) / 60)) min)."
    fi
else
    APAG="No hay registro de cuándo se apagó."
fi
CONT=""
for c in pos-php-ci4 pos-mysql; do
    if [ "$(docker inspect -f '{{.State.Running}}' "$c" 2>/dev/null)" = "true" ]; then CONT="$CONT ✅ $c"; else CONT="$CONT ❌ $c"; fi
done
/opt/pos/scripts/notificar.sh "🟢 <b>${NOMBRE} encendido</b>
${APAG}
📅 $(date '+%d/%m/%Y %H:%M')
Contenedores:${CONT}"
