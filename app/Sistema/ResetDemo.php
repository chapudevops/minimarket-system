<?php

namespace App\Sistema;

use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Borra la data operativa/demo y deja la instalacion como una tienda nueva.
 *
 * --------------------------------------------------------------------------
 * Que se conserva y por que
 * --------------------------------------------------------------------------
 *
 * La linea divisoria no es "tabla con pocas filas" sino "dato que describe
 * como esta montada la tienda" contra "dato que la tienda produjo operando".
 * Lo primero se configura una vez y sobrevive; lo segundo se genera vendiendo
 * y es lo que estamos tirando.
 *
 *   ESTRUCTURAL   empresa, users, roles, user_roles, almacenes, cajas, series,
 *                 conductores y vehiculos (*)
 *   OPERATIVA     todo documento, detalle, movimiento, stock y producto
 *   AUDITORIA     auditorias
 *   SISTEMA       migrations, jobs, failed_jobs, password_resets, tokens
 *
 *   (*) conductores y vehiculos son datos de la flota, no documentos. Se
 *       conservan salvo que se pida --limpiar-flota: los del dump son demo,
 *       pero en una tienda real son configuracion.
 *
 * --------------------------------------------------------------------------
 * Orden de borrado
 * --------------------------------------------------------------------------
 *
 * Sale del grafo real de claves foraneas (64 FK), no de una lista escrita a
 * mano: hijos, despues documentos, despues movimientos, y recien al final
 * productos y los maestros comerciales. Asi nunca hace falta apagar
 * FOREIGN_KEY_CHECKS y la base valida la integridad en cada paso, que es
 * justamente la red de seguridad que uno quiere teniendo un borrado masivo
 * entre manos.
 *
 * --------------------------------------------------------------------------
 * Lo que NO hace
 * --------------------------------------------------------------------------
 *
 *  - No toca la estructura: ni una tabla, indice, FK o migracion.
 *  - No borra el certificado digital ni las credenciales SUNAT. Nunca.
 *  - No deja 10.000 entradas de bitacora: deja UNA con el resumen.
 *  - No corre en produccion. Ni con --confirmar.
 */
class ResetDemo
{
    /**
     * Tablas a vaciar, en orden de borrado seguro.
     *
     * El orden importa y esta verificado contra el grafo de FK: cada tabla
     * solo aparece despues de todas las que la referencian.
     *
     * @var array<string,array<int,string>>
     */
    public const MODULOS = [
        'Detalles de documentos' => [
            'venta_cuotas',
            'venta_detalles',
            'compra_detalles',
            'cotizacion_detalles',
            'nota_venta_detalles',
            'nota_credito_detalles',
            'nota_debito_detalles',
            'guia_remision_detalles',
            'orden_traslado_detalle',
            'combo_detalles',
        ],
        'Documentos' => [
            'notas_credito',
            'notas_debito',
            'notas_venta',
            'cotizaciones',
            'guias_remision',
            'ordenes_traslado',
            'combos',
            'compras',
            'ventas',
        ],
        'Movimientos y caja' => [
            'producto_almacen',
            'gastos',
            'apertura_cajas',
        ],
        'Catalogo' => [
            'productos',
        ],
        'Maestros comerciales' => [
            'clientes',
            'proveedores',
        ],
        'Cola de envios' => [
            'jobs',
            'failed_jobs',
        ],
        'Bitacora' => [
            'auditorias',
        ],
    ];

    /** Tablas que el reset no toca jamas. Se verifica antes y despues. */
    public const INTOCABLES = [
        'empresa', 'users', 'roles', 'user_roles', 'almacenes', 'cajas',
        'series', 'migrations', 'password_reset_tokens', 'password_resets',
        'personal_access_tokens',
    ];

    /** Flota: estructural por defecto, se limpia solo si se pide. */
    public const FLOTA = ['conductores', 'vehiculos'];

    /**
     * Documento del cliente generico.
     *
     * cotizaciones.cliente_id y notas_venta.cliente_id son NOT NULL, asi que
     * sin un cliente generico esos dos modulos quedan inutilizables sobre una
     * base recien limpiada. Se conserva si existe y se recrea si no.
     */
    public const CLIENTE_GENERICO_DOC = '00000000';

    public const CLIENTE_GENERICO_NOMBRE = 'CLIENTE VARIOS';

    /** Carpetas de comprobantes emitidos. Demo, se vacian. */
    public const CARPETAS_COMPROBANTES = [
        'app/comprobantes/xml',
        'app/comprobantes/cdr',
        'app/comprobantes/pdf',
    ];

    /** Carpetas que no se tocan aunque esten dentro de storage. */
    public const CARPETAS_PROTEGIDAS = [
        'app/empresa',
        'app/empresa/certificados',
        'backups',
    ];

    public function __construct(
        private readonly bool $limpiarFlota = false,
        private readonly bool $reiniciarCorrelativos = false,
    ) {}

    /**
     * Cuenta lo que hay hoy, sin tocar nada.
     *
     * @return array<string,array<string,int>>
     */
    public function inventario(): array
    {
        $inventario = [];

        foreach ($this->modulos() as $modulo => $tablas) {
            foreach ($tablas as $tabla) {
                $inventario[$modulo][$tabla] = $this->existe($tabla)
                    ? DB::table($tabla)->count()
                    : 0;
            }
        }

        return $inventario;
    }

    /** Cuantos clientes se borran y cuantos sobreviven. */
    public function planClientes(): array
    {
        if (! $this->existe('clientes')) {
            return ['conservados' => 0, 'eliminados' => 0, 'generico' => null];
        }

        $generico = DB::table('clientes')
            ->where('numero_documento', self::CLIENTE_GENERICO_DOC)
            ->first();

        return [
            'conservados' => $generico ? 1 : 0,
            'eliminados'  => DB::table('clientes')->count() - ($generico ? 1 : 0),
            'generico'    => $generico?->nombre_razon_social,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function planCorrelativos(): array
    {
        if (! $this->existe('series')) {
            return [];
        }

        return DB::table('series')->orderBy('id')->get()
            ->map(fn ($s) => [
                'serie'   => $s->serie,
                'tipo'    => $s->tipo_comprobante,
                'antes'   => (int) $s->correlativo,
                'despues' => $this->reiniciarCorrelativos ? 0 : (int) $s->correlativo,
            ])
            ->all();
    }

    /** Archivos de comprobantes que se borrarian. */
    public function planArchivos(): array
    {
        $plan = [];

        foreach (self::CARPETAS_COMPROBANTES as $carpeta) {
            $ruta = storage_path($carpeta);
            $plan[$carpeta] = is_dir($ruta) ? count(File::files($ruta)) : 0;
        }

        return $plan;
    }

    /**
     * Vacia las tablas dentro de una transaccion.
     *
     * El filesystem queda fuera a proposito: no participa de la transaccion y
     * un borrado de archivos no se puede deshacer con un rollback. Primero se
     * confirma la base, despues se tocan los archivos, y si eso falla la base
     * ya quedo consistente.
     *
     * @return array<string,int> tabla => filas eliminadas
     */
    public function ejecutar(): array
    {
        $eliminados = [];

        DB::transaction(function () use (&$eliminados) {
            $genericoId = $this->protegerClienteGenerico();

            foreach ($this->modulos() as $tablas) {
                foreach ($tablas as $tabla) {
                    if (! $this->existe($tabla)) {
                        continue;
                    }

                    $query = DB::table($tabla);

                    // El cliente generico es el unico registro que sobrevive a
                    // su tabla: cotizaciones y notas de venta lo necesitan.
                    if ($tabla === 'clientes' && $genericoId !== null) {
                        $query->where('id', '!=', $genericoId);
                    }

                    $eliminados[$tabla] = $query->delete();
                }
            }

            if ($this->reiniciarCorrelativos && $this->existe('series')) {
                // correlativo guarda el ULTIMO numero emitido y el siguiente
                // sale de correlativo + 1. Por eso el valor inicial es 0 y no
                // 1: con 1 la primera boleta seria B001-00000002 y el numero
                // 1 quedaria sin usar, que ante SUNAT es un salto.
                DB::table('series')->update(['correlativo' => 0]);
            }

            $this->recrearClienteGenerico($genericoId);
        });

        return $eliminados;
    }

    /**
     * Borra los comprobantes emitidos. Fuera de la transaccion, a proposito.
     *
     * @return array{eliminados:int,fallos:array<int,string>}
     */
    public function limpiarArchivos(): array
    {
        $eliminados = 0;
        $fallos = [];

        foreach (self::CARPETAS_COMPROBANTES as $carpeta) {
            $ruta = storage_path($carpeta);

            if (! is_dir($ruta) || $this->estaProtegida($carpeta)) {
                continue;
            }

            foreach (File::files($ruta) as $archivo) {
                try {
                    File::delete($archivo->getPathname())
                        ? $eliminados++
                        : $fallos[] = $archivo->getPathname();
                } catch (\Throwable $e) {
                    $fallos[] = $archivo->getPathname().': '.$e->getMessage();
                }
            }
        }

        return ['eliminados' => $eliminados, 'fallos' => $fallos];
    }

    /**
     * Deja UNA entrada en la bitacora con el resumen.
     *
     * Se llama despues de vaciar auditorias, no antes: si no, el propio reset
     * borraria su registro.
     */
    public function registrarEnBitacora(array $eliminados, array $archivos): Auditoria
    {
        $total = array_sum($eliminados);

        return Auditoria::registrar(
            'RESET',
            'Sistema',
            null,
            "Reset de datos demo: {$total} registros eliminados de ".count($eliminados).' tablas',
            [
                'tablas'              => array_filter($eliminados),
                'total_registros'     => $total,
                'archivos_eliminados' => $archivos['eliminados'],
                'entorno'             => app()->environment(),
                'correlativos'        => $this->reiniciarCorrelativos ? 'reiniciados a 0' : 'conservados',
            ],
        );
    }

    /**
     * Verifica que lo estructural siga intacto.
     *
     * @return array<string,int>
     */
    public function verificarIntocables(): array
    {
        $estado = [];

        foreach (array_merge(self::INTOCABLES, $this->limpiarFlota ? [] : self::FLOTA) as $tabla) {
            if ($this->existe($tabla)) {
                $estado[$tabla] = DB::table($tabla)->count();
            }
        }

        return $estado;
    }

    /** @return array<string,array<int,string>> */
    private function modulos(): array
    {
        $modulos = self::MODULOS;

        if ($this->limpiarFlota) {
            $modulos['Flota'] = self::FLOTA;
        }

        return $modulos;
    }

    private function protegerClienteGenerico(): ?int
    {
        if (! $this->existe('clientes')) {
            return null;
        }

        return DB::table('clientes')
            ->where('numero_documento', self::CLIENTE_GENERICO_DOC)
            ->value('id');
    }

    /**
     * Si no habia cliente generico, lo crea.
     *
     * No es un dato demo que se cuela de vuelta: es infraestructura del POS.
     * Sin el, cotizaciones y notas de venta no pueden guardarse.
     */
    private function recrearClienteGenerico(?int $existente): void
    {
        if ($existente !== null || ! $this->existe('clientes')) {
            return;
        }

        DB::table('clientes')->insert([
            'tipo_documento'      => 'DNI',
            'numero_documento'    => self::CLIENTE_GENERICO_DOC,
            'nombre_razon_social' => self::CLIENTE_GENERICO_NOMBRE,
            'direccion'           => 'Venta al publico',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    private function estaProtegida(string $carpeta): bool
    {
        foreach (self::CARPETAS_PROTEGIDAS as $protegida) {
            if (str_starts_with($carpeta, $protegida)) {
                return true;
            }
        }

        return false;
    }

    private function existe(string $tabla): bool
    {
        return DB::getSchemaBuilder()->hasTable($tabla);
    }
}
