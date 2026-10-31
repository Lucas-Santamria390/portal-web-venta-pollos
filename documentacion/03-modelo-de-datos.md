# Modelo de datos — Portal de Pollos Crudos

| Campo | Valor |
|---|---|
| Versión | 2.0 |
| Fecha | 2026-10-25 |
| Motor | SQLite (local y remoto) |
| Convención | Tablas en inglés, interfaz en español |

---

## Diagrama entidad-relación

```
users ──┐
        │
customers ──┐
        │   │
        │   └──< orders >──< order_items
        │         │
        │         └──< payments
        │
settings (una sola fila)
```

## Tablas

### `users`

| Campo | Tipo | Regla |
|---|---|---|
| id | bigint PK | |
| name | string(100) | obligatorio |
| email | string(150) | único, login |
| password | string | hash |
| role | enum(`admin`,`vendedor`) | default `admin` |
| active | boolean | default true |
| timestamps | | |

### `customers`

| Campo | Tipo | Regla |
|---|---|---|
| id | bigint PK | |
| name | string(150) | obligatorio, indexado |
| phone | string(30) | indexado, opcional |
| note | text | opcional |
| timestamps | | |

### `settings`

Una sola fila de configuración del negocio.

| Campo | Tipo | Regla |
|---|---|---|
| id | bigint PK | |
| price_per_lb | decimal(10,2) | ≥ 0, default 0 |
| auto_price | boolean | default true |
| timestamps | | |

`Setting::current()` devuelve la fila existente o la crea con los valores por defecto.

### `orders`

| Campo | Tipo | Regla |
|---|---|---|
| id | bigint PK | |
| customer_id | FK → customers | nullable, `restrict` |
| user_id | FK → users | quién lo registró, `restrict` |
| status | enum(`reservado`,`entregado`,`cobrado`,`anulado`) | default `reservado` |
| delivery_date | date | nullable, fecha prevista |
| reserved_at | timestamp | nullable |
| delivered_at | timestamp | null hasta entregar |
| paid_at | timestamp | null hasta saldo cero |
| cancelled_at | timestamp | null hasta anular |
| price_per_lb | decimal(10,2) | nullable, snapshot del pedido |
| total | decimal(12,2) | ≥ 0, suma de los precios de los pollos |
| note | text | opcional |
| timestamps | | |

Índices: `(status)`, `(created_at)`, `(status, delivery_date)`, `(status, delivered_at)`.

`customer_id` es nullable para permitir la venta directa pagada por completo
(«Consumidor final»). En reservas y fiados el cliente es obligatorio.

### `order_items`

Una fila por pollo.

| Campo | Tipo | Regla |
|---|---|---|
| id | bigint PK | |
| order_id | FK → orders | `cascade` |
| position | unsigned int | orden del pollo dentro del pedido |
| weight_lb | decimal(8,2) | nullable; obligatorio al entregar |
| price | decimal(12,2) | nullable; null mientras está reservado |
| timestamps | | |

Índice único: `(order_id, position)`.

### `payments`

| Campo | Tipo | Regla |
|---|---|---|
| id | bigint PK | |
| order_id | FK → orders | `cascade` |
| user_id | FK → users | quién cobró |
| amount | decimal(12,2) | > 0 y ≤ saldo |
| method | enum(`efectivo`,`transferencia`,`tarjeta`,`otro`) | obligatorio |
| paid_at | datetime | obligatorio |
| note | string(255) | opcional |
| timestamps | | |

## Dinero: una sola fuente de verdad

No se guarda `paid_amount` en `orders`. El saldo se calcula:

```
saldo = total − suma(payments.amount)
```

En Laravel: `withSum('payments', 'amount')` en listados para evitar N+1.
`status` sí se persiste porque es una máquina de estados, no un cálculo.

El total de un pedido es la suma de `order_items.price`. Mientras está reservado vale 0
y sus precios son `null`; se completan al entregar.

## Precio de cada pollo

Con `auto_price = true`:

```
price = weight_lb × price_per_lb
```

Con `auto_price = false`:

```
price = valor escrito por el usuario
```

En ambos casos `weight_lb` se registra. `auto_price` se lee de la configuración vigente
en el momento de vender, entregar o editar; no se guarda en el pedido. El
`price_per_lb` sí se guarda como snapshot del pedido y puede ajustarse en cada entrega.

## Máquina de estados y guardas

```
reservado ──entregar──► entregado ──saldo 0──► cobrado
    │                       │
    └────anular─────────────┴──► anulado
```

| Acción | Desde | Guardas |
|---|---|---|
| Entregar | reservado | peso obligatorio de cada pollo; una sola ejecución |
| Registrar pago | entregado | `0 < monto ≤ saldo`; saldo 0 → `cobrado` |
| Anular | reservado o entregado | sin pagos; solo `admin` |
| Editar | reservado | todo el personal; cliente, cantidad, pesos, fecha y nota |
| Editar | entregado | solo `admin` y sin pagos; recalcula pesos, precios y total |
| Vender ahora | — | crea el pedido ya entregado y registra el pago inicial |

Nada se edita en `cobrado` ni en `anulado`.

## Capa de dominio

```
OrderService::reserve()          → pedido reservado + pollos (peso opcional, precio null)
OrderService::sellNow()          → pedido entregado/cobrado + pago inicial
OrderService::update()           → edición según estado y permisos
OrderService::deliver()          → exige pesos, calcula precios y total
OrderService::registerPayment()  → inserta pago y evalúa saldo
OrderService::cancel()           → anula si no hay pagos
```

Cada método se ejecuta dentro de `DB::transaction()` con `lockForUpdate()` sobre el pedido.

## Casos que el sistema debe rechazar

| Caso | Respuesta |
|---|---|
| Entregar dos veces | "Solo se pueden entregar pedidos reservados" |
| Entregar sin el peso de algún pollo | "Registra el peso del pollo N" |
| Precio por libra en cero con cálculo automático | "Configura un precio por libra mayor a cero" |
| Precio manual faltante | "Indica el precio del pollo N" |
| Cobrar más que el saldo | "El monto supera el saldo de $X" |
| Cobrar cero o negativo | "El monto debe ser mayor a cero" |
| Cobrar un pedido reservado o ya cobrado | "Solo se pueden cobrar pedidos entregados" / "Este pedido ya está cobrado" |
| Venta directa con saldo y sin cliente | "Selecciona un cliente para dejar saldo pendiente (fiado)" |
| Pago inicial mayor al total | "El pago no puede superar el total de $X" |
| Anular con pagos | "No se puede anular un pedido cobrado o con pagos registrados" |
| Editar un pedido entregado con pagos | "Este pedido ya no se puede editar" |
| Borrar un cliente con pedidos | bloqueado por FK `restrict` |

## Datos iniciales

- Un usuario administrador configurado mediante variables privadas del entorno.
- Una fila de configuración con `price_per_lb = 0` y `auto_price = true`.
- Sin clientes ni pedidos de ejemplo.

## Fuera del modelo (a propósito)

Catálogo de productos, stock e inventario, categorías, impuestos, descuentos, facturación,
multi-sucursal, monedas, proveedores, compras, devoluciones parciales y notas de crédito.

## Eliminación administrativa

Solo el administrador puede borrar cobros, pedidos y clientes. Borrar un cobro
recalcula el saldo y el estado del pedido dentro de una transacción. Borrar un
pedido elimina en cascada sus `order_items` y `payments`. Borrar un cliente elimina
en cascada todos sus pedidos y el historial financiero asociado.
