# Entorno remoto — Guía de despliegue

Este documento describe el procedimiento general. Los nombres de host, usuarios,
direcciones, rutas exactas, contraseñas, claves SSH y archivos de configuración
de producción deben mantenerse fuera del repositorio y administrarse localmente.

## Requisitos

- Windows con PHP 8.3, Composer, Node.js, npm, Git y SQLite.
- Extensiones PHP para Laravel, SQLite y OPcache.
- nginx como servidor web y PHP-CGI en puertos locales.
- Red privada (VPN), HTTPS y autenticación adicional configurados fuera del repositorio.

## Seguridad

- No guardar `.env`, bases SQLite, respaldos, claves privadas, archivos de
  autenticación ni credenciales en Git.
- Mantener fuera del repositorio el script de despliegue y los datos del host.
- Usar una cuenta SSH con clave y permisos mínimos.
- Mantener `APP_ENV=production`, `APP_DEBUG=false` y una `APP_KEY` privada.
- Configurar credenciales únicas antes de ejecutar el seeder.
- No documentar direcciones IP, nombres de equipo, usuarios del sistema ni URLs
  privadas en archivos públicos.

## Despliegue

El servidor mantiene un checkout de Git en el directorio de la aplicación. El
despliegue se ejecuta con un script privado del operador, que no forma parte de
este repositorio y automatiza estos pasos:

1. Crear un respaldo consistente de la base SQLite.
2. Activar el modo mantenimiento.
3. Actualizar el checkout a `origin/main`.
4. Instalar dependencias de producción y compilar los recursos.
5. Ejecutar `php artisan migrate --force` y `php artisan optimize`.
6. Reiniciar el servidor web y verificar que el portal responda.
7. Volver al commit anterior si algún paso falla.

No usar `migrate:fresh` en una instalación con datos. Las actualizaciones normales
deben usar únicamente `migrate --force`.

### Actualización manual (referencia)

Si no se dispone del script:

```powershell
git fetch origin main
git reset --hard origin/main
cd /d <directorio-app>\sistema
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

Luego reiniciar el servidor web con el mecanismo administrado en el servidor.

## Reinicio y verificación

Si OPcache usa `validate_timestamps=0`, reiniciar el pool PHP y nginx mediante la
tarea o el mecanismo de servicio administrado en el servidor.

```powershell
ssh <ssh-alias> "cd /d <directorio-app>\sistema && php artisan about"
ssh <ssh-alias> "cd /d <directorio-app>\sistema && php artisan migrate:status"
ssh <ssh-alias> "powershell -NoProfile -Command \"(Invoke-WebRequest http://127.0.0.1/up -UseBasicParsing).StatusCode\""
```

Comprobar el login, la autenticación del servidor, los respaldos y los flujos
principales desde una red autorizada.

Los valores reales de conexión, red privada, nginx, tareas programadas, respaldos y
credenciales deben almacenarse en la configuración privada del equipo de producción
o en un gestor de secretos.
