<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index()
    {
        return view('auditoria.index', [
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'entidades' => Auditoria::distinct()->orderBy('entidad')->pluck('entidad'),
            'acciones' => Auditoria::distinct()->orderBy('accion')->pluck('accion'),
        ]);
    }

    public function getData(Request $request)
    {
        $registros = Auditoria::query()
            ->when($request->usuario_id, fn ($q, $v) => $q->where('usuario_id', $v))
            ->when($request->entidad, fn ($q, $v) => $q->where('entidad', $v))
            ->when($request->accion, fn ($q, $v) => $q->where('accion', $v))
            ->when($request->desde, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->hasta, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('id')
            // La bitacora crece sin techo: nunca se devuelve entera.
            ->limit(500)
            ->get();

        return response()->json([
            'data' => $registros->map(fn ($r) => [
                'fecha' => $r->created_at?->format('d/m/Y H:i:s'),
                'usuario' => $r->usuario_nombre,
                'accion' => $r->accion_badge,
                'entidad' => $r->entidad,
                'descripcion' => e($r->descripcion),
                'cambios' => $this->formatearCambios($r->cambios),
                'ip' => $r->ip ?? '-',
            ]),
        ]);
    }

    /** Los cambios se muestran como "campo: antes → despues". */
    private function formatearCambios(?array $cambios): string
    {
        if (! $cambios) {
            return '<span class="text-muted">—</span>';
        }

        $filas = [];

        foreach ($cambios as $campo => $valores) {
            $antes = $this->legible($valores['antes'] ?? null);
            $despues = $this->legible($valores['despues'] ?? null);

            $filas[] = '<div class="small"><strong>' . e($campo) . ':</strong> '
                . '<span class="text-danger">' . $antes . '</span> → '
                . '<span class="text-success">' . $despues . '</span></div>';
        }

        return implode('', $filas);
    }

    private function legible(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '<em>vacío</em>';
        }

        if (is_bool($valor)) {
            return $valor ? 'sí' : 'no';
        }

        return e(mb_strimwidth((string) $valor, 0, 60, '…'));
    }
}
