<?php

namespace App\Catalogo;

/**
 * Lo que dejo una corrida del importador.
 *
 * Se lleva la cuenta separada de creados y actualizados porque significan cosas
 * distintas: una segunda corrida que crea productos es un problema (el codigo
 * interno cambio y se duplico el catalogo), una que solo actualiza es lo
 * esperado.
 */
class ResultadoImportacion
{
    public int $leidas = 0;
    public int $creados = 0;
    public int $actualizados = 0;
    public int $sinCambios = 0;
    public int $codigosBarrasAsignados = 0;

    /** @var array<int,array<string,string>> filas que no entraron, con motivo */
    public array $rechazos = [];

    public function rechazar(array $fila, string $motivo): void
    {
        $this->rechazos[] = [
            'codigo_interno' => (string) ($fila['codigo_interno'] ?? ''),
            'descripcion'    => (string) ($fila['descripcion'] ?? ''),
            'operacion'      => (string) ($fila['operacion'] ?? ''),
            'motivo'         => $motivo,
        ];
    }

    public function totalRechazadas(): int
    {
        return count($this->rechazos);
    }

    public function totalEscritas(): int
    {
        return $this->creados + $this->actualizados;
    }

    /** Motivos de rechazo agrupados, para el resumen en pantalla. */
    public function rechazosPorMotivo(): array
    {
        $conteo = [];

        foreach ($this->rechazos as $rechazo) {
            // El motivo lleva el detalle de la fila despues de ':'; para
            // agrupar alcanza con la primera parte.
            $clave = trim(explode(':', $rechazo['motivo'], 2)[0]);
            $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
        }

        arsort($conteo);

        return $conteo;
    }
}
