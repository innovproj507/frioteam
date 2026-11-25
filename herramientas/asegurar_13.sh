#!/bin/bash
# Endurecimiento del servidor 13 (no reinicia, no corta el POS). Ejecutar UNA vez:
#     sudo bash /opt/pos/scripts/asegurar_13.sh
# Antes de cerrar la sesión donde lo ejecutaste, abre OTRA conexión SSH para comprobar que entras bien.
set -uo pipefail
[ "$(id -u)" = "0" ] || { echo "Ejecuta con sudo: sudo bash $0"; exit 1; }
paso() { echo; echo "=== $*"; }

paso "1/6 Quitar a soporte del grupo lxd (daba acceso de administrador sin contraseña)"
if id -nG soporte | grep -qw lxd; then gpasswd -d soporte lxd; else echo "soporte ya no está en lxd"; fi

paso "2/6 SSH más estricto"
F=/etc/ssh/sshd_config.d/10-frioteam.conf
cat > "$F" <<'EOF'
# Endurecimiento FrioTeam (el primer valor que lee sshd es el que vale; este archivo se lee antes que el resto)
PermitRootLogin no
X11Forwarding no
MaxAuthTries 3
LoginGraceTime 30
AllowUsers soporte
ClientAliveInterval 300
ClientAliveCountMax 2
EOF
chmod 644 "$F"
if sshd -t; then
    systemctl reload ssh && echo "SSH recargado con la configuración nueva."
    sshd -T 2>/dev/null | grep -E '^(permitrootlogin|passwordauthentication|x11forwarding|maxauthtries|logingracetime|allowusers) '
else
    echo "La configuración nueva tiene un error: se quita y SSH queda como estaba."
    rm -f "$F"
fi

paso "3/6 Firewall (ufw)"
ufw status verbose | head -30

paso "4/6 fail2ban (bloquea IPs que prueban contraseñas)"
systemctl is-active fail2ban && fail2ban-client status sshd 2>/dev/null | sed -n '1,12p' || echo "fail2ban no está activo"

paso "5/6 Salud del disco (único disco del servidor)"
smartctl -H /dev/sda | grep -iE 'result|overall' || true
smartctl -A /dev/sda | awk '$2 ~ /Reallocated_Sector|Current_Pending|Offline_Uncorrectable|Power_On_Hours|Temperature_Celsius/ {print $2": "$10}' || true

paso "6/6 Actualizaciones automáticas de seguridad"
cat /etc/apt/apt.conf.d/20auto-upgrades 2>/dev/null || echo "sin 20auto-upgrades"
systemctl is-active unattended-upgrades

echo; echo "Listo. Abre otra conexión SSH para confirmar que entras antes de cerrar esta."
