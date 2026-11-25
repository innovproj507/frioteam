# Servidor espejo (11) del POS (13)

El servidor **11** (192.168.0.11, SSH puerto 2222 desde fuera) es una copia en vivo del **13** (192.168.0.13).

## Qué se copia y cómo
| Qué | Cómo | Frecuencia |
|---|---|---|
| Base de datos `pos_empresa` | Réplica de MySQL 8.4.13 (`espejo-mysql`, solo lectura) por un túnel SSH cifrado en la red local | Al segundo |
| Código, fotos y comprobantes (`/opt/pos/ci4`) y archivos de Docker | `~/espejo/bin/sincronizar.sh` (rsync, llave de solo lectura) | Cada 5 min |
| Configuración del POS (`.env`) | Copiada a mano en `~/espejo/ci4.env` (600) | Volver a copiar si cambia en el 13 |

Llaves en el 11: `~/.ssh/espejo_tunel` (en el 13 solo puede abrir el túnel a MySQL) y `~/.ssh/espejo_rsync` (en el 13 solo puede leer `/opt/pos` con `rrsync -ro`).
Usuario de réplica en el 13: `replica@172.18.0.1` (solo permisos de replicación). Claves en `~/espejo/.env` del 11 (600).

## Avisos por Telegram
`~/espejo/bin/salud_espejo.sh` (cada 5 min): túnel caído, MySQL del espejo detenido, réplica detenida, réplica atrasada más de 5 min, o más de 30 min sin copiar archivos. Avisa al fallar, al recuperarse y cada hora mientras siga mal. También levanta el túnel si se cayó.

## Si el 13 se daña: activar el espejo
En el 11: `bash ~/espejo/bin/activar_espejo.sh` (pide escribir ACTIVAR). Corta la réplica, habilita escritura y levanta el POS en **http://192.168.0.11**.
- La impresión directa (CUPS) puede no funcionar en el 11: usar el PDF.
- Cuando el 13 vuelva, **no venderlo en paralelo**: primero hay que pasar los datos del 11 al 13 y volver a montar la réplica.

## Conexión de la réplica
El túnel va al puerto fijo `127.0.0.1:3307` del 13 (MySQL publicado solo para el propio equipo), guardado en `~/espejo/destino_mysql`. Ya no depende de la IP interna del contenedor.

Scripts fuente: `herramientas/espejo/` y `herramientas/telegram/` en el repositorio.
