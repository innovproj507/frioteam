#!/bin/bash
# Prepara el servidor 11 para ser el ESPEJO del 13. Ejecutar UNA vez, en el 11, como administrador:
#     sudo bash ~/preparar_11.sh
# Hace: 1) comprueba que el POS viejo esté archivado, 2) quita XAMPP (POS viejo, FTP y MySQL viejos),
#       3) instala todas las actualizaciones de Ubuntu 22.04, 4) instala Docker, 5) reinicia si hace falta.
set -euo pipefail
[ "$(id -u)" = "0" ] || { echo "Ejecuta con sudo: sudo bash $0"; exit 1; }
U=soporte
A=/home/$U/archivo_pos_viejo
log() { echo; echo "=== $*"; }

log "1/5 Comprobando el archivo del POS viejo"
DUMP=$(ls -1t $A/liang_final_*.sql.gz 2>/dev/null | head -1 || true)
[ -n "$DUMP" ] && [ -s "$DUMP" ] && gunzip -t "$DUMP" || { echo "NO hay respaldo final de la base 'liang' en $A. No sigo."; exit 1; }
ls -lh $A

log "2/5 Quitando el POS viejo (XAMPP)"
if [ -d /opt/lampp ]; then
    /opt/lampp/lampp stop || true
    systemctl disable --now lampp.service 2>/dev/null || true
    rm -f /etc/systemd/system/lampp.service /etc/init.d/lampp
    systemctl daemon-reload
    rm -rf /opt/lampp
    echo "XAMPP eliminado."
else
    echo "XAMPP ya no estaba."
fi
# Tarea vieja de respaldo de XAMPP (ya no aplica)
crontab -u $U -l 2>/dev/null | grep -v 'backupPos.sh' | crontab -u $U - || true

log "3/5 Actualizando Ubuntu (puede tardar varios minutos)"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get -y -q -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold full-upgrade
apt-get -y -q autoremove

log "4/5 Instalando Docker y herramientas del espejo"
apt-get -y -q install docker.io docker-compose-v2 rsync autossh
systemctl enable --now docker
usermod -aG docker $U
docker --version; docker compose version

log "5/5 Listo"
echo "El POS viejo fue quitado (su respaldo quedó en $A) y el 11 está actualizado con Docker."
if [ -f /var/run/reboot-required ]; then
    echo "Hace falta reiniciar: el 11 se reinicia en 1 minuto (Ctrl+C no lo detiene; para cancelar: sudo shutdown -c)."
    shutdown -r +1 "Reinicio tras actualizar (espejo del 13)"
fi
