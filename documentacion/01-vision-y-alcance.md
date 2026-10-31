# Visión y alcance — Portal de Pollos Crudos

| Campo | Valor |
|---|---|
| Versión | 2.0 |
| Fecha | 2026-10-25 |
| Estado | Implementado y desplegado |
| Tipo | Sistema interno (no hay portal de clientes) |

---

## 1. Problema

El negocio vende **pollos crudos enteros** y trabaja con reservas: el cliente aparta una
cantidad, el pollo se pesa al entregarlo y se cobra por libra. Llevar esto en papel o en
hojas de cálculo produce reservas perdidas, pesos y precios que no cuadran y deudas
(fiados) que nadie recuerda.

## 2. Propósito

Un portal web interno, con login, para:

- Registrar reservas por cantidad de pollos.
- Pesar cada pollo al entregarlo y calcular su precio por libra.
- Cobrar de forma total o parcial y controlar los saldos pendientes (fiados).
- Vender de inmediato en mostrador con la acción **Vender ahora**.

## 3. Modelo de negocio

```
1. El cliente reserva N pollos (peso opcional, fecha de entrega opcional)
2. Al entregar se pesa cada pollo            → precio = libras × precio por libra
3. Se cobra total o parcialmente             → si queda saldo, es fiado
4. Al llegar a saldo cero el pedido se cierra
```

También existe la venta directa:

```
Vender ahora → se pesa, se calcula, se entrega y se cobra en un solo paso
```

El negocio entrega primero y cobra después. Por eso el sistema debe responder en todo
momento: **quién debe, cuánto debe y desde cuándo**.

## 4. Actores

| Actor | Usa la web | Responsabilidad |
|---|---|---|
| Administrador / dueño | Sí | Todo el sistema, configuración y anulaciones |
| Vendedor / cobrador | Sí | Reservar, entregar, cobrar y ajustar el precio por libra de un pedido |
| Cliente del negocio | **No** | Solo existe como dato: nombre y teléfono |

Los clientes **no** se registran, no inician sesión y no ven pedidos. No hay portal público.

## 5. Alcance del MVP

- Login de personal con roles `admin` y `vendedor`.
- Configuración del negocio: precio por libra y cálculo automático o manual del precio.
- Clientes: nombre, teléfono, nota.
- Reservas: cliente, cantidad de pollos, peso opcional por pollo, fecha de entrega y nota.
- Entrega: peso obligatorio por pollo; precio por libra precargado desde la configuración
  y ajustable para ese pedido.
- Precio por pollo = libras × precio por libra (modo automático) o precio escrito a mano
  (modo manual, registrando igual el peso).
- Venta directa **Vender ahora**: crea, entrega y cobra en un paso.
- Pagos totales y parciales; el pedido se cierra solo al llegar a saldo cero.
- Fiado: pedido entregado con saldo; cliente obligatorio.
- Pantalla **Por cobrar** ordenada por antigüedad de deuda.
- Inicio con indicadores: reservas pendientes, pollos reservados, entregados sin cobrar,
  cobrado del mes, libras vendidas del mes, próximas reservas y pedidos recientes.
- Anulación solo para el administrador y solo si no hay pagos.
- Uso desde PC del local y desde celular del personal.
- Acceso público por HTTPS con el dominio de Tailscale, protegido por autenticación
  básica y por el login del portal.

## 6. Fuera de alcance

- Portal o login de clientes.
- Catálogo de productos, inventario o stock (el negocio solo vende pollos enteros).
- Ventas por unidad fraccionada, cortes o subproductos.
- Pagos online o pasarelas de pago.
- Facturación fiscal electrónica.
- Impuestos y descuentos como campos propios.
- Multi-sucursal, multi-empresa, multi-moneda.
- Compras a proveedores y cuentas por pagar.
- Devoluciones parciales y notas de crédito.
- App móvil nativa.
- Notificaciones automáticas por correo o WhatsApp.
- Reportes avanzados y BI.

## 7. Estados de un pedido

```
reservado ──entregar──► entregado ──saldo 0──► cobrado
    │                       │
    └────anular─────────────┴──► anulado
```

| Estado | Significado | Precio | Editable |
|---|---|---|---|
| `reservado` | Apartado, sin entregar | Total 0; se calcula al entregar | Sí, todo el personal |
| `entregado` | Entregado con saldo pendiente (fiado) | Pesos y precios definidos | Solo admin y solo sin pagos |
| `cobrado` | Saldo cero | Cerrado | No |
| `anulado` | Anulado sin pagos | Sin efecto | No |

## 8. Decisiones aprobadas

| # | Decisión |
|---|---|
| D1 | Solo se venden pollos crudos enteros; no hay catálogo ni stock |
| D2 | El precio de cada pollo es `peso_lb × price_per_lb` |
| D3 | `auto_price` activo: precio automático; inactivo: precio manual por pollo, conservando el peso |
| D4 | El precio por libra se propone desde la configuración y puede ajustarse en cada pedido |
| D5 | La reserva no tiene total hasta la entrega; los pesos son opcionales al reservar |
| D6 | Al entregar, el peso de cada pollo es obligatorio |
| D7 | El pedido pasa a `cobrado` automáticamente cuando el saldo llega a cero |
| D8 | Fiado = pedido `entregado` con saldo; el cliente es obligatorio |
| D9 | **Vender ahora** con pago completo permite cliente opcional («Consumidor final») |
| D10 | Anular es exclusivo del administrador y se bloquea si existe cualquier pago |
| D11 | Tablas en inglés, interfaz en español |
| D12 | Acceso remoto por **Tailscale**, con HTTPS y autenticación adicional configurados fuera del repositorio |

## 9. Requisitos no funcionales

| ID | Requisito | Criterio |
|---|---|---|
| RNF-1 | Rendimiento | p95 < 500 ms en listados; venta completa < 3 s |
| RNF-2 | Concurrencia | 10–15 usuarios en red local, sin doble cobro ni doble entrega |
| RNF-3 | Continuidad | La operación funciona sin internet; internet solo para acceso remoto y respaldo |
| RNF-4 | Respaldo | Respaldo diario automático; RPO 24 h, RTO 4 h |
| RNF-5 | Seguridad | Hash de contraseñas, rate limiting en login, sesión expirable, autorización por rol |
| RNF-6 | Pagos | No se almacenan datos de tarjeta |
| RNF-7 | Usabilidad | Venta directa en pocos pasos; interfaz en español |
| RNF-8 | Compatibilidad | Navegadores actuales en PC y celular; diseño responsive |
| RNF-9 | Integridad | El saldo nunca queda negativo; no se cobra más que el saldo |

## 10. Criterios de aceptación del MVP

1. Se entra con login y se cierra sesión.
2. El administrador configura el precio por libra y el modo automático/manual.
3. Se crea un cliente y se registra una reserva de varios pollos.
4. La reserva queda en `reservado` con total 0 y pesos opcionales.
5. Al entregar se exige el peso de cada pollo y se calcula el total.
6. Se cobra la mitad, queda saldo (fiado), se cobra el resto y el pedido pasa a `cobrado`.
7. Un cobro mayor al saldo es rechazado.
8. **Vender ahora** con pago completo no exige cliente; con saldo sí lo exige.
9. Anular un pedido con pagos es rechazado; sin pagos, solo el administrador puede hacerlo.
10. El inicio muestra reservas, pollos reservados, saldo por cobrar, cobrado y libras del mes.
11. Todo se usa desde el navegador del celular sin zoom ni scroll horizontal.
12. Existe un respaldo diario de la base de datos.
