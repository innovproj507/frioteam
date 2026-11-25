#!/bin/bash
# Instala el "apagado desde el sistema": un vigilante que apaga el servidor cuando el administrador
# pulsa "Apagar servidor" en Configuración. Ejecutar UNA SOLA VEZ en el servidor, como administrador:
#     sudo bash /opt/pos/scripts/instalar_apagado.sh
# Para quitarlo:  sudo bash /opt/pos/scripts/instalar_apagado.sh quitar
set -e
DIR=/opt/pos/ci4/writable/apagar
[ "$(id -u)" = "0" ] || { echo "Ejecuta con sudo: sudo bash $0"; exit 1; }

if [ "${1:-}" = "quitar" ]; then
    systemctl disable --now pos-apagar.path pos-apagar-cancelar.path 2>/dev/null || true
    rm -f /etc/systemd/system/pos-apagar.* /etc/systemd/system/pos-apagar-cancelar.* "$DIR/activo"
    systemctl daemon-reload
    echo "Apagado desde el sistema: quitado."
    exit 0
fi

# Carpeta compartida con el sistema (el contenedor escribe aquí como www-data)
mkdir -p "$DIR"
chown 33:33 "$DIR" 2>/dev/null || true
chmod 775 "$DIR"
rm -f "$DIR/solicitud" "$DIR/cancelar"

cat > /usr/local/sbin/pos-apagar.sh <<'EOS'
#!/bin/bash
# Solo mira si EXISTE el archivo de solicitud (no lee su contenido): apaga en 1 minuto.
D=/opt/pos/ci4/writable/apagar
rm -f "$D/solicitud"
logger -t pos-apagar "Apagado solicitado desde el sistema POS: el servidor se apaga en 1 minuto"
[ -x /opt/pos/scripts/notificar.sh ] && /opt/pos/scripts/notificar.sh "🔴 <b>Apagado solicitado desde el POS</b>
El servidor se apagará en 1 minuto. $(date '+%d/%m/%Y %H:%M')" || true
/sbin/shutdown -h +1 "Apagado solicitado desde el sistema POS"
EOS
cat > /usr/local/sbin/pos-apagar-cancelar.sh <<'EOS'
#!/bin/bash
D=/opt/pos/ci4/writable/apagar
rm -f "$D/cancelar"
logger -t pos-apagar "Apagado cancelado desde el sistema POS"
[ -x /opt/pos/scripts/notificar.sh ] && /opt/pos/scripts/notificar.sh "🟡 <b>Apagado cancelado</b> desde el POS. $(date '+%d/%m/%Y %H:%M')" || true
/sbin/shutdown -c "Apagado cancelado" || true
EOS
chmod 755 /usr/local/sbin/pos-apagar.sh /usr/local/sbin/pos-apagar-cancelar.sh

cat > /etc/systemd/system/pos-apagar.service <<'EOS'
[Unit]
Description=Apagar el servidor (solicitado desde el POS)
[Service]
Type=oneshot
ExecStart=/usr/local/sbin/pos-apagar.sh
EOS
cat > /etc/systemd/system/pos-apagar.path <<'EOS'
[Unit]
Description=Vigila la solicitud de apagado del POS
[Path]
PathExists=/opt/pos/ci4/writable/apagar/solicitud
Unit=pos-apagar.service
[Install]
WantedBy=multi-user.target
EOS
cat > /etc/systemd/system/pos-apagar-cancelar.service <<'EOS'
[Unit]
Description=Cancelar el apagado (solicitado desde el POS)
[Service]
Type=oneshot
ExecStart=/usr/local/sbin/pos-apagar-cancelar.sh
EOS
cat > /etc/systemd/system/pos-apagar-cancelar.path <<'EOS'
[Unit]
Description=Vigila la cancelacion del apagado del POS
[Path]
PathExists=/opt/pos/ci4/writable/apagar/cancelar
Unit=pos-apagar-cancelar.service
[Install]
WantedBy=multi-user.target
EOS

systemctl daemon-reload
systemctl enable --now pos-apagar.path pos-apagar-cancelar.path
# Marca de "instalado": el sistema solo habilita el botón si existe
echo "instalado $(date -Is)" > "$DIR/activo"
chmod 644 "$DIR/activo"
echo "Listo: el apagado desde el sistema quedó instalado."
systemctl --no-pager status pos-apagar.path | head -4
