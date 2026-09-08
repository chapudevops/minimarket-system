# Catálogo maestro del minimarket

Capa de datos para construir el catálogo de productos a partir de catálogos
públicos de supermercados peruanos (Tottus, Metro, Plaza Vea).

Está fuera de `database/` a propósito: son insumos de trabajo —descargas,
revisiones a medias, reportes— y no forman parte del esquema ni del seeder.

**Nada de esto inserta productos todavía.** El comando de normalización solo
lee y escribe archivos; la importación a la tabla `productos` es un paso
posterior que aún no está implementado.

---

## Estructura

```
data/catalogo-minimarket/
├── raw/                     ← lo copiado de cada fuente, sin interpretar
│   ├── _PLANTILLA.csv         solo cabeceras: el formato, no data
│   ├── tottus.csv
│   ├── metro.csv
│   └── plazavea.csv
├── diccionarios/
│   ├── marcas.csv             grafía oficial + alias no deducibles
│   ├── taxonomia.csv          categorías, códigos y unidad sugerida
│   └── reglas_tributarias.csv matriz IGV / ISC / IVAP  → ver TRIBUTOS.md
├── procesados/              ← reportes, se regeneran (git los ignora)
│   ├── catalogo_normalizado.csv
│   ├── errores_raw.csv
│   ├── duplicados.csv
│   ├── posibles_duplicados.csv
│   ├── marcas_desconocidas.csv
│   ├── pendientes_para_importar.csv
│   └── rechazos_importacion.csv
└── catalogo_maestro.csv     ← el resultado consolidado
```

El código vive en `app/Catalogo/` y el comando en
`app/Console/Commands/CatalogoNormalizar.php`.

El modelo tributario tiene su propio documento: **[TRIBUTOS.md](TRIBUTOS.md)**.

---

## Flujo

```bash
# 1. Revisar el RAW sin escribir nada
php artisan catalogo:normalizar --solo-validar

# 2. Normalizar y armar el maestro
php artisan catalogo:normalizar

# 3. Completar a mano en catalogo_maestro.csv lo que el catálogo no sabe:
#    precio_compra, precio_venta y, si se escanearon, los códigos de barras.

# 4. Ver qué pasaría al importar (por defecto NO escribe)
php artisan catalogo:importar

# 5. Importar de verdad
php artisan catalogo:importar --confirmar
```

El paso 3 es obligatorio: sin precios, `catalogo:importar` rechaza todas las
filas. Es intencional — el precio de góndola de un supermercado no es el costo
del minimarket, así que el pipeline no lo inventa.

**Los datos cargados a mano sobreviven.** Volver a normalizar no borra los
precios, los códigos de barras ni los stocks mínimos que alguien completó: se
reponen desde el maestro anterior, indexados por clave natural.

El comando de normalización:

1. lee todos los `raw/*.csv` (salta los que empiezan con `_`);
2. valida cada fila contra `EsquemaRaw`; lo que no pasa va a
   `procesados/errores_raw.csv` y no entra al maestro;
3. agrupa duplicados **antes** de codificar, para que una repetición no
   consuma un correlativo;
4. normaliza marca, presentación y descripción;
5. asigna el código interno;
6. escribe el maestro y los cuatro reportes.

Es idempotente: volver a correrlo sobre el mismo RAW devuelve exactamente los
mismos códigos.

---

## Importación

`php artisan catalogo:importar` carga `catalogo_maestro.csv` en la tabla
`productos`. **Por defecto simula**: hay que pedir `--confirmar` para que
escriba. Cargar miles de productos sobre la base de una tienda que ya opera no
es algo que deba pasar por teclear mal un comando.

| Opción | Qué hace |
|---|---|
| *(ninguna)* | Simula: informa qué pasaría y no escribe |
| `--confirmar` | Escribe en la base |
| `--archivo=…` | Importa otro CSV en vez del maestro |
| `--actualizar-precios` | Pisa los precios de los productos que ya existen |

La clave natural es `codigo_interno`, así que volver a correrlo actualiza en
vez de duplicar.

### Qué manda el catálogo y qué manda la tienda

Es la decisión central del importador. El escenario a evitar es concreto: la
tienda importa 2.000 productos, pasa tres semanas ajustando precios en el POS,
vuelve a importar un catálogo actualizado y pierde todo ese trabajo.

| Campo | Al crear | Al reimportar |
|---|---|---|
| `descripcion`, `marca`, `presentacion`, `unidad` | del CSV | **se refrescan** |
| `operacion`, `afecto_isc`, `afecto_ivap`, `tipo_producto` | del CSV | **se refrescan** |
| `precio_compra`, `precio_venta` | del CSV | intactos, salvo `--actualizar-precios` |
| `codigo_barras` | del CSV | se completa si faltaba; **nunca se pisa ni se borra** |
| `stock_minimo`, `fecha_vencimiento`, `detraccion`, `foto`, `estado` | del CSV | **nunca se tocan** |

Los tributos se refrescan a propósito: si el contador resolvió que un producto
iba `EXONERADO` y no `GRAVADO`, esa corrección tiene que llegar a la base.

El código de barras solo se completa: el EAN que alguien escaneó del producto
físico vale más que una celda vacía del CSV.

### Qué NO hace

- **No crea stock.** Un producto importado nace sin filas en `producto_almacen`
  y entra al inventario por una compra. Sembrarlo sería inventarlo.
- **No desactiva** productos que desaparecieron del CSV. Un catálogo recortado
  no es una orden de dar de baja media tienda.
- **No deja una entrada de auditoría por producto.** Una importación es *una*
  acción humana, no 4.800: queda una sola entrada con el resumen.

### Qué se rechaza, y por qué no frena al resto

El rechazo es **por fila**: un catálogo de 5.000 referencias con 200 sin
clasificar carga las 4.800 restantes. El detalle va a
`procesados/rechazos_importacion.csv`.

| Motivo | Cuándo |
|---|---|
| Afectación de IGV sin resolver | `operacion = PENDIENTE` |
| Faltan datos | sin precios, sin descripción, sin unidad |
| Código interno repetido en el archivo | la segunda fila pisaría a la primera |
| Código de barras repetido en el archivo | el índice único cortaría la importación a la mitad |
| Código de barras ya registrado | el EAN pertenece a otro producto de la base |

### Volumen

Lee con un generador, así que el consumo de memoria no depende del tamaño del
archivo, y escribe de a 500 filas por transacción — no una sola para todo el
archivo, que mantendría la tabla bloqueada mientras el POS intenta vender. Si
un lote falla, los anteriores quedan importados y basta con volver a correr el
comando.

Medido con 5.000 filas: **8,6 s** la carga inicial de 4.800 productos, **0,9 s**
una reimportación sin cambios, 73 MB de pico.

---

## Esquema RAW

| Columna | Obligatoria | Notas |
|---|---|---|
| `fuente` | sí | `TOTTUS`, `METRO`, `PLAZAVEA` |
| `categoria` | sí | debe existir en `diccionarios/taxonomia.csv` |
| `subcategoria` | sí | idem |
| `producto_tipo` | no | solo donde la subcategoría no alcanza para clasificar tributariamente (`LECHE_EVAPORADA` vs `LECHE_CRUDA_ENTERA`). Vacío es válido |
| `marca` | no | vacío para productos a granel o sin marca |
| `descripcion` | sí | como la publica la fuente, sin reescribir |
| `presentacion` | no | como la publica la fuente |
| `precio_referencia` | no | precio de góndola. **No es el precio de compra** |
| `url_fuente` | sí | `http(s)://…`; es la prueba de dónde salió el dato |
| `fecha_consulta` | sí | `AAAA-MM-DD` |

Reglas de llenado:

- **Copiar, no interpretar.** Si Metro escribe `COCA-COLA ORIGINAL 500 ML`, eso
  va en `descripcion`. Normalizar es trabajo del pipeline.
- **Una fila sin `url_fuente` no es verificable** y se rechaza. Sin URL no hay
  forma de volver a comprobar el dato.
- **Cuidado con la coma decimal.** `1,5L` sin comillas rompe el CSV y corre las
  columnas. Escribir `"1,5L"` o `1.5L`.

---

## Esquema normalizado

Las 16 primeras columnas son exactamente los campos que acepta
`ProductoController::store` (menos `estado`, que siempre entra activo).
`fuente` y `url_fuente` van al final como trazabilidad y **no** se guardan en
`productos`.

| Columna | Se llena ahora | De dónde sale |
|---|---|---|
| `codigo_interno` | sí | generado: `BEB-GAS-000001` |
| `codigo_barras` | **no** | no hay EAN verificable en fuentes públicas |
| `descripcion` | sí | la de la fuente, limpiada |
| `categoria` / `subcategoria` | sí | del RAW, validadas contra la taxonomía |
| `producto_tipo` | opcional | del RAW; decide la regla tributaria fina |
| `unidad` | sí | sugerida por la taxonomía |
| `marca` | sí | grafía oficial del diccionario |
| `presentacion` | sí | normalizada |
| `operacion` | parcial | **solo afectación IGV**; `PENDIENTE` si no es segura |
| `afecto_isc` | sí | ámbito del ISC. Informativo, no va al XML |
| `afecto_ivap` | sí | ámbito del IVAP (Ley 28211). Informativo |
| `requiere_revision_tributaria` | sí | trazabilidad; no se guarda en `productos` |
| `precio_compra` | **no** | lo define la lista del proveedor |
| `precio_venta` | **no** | depende del precio de compra |
| `fecha_vencimiento` | **no** | la trae el lote al recepcionarlo |
| `tipo_producto` | sí | siempre `PRODUCTO` |
| `foto` | **no** | módulo aparte |
| `detraccion` | sí | `0`: no aplica a productos de minimarket |
| `stock_minimo` | **no** | depende de la rotación real de la tienda |

Una celda vacía se lee como `NULL` al importar. Nunca se escribe la cadena
`"NULL"`.

### Por qué tantas columnas vacías

Es la regla central del módulo: **el precio de góndola de un supermercado no es
el costo del minimarket.** Derivar `precio_compra` de `precio_referencia`
produciría un margen inventado que después contamina el reporte de utilidad y
la valorización del inventario. Lo mismo con un EAN inventado (rompe el escáner
del POS), una fecha de vencimiento ficticia (dispara alertas falsas) o un stock
ficticio (descuadra la caja contra el inventario).

### `operacion`: lo que no se puede justificar queda en `PENDIENTE`

`operacion` significa **una sola cosa: la afectación del IGV** (Catálogo 07 de
SUNAT). Los otros tratamientos viven en sus propias columnas, porque no son lo
mismo: una cerveza es GRAVADA de IGV **y además** está en el ámbito del ISC.

`diccionarios/reglas_tributarias.csv` fija los tres ejes por
(categoría, subcategoría, producto_tipo), junto con la norma que los respalda.
Donde no hay una regla sólida el valor es `PENDIENTE`, y eso bloquea la
importación **y** la venta.

El detalle completo —qué cambió, por qué no hay tabla `producto_tributos`, cómo
se representa cada producto, la estrategia del arroz, el alcance del ISC, la
auditoría de detracciones y las decisiones que faltan— está en
**[TRIBUTOS.md](TRIBUTOS.md)**.

---

## Normalización

### Marcas

La equivalencia por mayúsculas, tildes, guiones y espacios es automática:

```
"Coca Cola"  "Coca-Cola"  "COCA COLA"  "coca  cola"  →  Coca-Cola
"Nestle"     "NESTLÉ"                                →  Nestlé
```

`diccionarios/marcas.csv` (columnas `canonica,alias`) hace falta solo para dos
cosas: fijar la grafía que se va a guardar, y declarar alias que **no** son
deducibles del texto:

```csv
Head & Shoulders,H&S
Inca Kola,Inka Kola
Pilsen Callao,Pilsen
```

Una marca que no está en el diccionario no se descarta ni se fuerza: se
capitaliza y se cuenta en `Marcas fuera del diccionario` para que alguien la
agregue.

### Presentaciones

```
"500ML"  "500 ml"  "500 Ml"  "500 mililitros"  "500 cc"  →  500 ml
"1,5L"   "1.5 LT"  "1.50 litros"                         →  1.5 L
"400 GR" "400gramos"                                     →  400 g
"PACK X 6"  "6 PACK"  "pack de 6"                        →  pack x6
"6x355ml"                                                →  x6 355 ml
```

Convenciones: `L` en mayúscula (en minúscula se confunde con el dígito 1), el
resto en minúscula, punto decimal, multiplicador siempre delante. Lo que no se
reconoce se deja intacto — preferimos no tocar antes que adivinar una unidad.

---

## Detección de duplicados

Dos niveles, porque no todos los casos se pueden resolver con texto.

**1. Duplicado probado** → `procesados/duplicados.csv`

Clave: marca + descripción + presentación, las tres normalizadas. La descripción
además pierde la marca y la presentación que ya viajan en sus propias columnas,
que es lo que permite unir el caso más común:

```
METRO   | Coca-Cola | "Coca-Cola Original 500 ml" | 500 ml
TOTTUS  | Coca Cola | "Original"                  | 500ML
                                          ↓
                                    el mismo producto
```

Se conserva la primera fila y las demás quedan listadas con su fuente y su URL.

**2. Sospecha** → `procesados/posibles_duplicados.csv`

Misma marca y misma presentación, pero descripción distinta:

```
TOTTUS   | Coca-Cola | "Gaseosa Coca Cola Original" | 500 ml
PLAZAVEA | Coca-Cola | "Coca-Cola Original"         | 500 ml
```

La palabra "Gaseosa" impide que las claves coincidan. Adivinar qué palabras
sobran es exactamente como se inventan productos, así que **no se fusionan**:
se reportan para revisión manual. Los productos sin marca no generan sospechas
(agruparían medio catálogo).

---

## Códigos internos

Formato `CATEGORIA-SUBCATEGORIA-NNNNNN`, con los códigos de tres letras que fija
`diccionarios/taxonomia.csv`:

```
BEB-GAS-000001    ABA-ARR-000001    GAL-DUL-000001
BEB-AGU-000001    ABA-ACE-000001    LIM-DET-000001
```

La tabla `productos` no tiene columnas `categoria` ni `subcategoria` (ver
`database/mysql-sqlserver/bd.sql`), así que la clasificación viaja dentro del
código. El CSV maestro sí las lleva como columnas, para poder auditar de dónde
salió cada código.

Dos garantías, porque un código interno termina impreso en una etiqueta de
góndola:

1. Un producto ya codificado conserva su código para siempre. El generador se
   siembra con `catalogo_maestro.csv` y con los `codigo_interno` que ya existen
   en la tabla `productos`.
2. Dentro de una corrida, la misma clave natural devuelve el mismo código.

---

## Obtención de los datos

**Todavía no se hace scraping.** Lo que sí se hizo fue revisar las
restricciones publicadas por cada sitio (`robots.txt`, consultado el
2026-09-07):

| Fuente | `robots.txt` | Situación |
|---|---|---|
| tottus.com.pe | `Disallow:` general vacío; bloquea `/checkout*`, `/basket*`, `/myaccount*`, `/orders*` | El catálogo no está bloqueado. Publica sitemaps de productos (`pdp`) y categorías. |
| metro.pe | bloquea `/checkout/*`, `/account/*`, `/login/*`, `/quick-view/*`, `/buscapagina/*`; `Allow: /colecciones/*` | Plataforma VTEX. Las rutas de listado paginado están explícitamente bloqueadas. |
| plazavea.com.pe | bloquea `/checkout` y los sitemaps de marca | Plataforma VTEX. El catálogo no está bloqueado. |

`robots.txt` no es lo único que manda: los **términos de uso** de cada sitio
pueden prohibir la extracción automatizada aunque el archivo la permita, y no
se revisaron todavía.

Orden recomendado antes de cargar nada:

1. Leer los términos de uso de cada sitio.
2. Preguntar si existe un feed o API pública documentada (Metro y Plaza Vea
   corren sobre VTEX, que expone endpoints de catálogo; Tottus publica
   sitemaps).
3. Si se termina descargando: identificarse con un `User-Agent` propio,
   respetar los `Disallow`, ir despacio (segundos entre pedidos, no
   concurrencia) y guardar la URL exacta de cada producto en `url_fuente`.
4. **La fuente más confiable para un minimarket real no es ninguna de las
   tres**: es la lista de precios del proveedor y las facturas de compra. Ahí
   están el costo real y el código de barras verificable, que es justo lo que
   estas tres fuentes no dan.

Mientras tanto, llenar el RAW a mano o por exportación manual también sirve: el
pipeline no distingue de dónde salió el archivo.

---

## Tests

```bash
vendor/bin/phpunit --testsuite Unit --filter Catalogo
```

`tests/Unit/Catalogo/` cubre el normalizador de marcas, el de presentaciones,
el detector de duplicados, el generador de códigos, la matriz tributaria y el
pipeline completo. `tests/Feature/ImportadorCatalogoTest.php` cubre la
importación.

Buena parte de los tests verifica lo que el sistema **no** hace: no inventar
precios, ni EAN, ni vencimientos, ni afectación de IGV — y, del lado del
importador, no destruir el trabajo que la tienda ya hizo.
