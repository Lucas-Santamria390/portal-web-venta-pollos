# Documentación — Portal de Pollos Crudos

Sistema interno para vender pollos crudos enteros: reservas por cantidad, peso por libra,
entrega, cobros totales o parciales y fiados. Los clientes **no** usan la web.

## Índice

| Documento | Contenido |
|---|---|
| [01-vision-y-alcance.md](01-vision-y-alcance.md) | Problema, propósito, alcance, actores, decisiones y criterios de aceptación |
| [02-pantallas.md](02-pantallas.md) | Mapa de navegación y especificación de las pantallas |
| [03-modelo-de-datos.md](03-modelo-de-datos.md) | Tablas, relaciones, estados, guardas y capa de dominio |
| [04-plan-de-implementacion.md](04-plan-de-implementacion.md) | Fases, entregables, pruebas, riesgos y definición de terminado |
| [05-entorno-remoto.md](05-entorno-remoto.md) | Requisitos y procedimiento general de despliegue |

## Resumen del flujo

```
Reserva (cantidad) → Entrega (pesar) → Precio (libras × $/lb) → Cobro total o parcial
                                                        └── saldo > 0 = fiado
```

Venta directa: **Vender ahora** crea, entrega y cobra en un solo paso.

## Stack

Laravel 13 · PHP 8.3 · Blade · Tailwind · SQLite · nginx + php-cgi

## Acceso

Las URLs, credenciales y detalles de red dependen del entorno y deben configurarse
fuera del repositorio. El acceso remoto puede protegerse con una red privada,
HTTPS, autenticación del servidor y el login del portal.

## Estructura del proyecto

```
portal-web-venta/
├── documentacion/   ← estos documentos
└── sistema/         ← aplicación Laravel
```
