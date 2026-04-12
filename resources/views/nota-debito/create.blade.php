@extends('layouts.master')

@section('title', 'Nueva Nota de Débito')
@section('css')
    <style>
        .concepto-row {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .remove-concepto {
            cursor: pointer;
            color: #dc3545;
        }
    </style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0">
                            <i class="bi bi-plus-circle"></i> Nueva Nota de Débito
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva nota de débito</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('notas-debito.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                        <select id="cliente_id" class="form-control">
                            <option value="">Seleccionar cliente</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->numero_documento }} - {{ $cliente->nombre_razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Venta Original (opcional)</label>
                        <select id="venta_id" class="form-control">
                            <option value="">Seleccionar venta</option>
                            @foreach($ventas as $venta)
                                <option value="{{ $venta->id }}">{{ $venta->documento }} - {{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Serie</label>
                        <input type="text" id="serie_documento" class="form-control" readonly>
                        <input type="hidden" id="serie" name="serie">
                        <input type="hidden" id="numero" name="numero">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipo de Nota</label>
                        <select id="tipo_nota" class="form-control">
                            <option value="INTERESES">Intereses</option>
                            <option value="GASTOS">Gastos</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Motivo <span class="text-danger">*</span></label>
                        <textarea id="motivo" class="form-control" rows="2" placeholder="Motivo de la nota de débito..."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="detraccion" class="form-check-input" value="1">
                            <label class="form-check-label fw-bold" for="detraccion">Detracción</label>
                        </div>
                    </div>
                </div>

                <hr>

                <h6 class="fw-bold mb-3"><i class="bi bi-receipt"></i> Conceptos a Debitar</h6>
                
                <!-- Botón Agregar Concepto -->
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-primary" id="btnAgregarConcepto">
                        <i class="bi bi-plus-circle"></i> Agregar Concepto
                    </button>
                </div>

                <div id="conceptosList" class="mb-3"></div>

                <div class="row">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <table class="table table-bordered">
                            <tr><td class="fw-bold">OP. Gravadas:</td><td class="text-end" id="subtotal">S/ 0.00</td></tr>
                            <tr><td class="fw-bold">IGV (18%):</td><td class="text-end" id="igv">S/ 0.00</td></tr>
                            <tr class="table-primary"><td class="fw-bold">Total:</td><td class="text-end fw-bold" id="total">S/ 0.00</td></tr>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Observaciones</label>
                    <textarea id="observaciones" class="form-control" rows="2"></textarea>
                </div>

                <div class="text-end">
                    <a href="{{ route('notas-debito.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="button" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar Nota de Débito
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let conceptos = [];
    let currentIndex = 0;

    function cargarSerie() {
        $.ajax({
            url: '/notas-debito/serie',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#serie_documento').val(response.documento);
                    $('#serie').val(response.serie);
                    $('#numero').val(response.numero);
                } else {
                    $('#serie_documento').val(response.message);
                }
            }
        });
    }

    cargarSerie();

    function renderizarConceptos() {
        let html = '';
        conceptos.forEach((item, idx) => {
            html += `
                <div class="concepto-row" data-index="${idx}">
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <label class="form-label fw-bold">Concepto</label>
                            <input type="text" class="form-control concepto-input" data-index="${idx}" value="${item.concepto}" placeholder="Descripción del concepto...">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label fw-bold">Cantidad</label>
                            <input type="number" class="form-control cantidad-input" data-index="${idx}" value="${item.cantidad}" min="1">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label fw-bold">Precio Unitario</label>
                            <input type="number" step="0.01" class="form-control precio-input" data-index="${idx}" value="${item.precio_unitario}" min="0">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-sm btn-danger remove-concepto" data-index="${idx}">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('#conceptosList').html(html);
        
        $('.concepto-input').on('change', function() {
            const idx = $(this).data('index');
            conceptos[idx].concepto = $(this).val();
        });
        
        $('.cantidad-input').on('change', function() {
            const idx = $(this).data('index');
            conceptos[idx].cantidad = parseInt($(this).val()) || 0;
            actualizarTotales();
        });
        
        $('.precio-input').on('change', function() {
            const idx = $(this).data('index');
            conceptos[idx].precio_unitario = parseFloat($(this).val()) || 0;
            actualizarTotales();
        });
        
        $('.remove-concepto').on('click', function() {
            const idx = $(this).data('index');
            conceptos.splice(idx, 1);
            renderizarConceptos();
            actualizarTotales();
        });
    }

    function actualizarTotales() {
        let subtotal = 0;
        conceptos.forEach(item => {
            subtotal += item.cantidad * item.precio_unitario;
        });
        const igv = subtotal * 0.18;
        const total = subtotal + igv;
        
        $('#subtotal').text(`S/ ${subtotal.toFixed(2)}`);
        $('#igv').text(`S/ ${igv.toFixed(2)}`);
        $('#total').text(`S/ ${total.toFixed(2)}`);
    }

    $('#btnAgregarConcepto').click(function() {
        conceptos.push({
            concepto: '',
            cantidad: 1,
            precio_unitario: 0
        });
        renderizarConceptos();
        actualizarTotales();
    });

    $('#btnGuardar').click(function() {
        if (conceptos.length === 0) {
            alert('Debe agregar al menos un concepto');
            return;
        }
        
        const detalles = [];
        conceptos.forEach(item => {
            if (item.concepto && item.cantidad > 0 && item.precio_unitario > 0) {
                detalles.push({
                    concepto: item.concepto,
                    cantidad: item.cantidad,
                    precio_unitario: item.precio_unitario
                });
            }
        });
        
        if (detalles.length === 0) {
            alert('Debe completar todos los campos de los conceptos');
            return;
        }
        
        const data = {
            cliente_id: $('#cliente_id').val(),
            venta_id: $('#venta_id').val(),
            motivo: $('#motivo').val(),
            tipo_nota: $('#tipo_nota').val(),
            detraccion: $('#detraccion').is(':checked'),
            observaciones: $('#observaciones').val(),
            detalles: detalles
        };
        
        if (!data.cliente_id) {
            alert('Debe seleccionar un cliente');
            return;
        }
        
        if (!data.motivo) {
            alert('Debe ingresar un motivo');
            return;
        }
        
        $.ajax({
            url: '/notas-debito',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '/notas-debito';
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error al guardar la nota de débito');
            }
        });
    });
});
</script>
@endsection