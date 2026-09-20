# Base de datos para tests

El esquema del proyecto vive en `database/mysql-sqlserver/bd.sql`, no en las
migraciones — estas solo cubren `users` y las tablas de tokens. Por eso los
tests **no** pueden usar `RefreshDatabase` ni sqlite en memoria: recrear la base
desde las migraciones dejaria 6 tablas de las 39 que necesita la aplicacion.

En cambio corren contra una base MySQL propia, `minimarketsystem_test`, que se
arma con el mismo dump. Cada test se envuelve en una transaccion
(`DatabaseTransactions`) y se revierte al terminar, asi que la base no se
ensucia entre corridas.

## Crearla

```bash
mysql -u root -p -e "CREATE DATABASE minimarketsystem_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p minimarketsystem_test < database/mysql-sqlserver/bd.sql
mysql -u root -p minimarketsystem_test < database/mysql-sqlserver/combos.sql

# El dump ya trae las tablas de las migraciones base: marcarlas como aplicadas
# para que 'migrate' solo corra las posteriores.
mysql -u root -p minimarketsystem_test -e "
INSERT IGNORE INTO migrations (migration, batch) VALUES
 ('2014_10_12_000000_create_users_table',1),
 ('2014_10_12_100000_create_password_reset_tokens_table',1),
 ('2014_10_12_100000_create_password_resets_table',1),
 ('2019_08_19_000000_create_failed_jobs_table',1),
 ('2019_12_14_000001_create_personal_access_tokens_table',1);"

DB_DATABASE=minimarketsystem_test php artisan migrate --force
```

## Correrlos

```bash
php artisan test                    # todo
php artisan test tests/Unit         # solo los que no tocan la base
php artisan test --filter=Venta     # por nombre
```

`phpunit.xml` ya apunta a `minimarketsystem_test`; no hace falta tocar el `.env`.

## Ojo: el dump esta desfasado respecto de la base real

`bd.sql` ya no refleja el esquema que usa la aplicacion. Su tabla `users` no
tiene `caja_id`, `almacen_id`, `estado`, `ultimo_acceso` ni `ultimo_ip`, que si
existen en desarrollo y de las que dependen el login, los permisos por rol y la
auditoria. Montar la base de test solo desde el dump falla al migrar con
`Unknown column 'estado' in 'users'`.

Hasta que el dump se regenere, la forma fiable de rearmarla es clonar el
esquema real (sin datos) y copiar el historial de migraciones:

```bash
mysqldump -u root -p --no-data --single-transaction minimarketsystem > /tmp/esquema.sql
mysqldump -u root -p --no-create-info --single-transaction minimarketsystem migrations > /tmp/migraciones.sql

mysql -u root -p -e "DROP DATABASE IF EXISTS minimarketsystem_test;
  CREATE DATABASE minimarketsystem_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p minimarketsystem_test < /tmp/esquema.sql
mysql -u root -p minimarketsystem_test < /tmp/migraciones.sql

# Datos de referencia que algunos tests dan por sentados.
DB_DATABASE=minimarketsystem_test php artisan db:seed --class=RoleSeeder --force
DB_DATABASE=minimarketsystem_test php artisan db:seed --class=TaxonomiaSeeder --force
```

Cargar el esquema por un fichero y no por una tuberia
(`mysqldump ... | mysql ...`): las dos puntas compiten por los mismos locks en
el mismo servidor y la carga muere a medias con un deadlock, dejando la base
con parte de las tablas.

## Si la suite empieza a fallar con deadlocks

Sintoma: `BaseVaciaTest` falla con `DeadlockException` en la suite completa pero
pasa cuando se corre sola.

Causa: filas huerfanas en `auditorias`. Si una corrida se corta antes del
rollback, sus registros de bitacora se quedan; `ResetDemo` los borra todos en el
`setUp` de cada test de `BaseVaciaTest`, y esa toma masiva de locks choca con
los tests siguientes. Con miles de filas acumuladas la suite se vuelve
intermitente.

```bash
mysql -u root -p minimarketsystem_test -e "DELETE FROM auditorias;"
```

## Cuando agregues una migracion

Correrla tambien sobre la base de test:

```bash
DB_DATABASE=minimarketsystem_test php artisan migrate --force
```

## Que cubren

- `tests/Unit/Sunat/` — calculo de IGV y redondeo, monto en letras, y los
  mapeos de catalogos de SUNAT. No tocan la base.
- `tests/Feature/VentaEnTerminalTest.php` — el camino que mueve plata:
  descuento de stock, sobreventa, correlativos, unicidad del comprobante y que
  el IGV cuadre al centimo.
- `tests/Feature/PermisosPorRolTest.php` — la matriz de permisos por rol.

`tests/Support/CreaEscenarioDeVenta.php` arma empresa, caja, almacen, serie,
usuario, cliente y producto con stock. Los tests no dependen de los seeders.
