# Modelo tributario del catálogo

Cómo representa el sistema la tributación de un producto de minimarket, qué
cambió en esta fase y qué sigue necesitando validación contable.

---

## A. Qué significaba `operacion` antes

Una sola columna `varchar(50)` en `productos`, con tres valores posibles
(`GRAVADO`, `EXONERADO`, `INAFECTO`), que cargaba con todo lo tributario.

Se consumía en tres lugares:

| Dónde | Para qué |
|---|---|
| `Producto::getAfectacionIgvSunatAttribute()` | traducir a Catálogo 07 de SUNAT |
| `Producto::gravaIgv()` | decidir si `Monto` desagrega IGV |
| `Producto::getOperacionTextoAttribute()` | etiqueta en la interfaz |

Y llegaba al comprobante por `ConstructorComprobante::lineas()`, que arma cada
`SaleDetail` con `setTipAfeIgv()` y acumula los totales en
`MtoOperGravadas` / `MtoOperExoneradas` / `MtoOperInafectas`.

**El problema:** hay productos de minimarket que no caben en esa única columna.

- Una cerveza es **GRAVADA de IGV** y **además** está en el ámbito del **ISC**.
  Con una sola columna hay que elegir uno de los dos hechos y perder el otro.
- El arroz pilado tiene el tratamiento del **IVAP** (Ley 28211), que no es
  ninguno de los tres valores del Catálogo 07.
- La **detracción** ya vivía aparte, pero por costumbre se la mezclaba en la
  misma conversación.

---

## B. Qué significa después de esta fase

`operacion` significa **exactamente una cosa: la afectación del IGV**
(Catálogo 07 de SUNAT). Nada más. Es lo único que viaja al comprobante.

Al lado suyo hay dos columnas nuevas en `productos`:

| Columna | Tipo | Qué dice | ¿Va al XML? |
|---|---|---|---|
| `operacion` | varchar(50) | Afectación IGV: GRAVADO / EXONERADO / INAFECTO | **Sí**, línea por línea |
| `afecto_isc` | boolean | El producto está en el ámbito del ISC | No |
| `afecto_ivap` | boolean | El producto está en el ámbito del IVAP (Ley 28211) | No |
| `detraccion` | boolean | *(ya existía)* configuración de detracción | No |

### Por qué `afecto_isc` y `afecto_ivap` son informativos

Porque un minimarket **no los declara**. El ISC grava la venta a nivel de
productor e importador: al minimarket, que compra a un distribuidor y revende,
le llega incorporado en el costo. Su boleta lleva IGV y nada más.

Marcar el producto sirve para identificarlo, analizar márgenes y responder ante
una revisión — no para emitir un tributo aparte.

### Por qué NO se creó una tabla `producto_tributos`

Se evaluó. No entra todavía, por tres razones:

1. **Son tres tributos y dos son booleanos.** Una tabla hija aportaría
   flexibilidad que hoy nadie usa.
2. **El costo es real.** `productos` no tiene ninguna tabla hija de este tipo
   (no hay ni categorías ni marcas normalizadas). Una tabla de tributos
   obligaría a una junta en cada consulta del POS, en el constructor de
   comprobantes y en el CRUD, para leer dos booleanos.
3. **No hay nada que configurar.** Una tabla `producto_tributos` se justifica
   cuando cada tributo necesita *parámetros* — sistema de cálculo (Catálogo 08)
   y tasa. Hoy no se calculan.

**Cuándo sí conviene esa tabla:** el día que el negocio importe o produzca, y
tenga que declarar ISC de verdad. Ahí hacen falta sistema de cálculo, base y
tasa por producto, y dos booleanos dejan de alcanzar. Ese es el disparador.

### `PENDIENTE`

Marcador del pipeline de catálogo. Significa *"todavía nadie determinó cómo
tributa esto"*. Vive en los CSV de `data/catalogo-minimarket/`.

Tiene **prohibido llegar a la tabla `productos`**, y está bloqueado en tres
capas:

1. `EsquemaMaestro::faltantesParaImportar()` — no se importa.
2. `ProductoController` (`in:GRAVADO,EXONERADO,INAFECTO`) — no se crea a mano.
3. `TerminalController::procesarPago()` — si de alguna forma llegara a la base,
   **no se vende**: la venta entera se rechaza con 422 y el stock no se toca.

La tercera capa es la importante. Sin ella, `ConstructorComprobante` aplica
`GRAVADO` por defecto ante un valor desconocido, y saldría un comprobante con
un IGV que nadie decidió.

> Ojo con el homónimo: `ventas.estado` también usa `'PENDIENTE'`, pero eso es
> una venta a crédito sin cobrar. Ejes distintos, tablas distintas.

---

## C. Cómo se representa cada producto

| Producto | `operacion` | `afecto_isc` | `afecto_ivap` | Comentario |
|---|---|---|---|---|
| **Coca-Cola** | `GRAVADO` | *revisar* | `0` | IGV sin discusión. El ISC de bebidas azucaradas depende del azúcar por 100 ml: la versión sin azúcar puede quedar fuera. Se marca por producto. |
| **Cerveza** | `GRAVADO` | `1` | `0` | El caso que el modelo viejo no sabía representar. |
| **Bebida energética** | `GRAVADO` | *revisar* | `0` | Partida 22.02. Confirmar alcance antes de darla por afecta. |
| **Arroz pilado** | `PENDIENTE` | `0` | `1` | El producto está en el ámbito del IVAP; que *la venta del minimarket* quede afecta depende de la operación. **No se asume.** |
| **Leche evaporada** | `PENDIENTE` | `0` | `0` | Industrializada: la regla general apunta a GRAVADO, pero se confirma. No hereda de la leche cruda. |
| **Leche cruda entera** | `EXONERADO` | `0` | `0` | Nominada en el Apéndice I. Un minimarket rara vez la vende. |
| **Huevo fresco** | `EXONERADO` | `0` | `0` | Huevos de ave con cáscara frescos, Apéndice I. |
| **Fruta fresca** | `EXONERADO` | `0` | `0` | Apéndice I. Verificar la partida: procesada o en conserva **no** entra. |
| **Detergente** | `GRAVADO` | `0` | `0` | Regla general. El Apéndice I no alcanza artículos de limpieza. |

*revisar* significa que la matriz dice `REVISAR`: el producto entra al catálogo
(su IGV es seguro) pero queda listado en el reporte para que alguien decida.

Los tres primeros muestran el punto: **los tres son `GRAVADO` y se diferencian
en otra columna.** Antes eran indistinguibles.

---

## D. Cómo se clasifica: la matriz

`diccionarios/reglas_tributarias.csv`, con granularidad
**(categoría, subcategoría, producto_tipo)**.

```csv
categoria,subcategoria,producto_tipo,igv,isc,ivap,requiere_revision,fuente_normativa,observacion
```

| Columna | Valores | Significado |
|---|---|---|
| `producto_tipo` | `*` o un tipo | `*` aplica a toda la subcategoría |
| `igv` | GRAVADO / EXONERADO / INAFECTO / PENDIENTE | afectación |
| `isc` / `ivap` | SI / NO / REVISAR | **`REVISAR` nunca se convierte en `SI`** |
| `requiere_revision` | true / false | **habla solo del IGV**: es lo único que bloquea |

### Las dos reglas que evitan clasificar mal

**1. `producto_tipo` existe porque hay familias que no se pueden clasificar
enteras.** "Leche cruda entera" está nominada en el Apéndice I; "leche
evaporada" es un producto industrializado. Una regla por subcategoría daría una
de las dos por buena para las dos.

**2. Un tipo sin regla propia NO hereda de una familia que distingue.** Si
`LACTEOS/LECHE` declara reglas por tipo, entonces `LECHE_DE_ALMENDRAS` sale
`PENDIENTE` en vez de tomar prestada la regla general. Heredar ahí sería
clasificar por parecido.

**Nunca se clasifica por el nombre del producto.** No hay ninguna regla del tipo
`if descripcion contiene "arroz" entonces IVAP`. La descripción es texto libre
de un catálogo ajeno; la clasificación sale de la categoría declarada y del
`producto_tipo` que alguien asignó a conciencia.

### `requiere_revision` bloquea, `REVISAR` avisa

Son dos señales distintas y separarlas importa:

- `requiereRevisionIgv()` → la afectación no es segura → **PENDIENTE, bloquea.**
- `requiereRevision()` → algo (IGV, ISC o IVAP) amerita revisión → **reporta,
  no bloquea.**

Una gaseosa es GRAVADA sin discusión aunque su ISC dependa del azúcar.
Bloquearla por eso dejaría medio catálogo afuera sin ninguna razón.

---

## E. El arroz: estrategia del sistema

**No todo producto que dice "arroz" es IVAP.** El sistema distingue tres cosas:

1. **Naturaleza del producto** — ¿es arroz pilado en el sentido de la Ley 28211?
   Eso lo dice `producto_tipo`, no la descripción. `ARROZ_PILADO` → `afecto_ivap = 1`.
   `ARROZ_INTEGRAL` → `REVISAR`, puede no serlo. Sin `producto_tipo` → no se
   marca nada.

2. **Operación realizada** — el IVAP alcanza a *determinadas operaciones*. Que
   la venta de un minimarket quede afecta a IVAP, exonerada de IGV o gravada
   **depende de qué operación hace el negocio en la cadena**, no del producto.
   El sistema **no lo decide**: deja `operacion = PENDIENTE`.

3. **Tratamiento almacenado** — `afecto_ivap` guarda el hecho sobre el producto
   (está en el ámbito). `operacion` guarda la decisión sobre la venta. Son dos
   columnas porque son dos preguntas.

**Consecuencia práctica:** hoy ningún arroz se puede importar al catálogo
definitivo hasta que el contador defina el punto 2. Es intencional.

---

## F. El ISC: qué se implementó y qué no

**Implementado:** una bandera que dice si el producto está en el ámbito.

**No implementado, a propósito:** tasas, sistema de cálculo (Catálogo 08), base
imponible, y la línea de ISC en el XML. `greenter` los soporta
(`setMtoBaseIsc`, `setPorcentajeIsc`, `setTipSisIsc`) pero **no se usan**, y no
hay ninguna tasa hardcodeada en el proyecto.

Productos de minimarket en el ámbito, según la matriz:

- **Bebidas alcohólicas** (cerveza, vino, pisco, ron, whisky, vodka,
  espumantes) → `SI`. Apéndice IV, sin ambigüedad.
- **Cigarrillos** → `SI`. El sistema todavía no maneja tabaco; la fila existe
  para que el día que se venda no se clasifique a ojo.
- **Gaseosas, jugos, energizantes, bebidas deportivas, té listo** → `REVISAR`.
  Están en la partida, pero la tasa y el alcance dependen del azúcar del
  producto concreto.

---

## G. Detracciones: qué se revisó y qué NO se tocó

Se auditó y **está correctamente separada del IGV**. No se modificó nada.

| Hallazgo | Estado |
|---|---|
| `productos.detraccion` es una columna propia, independiente de `operacion` | Correcto |
| `ventas.detraccion` es otra columna, de la venta, no del producto | Correcto |
| ¿Hay reglas que la activen automáticamente por categoría? | **No existe ninguna.** Es un checkbox manual |
| ¿Interviene en productos comunes? | No. El catálogo maestro tiene 0 productos con detracción, y hay un test que lo fija |
| ¿Llega al XML? | **No.** `ConstructorComprobante` nunca llama a `setDetraccion()` |

**Riesgo de activarla por categoría incorrectamente: ninguno hoy**, justamente
porque no hay automatismo. La detracción aplica a determinados servicios y
bienes por encima de un monto, no a la venta minorista de un minimarket.

Lo único que se corrigió es un bug que la afectaba: el formulario de edición
llenaba los checkboxes con `.val()` en vez de `.prop("checked")`, así que
**editar cualquier producto le apagaba la detracción en silencio**. Ver sección
siguiente.

---

## H. Impacto sobre facturación SUNAT

**Ninguno en el XML.** Verificado por tests:

- La línea del comprobante sigue saliendo de `operacion` vía `setTipAfeIgv()`.
- `afecto_isc` y `afecto_ivap` no se leen en `ConstructorComprobante`.
- Los totales `MtoOperGravadas` / `Exoneradas` / `Inafectas` no cambian.
- El IGV sigue cuadrando al céntimo con lo cobrado.

Lo único que cambia es que **un producto sin clasificar ya no puede generar un
comprobante**, cuando antes habría salido como GRAVADO por defecto.

---

## I. Decisiones que requieren validación contable

Ninguna de estas la puede tomar el sistema. Cada una bloquea la importación de
su familia hasta que se resuelva.

| # | Tema | Qué hay que decidir |
|---|---|---|
| 1 | **Arroz** | Qué operación realiza el negocio en la cadena, y por lo tanto si su venta va afecta a IVAP, exonerada de IGV o gravada |
| 2 | **Leche evaporada / UHT** | Confirmar que van GRAVADAS. No se asumió por analogía con la cruda |
| 3 | **Harinas y menestras** | Depende de la partida arancelaria del producto concreto |
| 4 | **Carnes, pollo, pescados** | Fresco del Apéndice I contra procesado, por partida |
| 5 | **Pan** | Tratamiento propio según partida y forma de venta |
| 6 | **Frutos secos** | Naturales (Apéndice I) contra tostados y envasados |
| 7 | **ISC de bebidas azucaradas** | Qué productos concretos superan el umbral de azúcar |
| 8 | **ISC en general** | Si el negocio alguna vez importa o produce; ahí cambia todo el diseño |
| 9 | **ICBPER** | *No auditado en esta fase.* `ConstructorComprobante` manda `setFactorIcbper(0)` fijo. Si el minimarket cobra bolsas plásticas, hay que modelarlo |

El reporte `procesados/pendientes_para_importar.csv` lista producto por producto
qué falta, con su categoría, tipo y las tres columnas tributarias.
