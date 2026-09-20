<?php

namespace App\Ventas;

use App\Estados\EstadoDevolucion;
use App\Estados\EstadoDocumento;
use App\Models\Auditoria;
use App\Models\ProductoAlmacen;
use App\Models\Venta;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de la devolucion por nota de credito.
 *
 * Estaban ausentes: NotaCreditoController creaba la nota y sumaba stock sin
 * comprobar nada, asi que se podia devolver 999 unidades de un producto
 * vendido una vez, devolver algo que no estaba en la venta, o repetir la misma
 * nota indefinidamente y multiplicar el stock.
 *
 * Vive aparte del controller para poder probarla sin pasar por HTTP y para que
 * el recalculo del estado de la venta ocurra en un solo sitio.
 */
class RegistroDevolucion
{
    /**
     * Comprueba que lo que se pretende devolver cabe en lo que queda por
     * devolver de esa venta.
     *
     * @param  list<array{producto_id:int,cantidad:int}>  $lineas
     *
     * @throws ValidationException
     */
    public function validar(Venta $venta, array $lineas): void
    {
        if ($venta->estaAnulada()) {
            throw ValidationException::withMessages([
                'venta_id' => 'La venta está anulada: no admite notas de crédito.',
            ]);
        }

        // Lo vendido por producto, para no fiarse de lo que llegue del cliente.
        $vendido = $venta->detalles()
            ->selectRaw('producto_id, SUM(cantidad) as cantidad')
            ->groupBy('producto_id')
            ->pluck('cantidad', 'producto_id');

        $agrupadas = [];

        foreach ($lineas as $linea) {
            $id = (int) $linea['producto_id'];
            $agrupadas[$id] = ($agrupadas[$id] ?? 0) + (int) $linea['cantidad'];
        }

        foreach ($agrupadas as $productoId => $cantidad) {
            if (! isset($vendido[$productoId])) {
                throw ValidationException::withMessages([
                    'detalles' => "El producto {$productoId} no figura en la venta {$venta->documento}.",
                ]);
            }

            // Las notas anteriores cuentan: si no, dos notas del 100% cada una
            // devolverian el doble de lo vendido.
            $yaDevuelto = $venta->unidadesDevueltasDe($productoId);
            $disponible = (int) $vendido[$productoId] - $yaDevuelto;

            if ($cantidad > $disponible) {
                throw ValidationException::withMessages([
                    'detalles' => sprintf(
                        'Del producto %d se vendieron %d unidades y ya se devolvieron %d: solo quedan %d por devolver.',
                        $productoId,
                        $vendido[$productoId],
                        $yaDevuelto,
                        max(0, $disponible)
                    ),
                ]);
            }
        }
    }

    /**
     * Devuelve el stock de una nota ya creada. Se llama UNA vez, dentro de la
     * misma transaccion que crea la nota.
     *
     * @param  list<array{producto_id:int,cantidad:int,almacen_id:int}>  $lineas
     */
    public function restaurarStock(array $lineas): void
    {
        foreach ($lineas as $linea) {
            $stock = ProductoAlmacen::where('producto_id', $linea['producto_id'])
                ->where('almacen_id', $linea['almacen_id'])
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->stock += (int) $linea['cantidad'];
                $stock->save();
            }
        }
    }

    /**
     * Recalcula estado_devolucion desde las notas vigentes.
     *
     * Es un recalculo completo, no un incremento: asi el estado sigue siendo
     * correcto aunque despues se anule una nota de credito.
     *
     * Importante: NO toca `estado`. Una venta con devolucion total sigue
     * APROBADA, porque ocurrio de verdad. La diferencia con una anulacion es
     * justamente lo que se quiere conservar.
     */
    public function recalcular(Venta $venta): string
    {
        $estado = EstadoDevolucion::desdeUnidades(
            $venta->unidadesVendidas(),
            $venta->unidadesDevueltas()
        );

        if ($venta->estado_devolucion !== $estado) {
            $venta->update(['estado_devolucion' => $estado]);
            $this->auditar($venta, $estado);
        }

        return $estado;
    }

    private function auditar(Venta $venta, string $estado): void
    {
        $accion = match ($estado) {
            EstadoDevolucion::TOTAL => 'DEVOLUCION_TOTAL',
            EstadoDevolucion::PARCIAL => 'DEVOLUCION_PARCIAL',
            default => null,
        };

        if ($accion === null) {
            return;
        }

        Auditoria::registrar(
            $accion,
            'Venta',
            $venta->id,
            sprintf(
                'Devolución %s de la venta %s: S/ %s devueltos de S/ %s',
                $estado === EstadoDevolucion::TOTAL ? 'total' : 'parcial',
                $venta->documento,
                number_format($venta->montoDevuelto(), 2),
                number_format((float) $venta->total, 2)
            )
        );
    }

    /** Notas de credito vigentes de una venta (las anuladas no cuentan). */
    public static function notasVigentes(Venta $venta)
    {
        return $venta->notasCredito()->where('estado', EstadoDocumento::REGISTRADA)->get();
    }
}
