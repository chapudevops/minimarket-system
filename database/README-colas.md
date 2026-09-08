# Cola de envíos a SUNAT

El web service de SUNAT se cae seguido. Una venta en el mostrador no puede
quedar esperando por eso, así que la venta se cierra en local y el envío del
comprobante viaja por una cola aparte.

## Cómo funciona

1. El terminal registra la venta y hace `commit`.
2. Despacha `EnviarComprobanteASunat` — **después** del commit, para que el
   worker no tome el job antes de que la venta exista.
3. El cajero ya tiene su respuesta; la venta queda en `estado_sunat = PENDIENTE`.
4. El worker toma el job, genera y firma el XML, lo envía y guarda el CDR.

Si el envío falla, la venta **no se toca**: sigue `COMPLETADA`, con su stock
descontado y su comprobante emitido. Sólo queda `PENDIENTE` ante SUNAT.

## Levantar el worker

En desarrollo:

```bash
php artisan queue:work
```

En producción conviene supervisarlo para que se reinicie solo. Con systemd:

```ini
# /etc/systemd/system/minimarket-worker.service
[Unit]
Description=Cola de envios a SUNAT
After=network.target mysql.service

[Service]
User=www-data
Restart=always
RestartSec=5
WorkingDirectory=/ruta/al/proyecto
ExecStart=/usr/bin/php artisan queue:work --tries=4 --timeout=90 --sleep=3

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now minimarket-worker
```

> Tras cada despliegue: `php artisan queue:restart`, para que los workers
> tomen el código nuevo.

## Reintentos

`EnviarComprobanteASunat` reintenta 4 veces con espera creciente
(1 min, 5 min, 15 min). Insistir cada segundo contra un servicio caído no
ayuda a nadie.

Un **rechazo** de SUNAT (códigos `2xxx` y `3xxx`) no se reintenta: el
comprobante tiene un error de fondo y volver a mandarlo da lo mismo. Queda como
`RECHAZADO` con el motivo en `descripcion_respuesta`.

Un **fallo de red o servicio caído** sí se reintenta, y si se agotan los
intentos la venta vuelve a `PENDIENTE` para que la retome el comando.

## Si la cola se atrasó

```bash
php artisan sunat:enviar --limite=50   # procesa pendientes a mano
php artisan queue:failed               # jobs que agotaron reintentos
php artisan queue:retry all            # reencolarlos
```

Conviene programar el comando por si algún job se perdió:

```
*/15 * * * * cd /ruta/al/proyecto && php artisan sunat:enviar --limite=50
```

---

# Series de comprobantes

SUNAT valida el formato de la serie y rechaza el envío si no cumple. Lo aprendí
por las malas: una nota con serie `NC01` da el error **0151 — "El nombre del
archivo ZIP es incorrecto"**, que no dice nada sobre la serie.

| Comprobante | Serie | Regla |
|---|---|---|
| Factura | `F###` | Empieza con **F** |
| Boleta | `B###` | Empieza con **B** |
| Nota de crédito | `F###` o `B###` | La letra debe coincidir con el **comprobante que modifica** |
| Nota de débito | `F###` o `B###` | Igual |
| Guía de remisión | `T###` | Empieza con **T** |

Las series actuales:

```
FACTURA        F001
BOLETA         B001
NOTA_CREDITO   FC01   ← sólo sirve para notas sobre facturas
NOTA_DEBITO    FD01   ← ídem
GUIA_REMISION  T001
COTIZACION     C001   (documento interno, no va a SUNAT)
NOTA_VENTA     NV01   (documento interno, no va a SUNAT)
```

## Limitación pendiente

Los controllers de notas buscan **una sola serie por tipo de comprobante**, sin
mirar si la nota afecta a una boleta o a una factura. Con las series actuales
sólo se pueden emitir notas sobre **facturas**.

Para poder emitir notas sobre boletas hacen falta dos series más (`BC01` y
`BD01`) y que el controller elija según el `tipo_comprobante` de la venta
referenciada. Hoy intentarlo genera una nota con serie `FC01` que SUNAT va a
rechazar.

# Cuidado al cambiar un Job

Los jobs se serializan en la tabla `jobs`. Si se agrega una propiedad al
constructor, los jobs que ya estaban encolados se deserializan **sin** ella, y
leer una typed property sin inicializar lanza un error.

`EnviarComprobanteASunat::tipo()` existe por eso: lee la propiedad con `isset()`
y cae a `'Venta'` si el payload es de una versión anterior. Al desplegar un
cambio de este tipo, conviene además vaciar la cola o correr
`php artisan queue:restart`.
