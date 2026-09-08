<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;

/**
 * Deja rastro automatico de altas, cambios y bajas del modelo.
 *
 * El modelo puede definir:
 *   protected array $auditarSolo = ['precio_venta', ...];  // campos a vigilar
 *   public function etiquetaAuditoria(): string            // como nombrarlo
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($modelo) {
            Auditoria::registrar(
                'CREO',
                class_basename($modelo),
                $modelo->getKey(),
                'Creó ' . $modelo->descripcionAuditoria()
            );
        });

        static::updated(function ($modelo) {
            $cambios = $modelo->cambiosAuditables();

            // Sin cambios visibles no se registra nada: una bitacora llena de
            // entradas vacias es una bitacora que nadie lee.
            if (empty($cambios)) {
                return;
            }

            Auditoria::registrar(
                'ACTUALIZO',
                class_basename($modelo),
                $modelo->getKey(),
                'Modificó ' . $modelo->descripcionAuditoria(),
                $cambios
            );
        });

        static::deleted(function ($modelo) {
            Auditoria::registrar(
                'ELIMINO',
                class_basename($modelo),
                $modelo->getKey(),
                'Eliminó ' . $modelo->descripcionAuditoria()
            );
        });
    }

    /**
     * Campos que cambiaron, con su valor anterior y el nuevo.
     */
    protected function cambiosAuditables(): array
    {
        $vigilados = property_exists($this, 'auditarSolo') ? $this->auditarSolo : null;
        $cambios = [];

        foreach ($this->getChanges() as $campo => $nuevo) {
            if (in_array($campo, Auditoria::NUNCA_REGISTRAR, true)) {
                continue;
            }

            if ($vigilados !== null && ! in_array($campo, $vigilados, true)) {
                continue;
            }

            // getAttribute aplica los casts del modelo: sin esto un decimal
            // quedaba "23.90" antes y 77.5 despues, y la pantalla lo mostraba
            // con distinta cantidad de decimales.
            $cambios[$campo] = [
                'antes'   => $this->normalizarValor($this->getOriginal($campo)),
                'despues' => $this->normalizarValor($this->getAttribute($campo)),
            ];
        }

        return $cambios;
    }

    /** Todo se guarda como texto o null, para que la comparacion sea pareja. */
    private function normalizarValor(mixed $valor): string|null
    {
        if ($valor === null) {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return is_scalar($valor) ? (string) $valor : json_encode($valor);
    }

    /** Como se nombra este registro en la bitacora. */
    public function descripcionAuditoria(): string
    {
        $etiqueta = method_exists($this, 'etiquetaAuditoria')
            ? $this->etiquetaAuditoria()
            : ('#' . $this->getKey());

        return strtolower(class_basename($this)) . ' ' . $etiqueta;
    }
}
