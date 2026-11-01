# Plan de implementación — Portal de Pollos Crudos

| Campo | Valor |
|---|---|
| Versión | 2.0 |
| Fecha | 2026-10-25 |
| Stack | Laravel 13 · PHP 8.3 · Blade · Tailwind · SQLite |
| Referencias | `01-vision-y-alcance.md`, `02-pantallas.md`, `03-modelo-de-datos.md` |

---

## Fase 0 — Entorno (completada)

**Estado real (2026-10-25):** ver `05-entorno-remoto.md`.

| Entorno | Stack |
|---|---|
| Local (desarrollo) | Laragon · PHP 8.3.30 · Composer 2.9.4 · Node 24 |
| Remoto (ejecución) | PHP 8.3.36 · Composer 2.10.3 · SQLite · red privada |

Acceso remoto por SSH mediante una configuración privada del entorno.

**Aceptación:** `php -v` y `composer -V` funcionan en ambos entornos; SQLite operativo en remoto.

## Fase 1 — Base de la aplicación (completada)

1. Proyecto Laravel 13 dentro de `sistema/`.
2. Laravel Breeze (Blade + Tailwind) para autenticación.
3. Layout responsive con barra lateral en escritorio y barra inferior en celular.
4. Seed del usuario administrador.
5. Middleware `admin` y enum `UserRole` (`admin`, `vendedor`).

**Decisiones aplicadas:**

- Registro público, recuperación de contraseña y verificación de correo deshabilitados.
- Interfaz en español; tablas y código en inglés.
- El administrador inicial y su contraseña se configuran mediante variables privadas
  del entorno; no se documentan credenciales en el repositorio.
- Rate limiting de login y sesión de 120 minutos.

## Fase 2 — Clientes (completada)

1. Migración y CRUD de clientes.
2. Listado con búsqueda por nombre o teléfono y saldo pendiente calculado.
3. Detalle con contacto, nota, deuda e historial de pedidos.

**Decisiones aplicadas:**

- Alta y edición disponibles para todo el personal.
- El teléfono se puede tocar para llamar o abrir WhatsApp; no hay integración.
- Los clientes no se eliminan: la FK `restrict` de pedidos lo impide.
- El saldo y el último pedido se calculan sobre `orders` y `payments`.

## Fase 3 — Configuración y modelo de pedidos (completada)

1. Migración `settings` con una sola fila (`price_per_lb`, `auto_price`).
2. Migraciones `orders` y `order_items` (un registro por pollo).
3. Migración `payments` con `order_id`.
4. Enums `OrderStatus` y `PaymentMethod`.
5. Modelos `Setting`, `Order`, `OrderItem`, `Payment` y relaciones.
6. Eliminación del dominio anterior: `products`, `stock_movements`, `sales`, `sale_items`,
   controladores, servicios, vistas y pruebas.

**Decisiones aplicadas:**

- `orders.customer_id` es nullable para la venta directa de consumidor final.
- `order_items` guarda `position`, `weight_lb` y `price`; una fila por pollo.
- El total nunca se guarda como deuda: se calcula de los ítems y los pagos.
- `Setting::current()` crea la configuración por defecto si no existe.
- La base local y la de producción se reiniciaron con el esquema nuevo
  (`migrate:fresh --seed --force`).

## Fase 4 — Reservas (completada)

1. Pantalla **Nueva reserva** con cliente, cantidad, pesos opcionales, fecha y nota.
2. `OrderService::reserve()` transaccional.
3. Listado de pedidos con pestañas, búsqueda y rango de fechas.
4. Detalle de pedido con pollos, pesos y total pendiente de cálculo.
5. Edición de reservas por cualquier usuario.
6. Alta rápida de cliente desde el formulario (JSON).

**Decisiones aplicadas:**

- La reserva nace en `reservado`, con `total = 0` y `price = null` en cada pollo.
- Los pesos son opcionales al reservar y se pueden completar después.
- Se guarda el `price_per_lb` vigente como referencia del pedido.
- El listado muestra `—` en total, pagado y saldo mientras está reservado.

## Fase 5 — Entrega y precio por peso (completada)

1. Pantalla **Entregar pedido** con un peso obligatorio por pollo.
2. Precio por libra precargado desde el pedido/configuración y ajustable.
3. Modo automático: `precio = libras × precio por libra`.
4. Modo manual: precio escrito por pollo, conservando el peso.
5. `OrderService::deliver()` calcula precios y total y pasa a `entregado`.
6. Edición de pedidos entregados solo para el administrador y solo sin pagos.

**Decisiones aplicadas:**

- La entrega exige el peso de todos los pollos; si falta uno, no se modifica nada.
- `auto_price` se lee de la configuración vigente al entregar/editar.
- El `price_per_lb` del pedido se conserva como snapshot; cambiarlo en configuración no
  altera pedidos ya entregados.
- La doble entrega se rechaza.

## Fase 6 — Cobros y fiados (completada)

1. `OrderService::registerPayment()` con validación de saldo.
2. Modal con **Cobrar saldo** (precarga el saldo) y **Abonar** (monto libre).
3. Historial de pagos en el detalle.
4. Transición automática a `cobrado` cuando el saldo llega a cero.
5. Pantalla **Por cobrar** ordenada por antigüedad.
6. Deuda real y último pedido en clientes.

**Decisiones aplicadas:**

- Solo se cobran pedidos `entregado`.
- El monto debe ser mayor a cero y no puede superar el saldo.
- Se permiten varios pagos parciales.
- Cada pago guarda usuario, fecha, medio y nota.
- Los errores de cobro vuelven siempre al detalle del pedido.

## Fase 7 — Venta directa (completada)

1. Pantalla **Vender ahora** reutilizando el formulario de pedidos.
2. `OrderService::sellNow()` crea, entrega y registra el pago inicial en una transacción.
3. Cliente opcional si el pago cubre el total; obligatorio si queda saldo.
4. Pago inicial precargado con el total y ajustable.

**Decisiones aplicadas:**

- Si el pago cubre el total, el pedido nace `cobrado` con `paid_at`.
- Si queda saldo, nace `entregado` (fiado) y requiere cliente.
- El pago inicial no puede superar el total.
- La venta directa también admite precios manuales cuando `auto_price` está desactivado.

## Fase 8 — Anulación y permisos (completada)

1. `OrderService::cancel()` para pedidos reservados o entregados sin pagos.
2. Ruta y acción de anulación protegidas por el middleware `admin`.
3. Botón de anulación visible solo para el administrador y cuando corresponde.
4. Edición de entregados restringida al administrador y sin pagos.

**Decisiones aplicadas:**

- Anular un pedido con cualquier pago es rechazado.
- Un pedido `cobrado` o `anulado` no se puede anular.
- El vendedor puede reservar, entregar, cobrar y ajustar el precio por libra del pedido;
  no puede anular ni entrar a Configuración.

## Fase 9 — Inicio e indicadores (completada)

1. Tarjetas: reservas pendientes, pollos reservados, entregados sin cobrar (con saldo),
   cobrado del mes y libras vendidas del mes.
2. Listas: próximas reservas (por fecha) y pedidos recientes.
3. Accesos directos a nueva reserva, vender ahora y por cobrar.

**Decisiones aplicadas:**

- Las reservas sin fecha de entrega se ordenan al final.
- Las libras del mes cuentan solo pedidos entregados/cobrados con `delivered_at` del mes.
- El cobrado del mes suma `payments.paid_at` del mes.
- Los pedidos anulados no se muestran como deuda.

## Fase 10 — Endurecimiento y despliegue (completada, con verificaciones manuales pendientes)

1. Mensajes de validación en español.
2. Respaldos automáticos diarios con retención de 14 días.
3. Servidor en el puerto 80 con tareas de Windows (arranque, respaldo y vigilancia).
4. Acceso remoto por red privada.
5. Prueba de aceptación del flujo completo.

**Estado:** sistema desplegado en una instalación remota protegida por red privada
y autenticación adicional.

**Pendiente de verificación manual en el local:**

- Recorrer el flujo completo desde un celular real a través de la red privada.
- Cambiar la contraseña inicial del administrador y la del usuario `Lucas`.
- Evaluar deshabilitar el acceso SSH por contraseña y restringirlo a la red privada.

## Fase 11 — Rendimiento y acceso público (completada)

1. Diagnóstico: el servidor integrado de PHP atendía una petición por vez y OPcache
   estaba desactivado, con ~650 ms por página.
2. OPcache activado y exclusiones de Windows Defender.
3. Sesiones y caché movidas de SQLite a archivos.
4. nginx 1.30.6 + pool de 4 procesos `php-cgi` en los puertos 9001-9004.
5. Autenticación básica de nginx como segunda barrera.
6. Publicación opcional mediante un **túnel HTTPS**, con la URL definida fuera del repositorio.
7. `trustProxies` y `SecureSessionCookie` para reconocer HTTPS detrás del proxy.

**Resultados medidos:**

| Escenario | Antes | Después |
|---|---|---|
| Página individual (red interna) | ~650 ms | ~50 ms |
| 4 peticiones simultáneas | 4,5 s en cola | ~50-110 ms cada una |
| Acceso público por túnel | — | ~0,7-0,9 s (latencia del punto de entrada) |

**Decisión aplicada:** la cookie de sesión se marca `Secure` solo en HTTPS, para no
romper el acceso por HTTP dentro de la red del local.

## Fase 12 — Gestión de usuarios (completada)

1. Pantalla **Usuarios** solo para administradores: listado, búsqueda, alta, edición,
   cambio de contraseña y activar/desactivar.
2. Roles existentes `admin` y `vendedor`; sin borrado de usuarios (se desactivan).
3. El administrador no puede desactivarse ni quitarse el rol a sí mismo.
4. El login ahora exige `active = true`; un usuario desactivado no puede entrar.
5. Enlace en la navegación (escritorio y barra inferior móvil, ahora de 6 elementos
   para el administrador).

---

## Estrategia de pruebas

| Nivel | Qué cubre |
|---|---|
| Feature tests | Reservas, edición, entrega, precios automáticos/manuales, venta directa, anulación |
| Feature tests | Pagos parciales/totales, sobrepago, pedidos reservados/cobrados, por cobrar, deuda por cliente |
| Feature tests | Configuración, permisos por rol, dashboard y navegación |
| Feature tests | Cookies seguras en HTTPS y compatibilidad con HTTP local |
| Feature tests | Autenticación, perfil y respaldos |
| Manual | Flujo completo en celular y PC |

Suite actual: **78 tests / 377 aserciones**.

Casos obligatorios: doble entrega, entrega sin peso, precio manual faltante, sobrepago,
anulación con pagos, venta directa con saldo sin cliente y venta directa con sobrepago.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Precio por libra desactualizado | Configuración visible y editable; snapshot por pedido |
| Peso mal cargado | Edición del entregado por el administrador mientras no haya pagos |
| Personal cobra y no registra | Regla operativa: el pago se registra en el momento |
| PC del local apagada = sin acceso remoto | Definir horario; evaluar VPS si se requiere 24/7 |
| Windows 10 Home sin soporte desde octubre de 2025 | No exponer más servicios que el portal; evaluar VPS Linux |
| Latencia del acceso público por túnel | Usar la URL interna para el trabajo diario; el túnel para consultas externas |
| Pérdida de datos | Respaldo diario + copia externa semanal |
| Cambios de alcance | Documentos 01–03 como línea base |

## Definición de terminado

- Los criterios de aceptación del MVP se cumplen.
- Los tests de los flujos críticos pasan.
- Funciona en celular sin zoom ni scroll horizontal.
- Existe respaldo automático y se probó una restauración.
- El administrador opera el sistema sin asistencia.
