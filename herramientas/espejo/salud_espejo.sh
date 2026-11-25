#!/bin/bash
# Salud del ESPEJO (servidor 11), cada 5 minutos por cron. Avisa por Telegram solo cuando algo falla,
# cuando se recupera, y repite cada hora mientras siga mal. De paso, levanta el túnel si se cayó.
#   SALUD_PRUEBA_FALLO=replica salud_espejo.sh   -> simula un fallo (mysql, replica, atraso, tunel, copia)
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
. /home/soporte/.config/ip_monitor.env 2>/dev/null
NOMBRE="${NOMBRE:-servidor 11}"
D=/home/soporte/.salud; mkdir -p "$D"
NOTIF=/home/soporte/espejo/bin/notificar.sh
AHORA=$(date +%s)
echo "$AHORA" > "$D/latido"

estado() {
    local clave="$1" nuevo="$2" detalle="$3" f="$D/$1" previo="ok" ult=0
    [ -f "$f" ] && read -r previo ult < "$f"
    if [ "$nuevo" = "fallo" ]; then
        if [ "$previo" != "fallo" ] || [ $((AHORA - ${ult:-0})) -ge 3600 ]; then
            "$NOTIF" "🔴 <b>ESPEJO con problemas (${NOMBRE})</b>
${detalle}
📅 $(date '+%d/%m/%Y %H:%M')"
            echo "fallo $AHORA" > "$f"
        fi
    else
        [ "$previo" = "fallo" ] && "$NOTIF" "🟢 <b>Espejo recuperado (${NOMBRE})</b>
${detalle}
📅 $(date '+%d/%m/%Y %H:%M')"
        echo "ok $AHORA" > "$f"
    fi
}
prueba() { [ "${SALUD_PRUEBA_FALLO:-}" = "$1" ]; }

# Túnel al 13: si no está, se levanta
/home/soporte/espejo/bin/tunel.sh >/dev/null 2>&1
if pgrep -f "/autossh -M 0 .*espejo_tunel" >/dev/null && ! prueba tunel; then
    estado tunel ok "El túnel al 13 está arriba"
else
    estado tunel fallo "El túnel cifrado al 13 no está funcionando (¿el 13 está apagado o sin red?)"
fi

# MySQL espejo y réplica
if [ "$(docker inspect -f '{{.State.Running}}' espejo-mysql 2>/dev/null)" = "true" ] && ! prueba mysql; then
    estado mysql ok "El MySQL del espejo está en marcha"
    ST=$(docker exec espejo-mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SHOW REPLICA STATUS\G" 2>/dev/null')
    IO=$(echo "$ST" | sed -n 's/^ *Replica_IO_Running: *//p'); SQ=$(echo "$ST" | sed -n 's/^ *Replica_SQL_Running: *//p')
    LAG=$(echo "$ST" | sed -n 's/^ *Seconds_Behind_Source: *//p')
    ERR=$(echo "$ST" | sed -n 's/^ *Last_\(IO\|SQL\)_Error: *\(..*\)$/\2/p' | head -1 | cut -c1-180 | sed 's/</\&lt;/g')
    if [ "$IO" = "Yes" ] && [ "$SQ" = "Yes" ] && ! prueba replica; then
        estado replica ok "La réplica volvió a copiar del 13"
    else
        estado replica fallo "La réplica está detenida (lectura: ${IO:-?}, aplicación: ${SQ:-?})
${ERR}"
    fi
    if [ "$LAG" = "NULL" ] || [ -z "$LAG" ]; then LAG=0; fi
    prueba atraso && LAG=900
    if [ "$LAG" -ge 300 ]; then estado atraso fallo "La réplica va atrasada ${LAG} segundos respecto al 13"; else estado atraso ok "La réplica está al día"; fi
else
    estado mysql fallo "El MySQL del espejo está detenido"
fi

# Copia de código y fotos (cada 5 min): avisar si pasaron más de 30 min sin copiar
ULT=$(cat /home/soporte/espejo/.ultima_sincronizacion 2>/dev/null || echo 0)
prueba copia && ULT=0
if [ $((AHORA - ULT)) -ge 1800 ]; then
    estado copia fallo "Hace más de 30 minutos que no se copian el código y las fotos del 13"
else
    estado copia ok "La copia de código y fotos funciona"
fi
