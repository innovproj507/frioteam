#!/bin/bash
# ACTIVAR EL ESPEJO: convierte el servidor 11 en el POS cuando el 13 está dañado o apagado por mucho tiempo.
#     bash ~/espejo/bin/activar_espejo.sh
# Qué hace: corta la réplica, deja la base del 11 en modo escritura y levanta el POS en http://192.168.0.11
# (las cajas y celulares deben abrir esa dirección). Los datos son los del último segundo que llegó del 13.
# OJO: si el 13 vuelve, NO lo usen hasta pasar los datos del 11 al 13 (pedir ayuda): si ambos venden a la vez,
# las ventas quedan repartidas en dos bases.
set -euo pipefail
cd ~/espejo
echo "Esto convierte el 11 en el POS principal. Escribe ACTIVAR para seguir:"
read -r R; [ "$R" = "ACTIVAR" ] || { echo "Cancelado."; exit 1; }

echo "== 1/4 Deteniendo la réplica y habilitando escritura"
docker exec -i espejo-mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" 2>/dev/null' <<'SQL'
STOP REPLICA;
SET PERSIST super_read_only = OFF;
SET PERSIST read_only = OFF;
SQL
pkill -f "/autossh -M 0 .*espejo_tunel" || true
touch ~/espejo/ACTIVADO   # salud_espejo.sh deja de levantar el túnel

echo "== 2/4 Preparando la configuración del POS"
[ -f ~/espejo/ci4.env ] || { echo "Falta ~/espejo/ci4.env (configuración del POS)"; exit 1; }
cp ~/espejo/ci4.env ~/espejo/ci4/.env && chmod 644 ~/espejo/ci4/.env   # el servidor web del contenedor debe poder leerlo
# En el 11 la base está en este mismo equipo
sed -i -E "s#^(\s*database\.default\.hostname\s*=).*#\1 127.0.0.1#; s#^(\s*app\.baseURL\s*=).*#\1 'http://192.168.0.11/'#" ~/espejo/ci4/.env
mkdir -p ~/espejo/ci4/writable/{cache,session,logs,debugbar}
chmod -R 777 ~/espejo/ci4/writable

echo "== 3/4 Levantando el POS (la primera vez construye la imagen, unos minutos)"
cat > ~/espejo/docker-compose.app.yml <<'YML'
services:
  php-ci4:
    build:
      context: ./docker/php-ci4
    container_name: espejo-php
    restart: unless-stopped
    network_mode: host
    volumes:
      - ./ci4:/var/www/html
      - ./docker/php-ci4/start.sh:/usr/local/bin/start.sh:ro
YML
docker compose -f docker-compose.app.yml up -d --build

echo "== 4/4 Comprobando"
sleep 5
curl -s -o /dev/null -w "POS en el 11: %{http_code}\n" http://127.0.0.1/login || true
~/espejo/bin/notificar.sh "🟠 <b>ESPEJO ACTIVADO</b>: el POS ahora funciona en el servidor 11 (http://192.168.0.11). No usar el 13 hasta pasarle los datos." || true
echo "Listo. Abran http://192.168.0.11 en las cajas."
