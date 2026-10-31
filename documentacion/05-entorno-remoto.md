# Entorno remoto — Guía de despliegue

Este documento describe el procedimiento general. Los nombres de host, usuarios,
direcciones, rutas exactas, contraseñas, claves SSH y archivos de configuración
de producción deben mantenerse fuera del repositorio y administrarse localmente.

## Requisitos

- Windows con PHP 8.3, Composer, Node.js, npm, Git y SQLite.
- Extensiones PHP para Laravel, SQLite y OPcache.
- nginx como servidor web y PHP-CGI en puertos locales.
- Tailscale, HTTPS y autenticación adicional configurados fuera del repositorio.

## Seguridad

- No guardar `.env`, bases SQLite, respaldos, claves privadas, archivos de
  autenticación ni credenciales en Git.
- Usar una cuenta SSH con clave y permisos mínimos.
- Mantener `APP_ENV=production`, `APP_DEBUG=false` y una `APP_KEY` privada.
- Configurar credenciales únicas antes de ejecutar el seeder.
- No documentar direcciones IP, nombres de equipo, usuarios del sistema ni URLs
  privadas en archivos públicos.

## Despliegue

Crear un respaldo consistente de la base SQLite antes de actualizar y comprobar
que el archivo generado pueda leerse.

Desde la raíz del repositorio:

```powershell
git archive --format=zip -o ..\portal-deploy.zip HEAD sistema
scp ..\portal-deploy.zip <ssh-alias>:<paquete-remoto>.zip
ssh <ssh-alias> "powershell -NoProfile -Command Expand-Archive -Path <paquete-remoto>.zip -DestinationPath <directorio-app> -Force"
ssh <ssh-alias> "cd /d <directorio-app>\sistema && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader"
ssh <ssh-alias> "cd /d <directorio-app>\sistema && npm ci && npm run build"
ssh <ssh-alias> "cd /d <directorio-app>\sistema && php artisan migrate --force"
ssh <ssh-alias> "cd /d <directorio-app>\sistema && php artisan optimize"
```

No usar `migrate:fresh` en una instalación con datos. Las actualizaciones normales
deben usar únicamente `migrate --force`.

## Reinicio y verificación

Si OPcache usa `validate_timestamps=0`, reiniciar el pool PHP y nginx mediante la
tarea o el mecanismo de servicio administrado en el servidor.

```powershell
ssh <ssh-alias> "cd /d <directorio-app>\sistema && php artisan about"
ssh <ssh-alias> "cd /d <directorio-app>\sistema && php artisan migrate:status"
ssh <ssh-alias> "tailscale status"
```

Comprobar el login, la autenticación del servidor, los respaldos y los flujos
principales desde una red autorizada.

Los valores reales de conexión, Tailscale, nginx, tareas programadas, respaldos y
credenciales deben almacenarse en la configuración privada del equipo de producción
o en un gestor de secretos.
