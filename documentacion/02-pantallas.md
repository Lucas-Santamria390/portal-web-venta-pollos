# Pantallas — Portal de Pollos Crudos

| Campo | Valor |
|---|---|
| Versión | 2.1 |
| Fecha | 2026-10-25 |
| Referencia | `01-vision-y-alcance.md` |

---

## Mapa de navegación

```
Login
  └── Inicio
        ├── Pedidos ──► Nueva reserva
        │           └── Vender ahora
        │           └── Detalle de pedido ──► [Entregar] [Editar] [Cobrar saldo / Abonar] [Anular]
        ├── Por cobrar
        ├── Clientes ──► Detalle de cliente ──► [Nuevo pedido]
        ├── Usuarios (solo administrador)
        └── Configuración (solo administrador)
```

Navegación principal: **Inicio · Pedidos · Por cobrar · Clientes · Usuarios · Configuración**.
Escritorio: barra lateral. Celular: barra inferior fija. **Usuarios** y **Configuración**
solo aparecen para el administrador.

---

## 1. Login

- Usuario (email) y contraseña.
- Sin registro público ni recuperación por correo; la contraseña la resetea el administrador.
- Error genérico: "Usuario o contraseña incorrectos".
- Rate limiting en intentos fallidos.

## 2. Inicio

| Tarjeta | Contenido |
|---|---|
| Reservas pendientes | Pedidos en estado `reservado` |
| Pollos reservados | Cantidad total de pollos en reservas pendientes |
| Entregados sin cobrar | Pedidos `entregado` con saldo + suma del saldo |
| Cobrado este mes | Suma de pagos registrados en el mes |
| Libras vendidas este mes | Peso de los pollos entregados en el mes |

Listas cortas: **Próximas reservas** (5, ordenadas por fecha de entrega) y
**Pedidos recientes** (5, con estado y saldo).
Accesos directos: **Nueva reserva**, **Vender ahora** y **Por cobrar**.

## 3. Pedidos (listado)

- Pestañas: todos, reservados, entregados, cobrados, anulados.
- Búsqueda por cliente o número de pedido; rango de fechas.
- Columnas: fecha, cliente, pollos, entrega, total, pagado, saldo, estado, acciones.
- En reservas, total/pagado/saldo se muestran como `—` porque el precio aún no existe.
- Colores de estado: reservado ámbar, entregado azul, cobrado verde, anulado rojo.
- En celular: tarjetas con estado y saldo siempre visibles.
- Botones: **Nueva reserva** y **Vender ahora**.

## 4. Nueva reserva

1. **Cliente** obligatorio; botón "Crear cliente" sin salir de la pantalla.
2. **Cantidad de pollos** (1–100).
3. **Peso por pollo** opcional en este momento.
4. **Fecha de entrega** opcional.
5. **Nota** opcional.
6. Acción: **Guardar reserva**.

La reserva nace en `reservado` con total 0. El panel lateral muestra el precio por libra
vigente y recuerda que el total se calcula al entregar.

## 5. Vender ahora

Mismo formulario, pero:

- El peso de **cada pollo es obligatorio**.
- Se muestra el **precio por libra** (precargado y ajustable) o el precio manual por pollo.
- Se muestra el **total** en vivo.
- Se indica el **pago recibido** (precargado con el total) y el **medio de pago**.
- Si el pago no cubre el total, aparece el aviso de fiado y el cliente pasa a ser obligatorio.
- Si el pago cubre el total, el cliente es opcional («Consumidor final»).
- Acción: **Vender ahora**: crea, entrega y cobra en un paso.

## 6. Detalle de pedido

- Cabecera: número, estado, cliente (enlace), fechas y usuario que lo registró.
- Dinero destacado: **Total · Pagado · Saldo**.
- En reservas, total/pagado/saldo se muestran como pendientes de cálculo.
- Tabla de pollos: posición, peso en libras y precio.
- Historial de pagos: fecha, medio, usuario, nota y monto.
- Acciones por estado:

| Estado | Acciones |
|---|---|
| reservado | Entregar · Editar · Anular (admin) |
| entregado | Cobrar saldo · Abonar · Editar (admin, sin pagos) · Anular (admin, sin pagos) |
| cobrado | Ninguna |
| anulado | Ninguna |

## 7. Entregar pedido

- Resumen del pedido y cliente.
- Un campo de **peso en libras** por cada pollo (obligatorio).
- Si `auto_price` está activo: campo de **precio por libra** precargado desde el pedido y
  ajustable para esa entrega; el precio de cada pollo se calcula en vivo.
- Si `auto_price` está inactivo: un campo de **precio** por cada pollo, conservando el peso.
- Total en vivo y acción **Confirmar entrega**.
- Al confirmar, el pedido pasa a `entregado` y queda el saldo por cobrar.

## 8. Editar pedido

- **Reservado**: cualquier usuario puede cambiar cliente, cantidad, pesos, fecha y nota.
- **Entregado**: solo el administrador y solo si no tiene pagos. Permite corregir pesos,
  precios y el precio por libra de ese pedido.
- **Cobrado / anulado**: no se edita.

## 9. Registrar pago (modal)

- Dos accesos: **Cobrar saldo** (precarga el saldo completo) y **Abonar** (monto vacío).
- Monto, medio de pago (efectivo, transferencia, tarjeta, otro), fecha y nota opcional.
- Rechaza monto ≤ 0 o mayor al saldo.
- Si el saldo llega a cero, el pedido pasa a `cobrado` automáticamente.

## 10. Por cobrar

- Solo pedidos `entregado` con saldo > 0.
- Orden: deuda más antigua primero.
- Encabezado: total por cobrar y cantidad de pedidos con saldo.
- Fila: cliente, fecha de entrega, pollos, total, pagado, saldo y acceso para cobrar.

## 11. Clientes (listado)

- Búsqueda por nombre o teléfono.
- Columnas: nombre, teléfono, saldo pendiente, último pedido.
- Botón **Nuevo cliente**. Formulario: nombre, teléfono, nota.

## 12. Detalle de cliente

- Datos y edición.
- **Total adeudado** destacado y cantidad de pedidos con saldo.
- Historial de pedidos con estado, total y saldo.
- Botón **Nuevo pedido** con el cliente preseleccionado.

## 13. Usuarios (solo administrador)

- Listado con búsqueda por nombre o correo.
- Columnas: usuario (nombre y correo), rol, estado y acciones.
- Botón **Nuevo usuario**. Formulario: nombre, correo, rol (`Administrador` o
  `Vendedor`), contraseña y confirmación, cuenta activa.
- **Editar**: permite cambiar datos, rol y contraseña (opcional; en blanco conserva la
  actual) y activar o desactivar la cuenta.
- **Activar / Desactivar** desde el listado. Un usuario inactivo no puede iniciar sesión.
- El administrador no puede desactivarse ni quitarse el rol a sí mismo.
- No se eliminan usuarios: se desactivan, porque los pedidos los referencian.

## 14. Configuración (solo administrador)

- **Precio por libra** (decimal, ≥ 0).
- **Calcular el precio automáticamente**:
  - Activo: precio del pollo = libras × precio por libra.
  - Inactivo: se escribe el precio de cada pollo al vender o entregar, sin dejar de
    registrar su peso.
- Acción **Guardar configuración**.

---

## Criterios de diseño transversales

- El dinero manda: total, pagado y saldo visibles en toda pantalla de pedido.
- Pocos toques: vender un pollo en pocos pasos.
- Sin hover: todo funciona con toque.
- Confirmación solo en lo irreversible: anular.
- Estados vacíos con acción: "Aún no hay pedidos. Registrar el primero".
- Formularios cortos: si un campo no cambia una decisión, no va.
- Diseño responsive primero para celular, no adaptado al final.

## 15. Eliminación de registros (solo administrador)

- **Eliminar cobro**: disponible en el historial de pagos del pedido. El saldo se
  recalcula; si un pedido cobrado vuelve a tener saldo, pasa a `entregado`.
- **Eliminar pedido**: disponible en el listado y detalle. Elimina permanentemente
  el pedido, sus pollos y todos sus cobros, incluso si ya estaba cobrado.
- **Eliminar cliente**: disponible en el listado y detalle. Elimina permanentemente
  el cliente y todo su historial de pedidos, pollos y cobros.
- Todas las acciones requieren confirmación explícita y no se pueden deshacer.
- Los vendedores no ven estas acciones y reciben `403` si intentan acceder por URL.
