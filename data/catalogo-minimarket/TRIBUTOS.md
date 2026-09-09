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

Porque un minimarket **no los declara**. El artículo 50 del TUO define como
operaciones gravadas con ISC la venta en el país **a nivel de productor** y la
importación de los bienes de los Apéndices III y IV, más la venta en el país
por el **importador** de los bienes del literal A del Apéndice IV. Un
minimarket que compra a un distribuidor y revende no está en ninguno de esos
supuestos: el ISC le llega incorporado en el costo. Su boleta lleva IGV y nada
más.

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

| Producto | `operacion` | `afecto_isc` | `afecto_ivap` | Norma |
|---|---|---|---|---|
| **Coca-Cola** | `GRAVADO` | *revisar* | `0` | Regla general. El ISC depende del azúcar del producto |
| **Cerveza** | `GRAVADO` | `1` | `0` | Apéndice IV. El caso que el modelo viejo no representaba |
| **Bebida energética** | `GRAVADO` | *revisar* | `0` | Partida 22.02; confirmar alcance |
| **Arroz pilado** | **`INAFECTO`** | `0` | `1` | Ley 28211 art. 7 (mod. Ley 28309) |
| **Leche evaporada** | **`GRAVADO`** | `0` | `0` | El Apéndice I dice *"Sólo: leche cruda entera"* |
| **Leche cruda entera** | `EXONERADO` | `0` | `0` | Apéndice I, partida 0401.20.00.00 |
| **Huevo fresco** | **`GRAVADO`** | `0` | `0` | **No** figura en el Apéndice I; Ley 31452 venció |
| **Fruta fresca** | `EXONERADO` | `0` | `0` | Apéndice I, 0803.00.11.00 / 0810.90.90.00 |
| **Detergente** | `GRAVADO` | `0` | `0` | Regla general |

*revisar* significa que la matriz dice `REVISAR`: el producto entra al catálogo
(su IGV es seguro) pero queda listado en el reporte para que alguien decida.

Los tres primeros muestran el punto: **los tres son `GRAVADO` y se diferencian
en otra columna.** Antes eran indistinguibles.

---

## D. Cómo se clasifica: la matriz

`diccionarios/reglas_tributarias.csv`, con granularidad
**(categoría, subcategoría, producto_tipo)**.

```csv
categoria,subcategoria,producto_tipo,igv,isc,ivap,requiere_revision,fuente_normativa,observacion,verificado_el
```

| Columna | Valores | Significado |
|---|---|---|
| `producto_tipo` | `*` o un tipo | `*` aplica a toda la subcategoría |
| `igv` | GRAVADO / EXONERADO / INAFECTO / PENDIENTE | afectación |
| `isc` / `ivap` | SI / NO / REVISAR | **`REVISAR` nunca se convierte en `SI`** |
| `requiere_revision` | true / false | **habla solo del IGV**: es lo único que bloquea |
| `verificado_el` | AAAA-MM-DD | cuándo se contrastó contra la norma. Las reglas caducan |

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

## E. El arroz: resuelto

**No todo producto que dice "arroz" recibe el mismo tratamiento.** El sistema
distingue tres cosas, y ahora las tres tienen respuesta:

**1. Naturaleza del producto** — lo dice `producto_tipo`, nunca la descripción.

| `producto_tipo` | Partida | `operacion` | `afecto_ivap` |
|---|---|---|---|
| `ARROZ_PILADO` | 1006.30.00.00 | `INAFECTO` | `1` |
| `ARROZ_CON_CASCARA` | 1006.10.90.00 | `EXONERADO` | `0` |
| `ARROZ_INTEGRAL` | 1006.20 o fuera | `PENDIENTE` | *revisar* |
| *(sin precisar)* | — | `PENDIENTE` | `0` |

**2. Operación realizada** — el IVAP grava la primera venta en el país y la
importación. El minimarket es una **venta posterior**, y el artículo 7 de la
Ley 28211 (modificado por el art. 11 de la Ley 28309) alcanza también a esas:

> las operaciones de venta o importación de bienes comprendidos en dicha Ley
> no estarán afectos al IGV, ISC o al IPM
>
> — y con la modificación de la Ley 28309, **también las ventas posteriores
> de dicho bien en el territorio nacional**

**3. Tratamiento almacenado** — la palabra importa: es **INAFECTO**, no
"exonerado". Son los códigos **30** y **20** de la Catálogo 07 y el comprobante
sale distinto. `afecto_ivap = 1` guarda el hecho sobre el producto;
`operacion = INAFECTO` guarda la consecuencia sobre la venta.

**En la práctica:** el minimarket vende arroz pilado **sin cobrar IGV** y sin
declarar IVAP (ese lo pagó el molino en la primera venta). El comprobante lleva
la línea como inafecta.

> **Fuera de alcance:** la declaración mensual del IVAP (PDT/formulario propio)
> no la genera este sistema. Aquí solo se representa la afectación en el
> comprobante.

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

## I. Estado de las decisiones tributarias

Verificado el **2026-09-08** contra el Apéndice I del TUO de la Ley del IGV
(texto actualizado al 24.4.2024, D.S. 058-2024-EF), la Ley 28211 y el art. 50
del TUO. Cada regla de la matriz lleva su fecha en `verificado_el`.

### Resueltas en esta fase (7 de 9)

| # | Tema | Resolución | Norma |
|---|---|---|---|
| 1 | **Arroz** | `INAFECTO` + `afecto_ivap` | Ley 28211 art. 7, mod. Ley 28309 art. 11 |
| 2 | **Leche evaporada / UHT** | `GRAVADO` | Apéndice I 0401.20.00.00: *"Sólo: leche cruda entera"* |
| 3a | **Harinas** | `GRAVADO` | Del trigo solo está exonerado el **de siembra** (1001.10.10.00) |
| 3b | **Menestras** | `EXONERADO` | Apéndice I 0713.10.10.00 / 0713.90.90.00 |
| 4a | **Carnes y pollo** | `GRAVADO` | El Apéndice I lista animales **vivos** (01.01–01.04), no carne beneficiada |
| 4b | **Pescados** | `EXONERADO` | Apéndice I 0301.10.00.00 / 0307.99.90.90 (excepto harina y aceite de pescado) |
| 5 | **Pan** | `GRAVADO` | No figura; la Ley 31452 venció el 31.7.2022 sin prórroga |
| 6 | **Frutos secos** | Parcial | Solo coco, nuez del Brasil y de marañón (0801.11/0801.32). Almendra, pecana y pistacho: `PENDIENTE` |

### Corrección importante

**Los huevos estaban mal clasificados.** La matriz los daba por `EXONERADO`.
No lo están: entre la partida 03.07 (pescados) y la 04.01 (leche cruda) **no
existe la 04.07** en el Apéndice I. Estuvieron exonerados por la Ley 31452
entre el 1.5.2022 y el 31.7.2022, que venció sin prórroga.

De haberse importado el catálogo antes de esta verificación, cada venta de
huevos habría salido en una boleta **sin IGV**. Lo mismo aplicaba a pollo, pan,
azúcar y fideos, que estaban en `PENDIENTE` y por eso no llegaron a facturarse.

### Abiertas (2 de 9, más 3 nuevas acotadas)

| # | Tema | Qué falta |
|---|---|---|
| 7 | **ISC de bebidas azucaradas** | Qué productos concretos superan el umbral de azúcar. Solo afecta la bandera informativa, no el IGV ni el comprobante |
| 8 | **ISC en general** | Solo cambia si el negocio importa o produce. Hoy no aplica: el art. 50 grava a nivel de productor e importador |
| 9 | **ICBPER** | *No auditado.* `ConstructorComprobante` manda `setFactorIcbper(0)` fijo. Si el minimarket cobra bolsas plásticas, hay que modelarlo |
| 10 | **Frutos secos no nominados** | Almendra, pecana, pistacho: verificar partida |
| 11 | **Arroz integral** | ¿Es 1006.20 (dentro del IVAP) o queda fuera? |
| 12 | **Infusiones** | Ya separado: el té (09.02) está exonerado, la manzanilla no. Falta asignar `producto_tipo` a cada producto real |

Las 5 reglas que siguen en `PENDIENTE` bloquean la importación de su familia,
que es lo correcto: `ABARROTES/ARROZ` sin tipo, `ABARROTES/INFUSIONES` sin
tipo, `LACTEOS/LECHE` sin tipo, `SNACKS/FRUTOS SECOS` sin tipo, y
`ABARROTES/ARROZ/ARROZ_INTEGRAL`.

### Por qué esto no reemplaza a un contador

Las reglas están verificadas contra el texto publicado de la norma y cada una
cita su partida, pero **la clasificación arancelaria de un producto concreto es
una decisión que toma el contribuyente**. Lo que cambió es el costo de
revisarlas: antes eran nueve preguntas abiertas, ahora son afirmaciones
citadas que alguien confirma o corrige.

Y caducan. `verificado_el` existe para eso, y `sinVerificarDesdeHace()` las
lista: un test falla si alguna pasa los dos años sin revisión.
