<?php

namespace App\Catalogo;

use RuntimeException;

/**
 * Codigos internos con la forma PREFIJO-NNNNNN: BEB-GAS-000001.
 *
 * Dos garantias que importan para poder volver a correr la normalizacion sin
 * romper nada:
 *
 *  1. Un producto ya codificado conserva su codigo para siempre. El generador
 *     se siembra con lo que ya existe (catalogo_maestro.csv o la tabla
 *     productos) y desde ahi solo agrega.
 *  2. Dentro de una corrida, la misma clave natural devuelve el mismo codigo.
 *
 * Sin la primera, cada re-normalizacion renumeraria el catalogo y los codigos
 * impresos en las etiquetas dejarian de servir.
 */
class GeneradorCodigoInterno
{
    private const ANCHO = 6;

    /** @var array<string,int> prefijo => ultimo correlativo usado */
    private array $correlativos = [];

    /** @var array<string,string> clave natural => codigo ya asignado */
    private array $asignados = [];

    /** @var array<string,string> codigo => clave natural que lo tiene */
    private array $duenios = [];

    /**
     * @param  array<string,string>  $existentes  clave natural => codigo interno
     */
    public function __construct(array $existentes = [])
    {
        foreach ($existentes as $clave => $codigo) {
            $this->reservar((string) $clave, (string) $codigo);
        }
    }

    /**
     * Registra un codigo ya emitido para que no se vuelva a repartir.
     *
     * La clave puede venir vacia cuando el codigo se leyo de la base y no se
     * sabe a que clave natural corresponde: igual hay que bloquear el numero.
     *
     * @return bool false si el codigo YA es de otro producto. En ese caso no se
     *              remapea: la clave que llego segunda se queda sin codigo
     *              recordado y recibe uno nuevo. Sin este control, dos
     *              productos distintos terminan con el mismo codigo interno y
     *              el importador machaca al primero creyendo que lo actualiza.
     */
    public function reservar(string $clave, string $codigo): bool
    {
        $codigo = strtoupper(trim($codigo));

        if ($codigo === '') {
            return true;
        }

        $duenio = $this->duenios[$codigo] ?? null;
        $enConflicto = $duenio !== null && $duenio !== '' && $clave !== '' && $duenio !== $clave;

        // El numero queda tomado igual: aunque no sepamos de quien es, no puede
        // volver a repartirse.
        $this->anotarCorrelativo($codigo);

        if ($enConflicto) {
            return false;
        }

        // Una reserva con clave le pone nombre a una anterior anonima.
        if ($duenio === null || ($duenio === '' && $clave !== '')) {
            $this->duenios[$codigo] = $clave;
        }

        if ($clave !== '') {
            $this->asignados[$clave] = $codigo;
        }

        return true;
    }

    private function anotarCorrelativo(string $codigo): void
    {
        $this->duenios[$codigo] ??= '';

        if (preg_match('/^(.+)-(\d+)$/', $codigo, $partes)) {
            $prefijo = $partes[1];
            $numero = (int) $partes[2];

            $this->correlativos[$prefijo] = max($this->correlativos[$prefijo] ?? 0, $numero);
        }
    }

    /** Si un codigo ya esta repartido. */
    public function estaTomado(string $codigo): bool
    {
        return isset($this->duenios[strtoupper(trim($codigo))]);
    }

    /**
     * Devuelve el codigo de una clave natural, creandolo si es nueva.
     *
     * @param  string  $prefijo  "BEB-GAS"
     * @param  string  $clave    clave natural del producto (ClaveProducto)
     */
    public function para(string $prefijo, string $clave): string
    {
        if ($clave !== '' && isset($this->asignados[$clave])) {
            return $this->asignados[$clave];
        }

        $prefijo = strtoupper(trim($prefijo));

        if ($prefijo === '') {
            throw new RuntimeException('No se puede generar un codigo interno sin prefijo de categoria.');
        }

        do {
            $this->correlativos[$prefijo] = ($this->correlativos[$prefijo] ?? 0) + 1;
            $codigo = sprintf('%s-%0' . self::ANCHO . 'd', $prefijo, $this->correlativos[$prefijo]);
        } while (isset($this->duenios[$codigo]));

        $this->duenios[$codigo] = $clave;

        if ($clave !== '') {
            $this->asignados[$clave] = $codigo;
        }

        return $codigo;
    }

    /** @return array<string,string> clave natural => codigo */
    public function asignados(): array
    {
        return $this->asignados;
    }
}
