@extends('layouts.master')

@section('title', 'Nueva Nota de Débito')
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        .concepto-row {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .concepto-row:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            background: #fff;
        }
        .remove-concepto {
            cursor: pointer;
            color: #dc3545;
            transition: all 0.2s;
        }
        .remove-concepto:hover {
            color: #a71d2a;
            transform: scale(1.05);
        }
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 8px !important;
            border: 1px solid #ced4da !important;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25) !important;
        }
        .info-cliente-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-cliente-card small {
            color: rgba(255,255,255,0.8);
        }
        .btn-agregar {
            background: linear-gradient(95deg, #059669, #10b981);
            border: none;
            border-radius: 40px;
            padding: 8px 20px;
            font-weight: 600;
        }
        .btn-agregar:hover {
            transform: scale(0.98);
            filter: brightness(1.02);
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
                            <i class="bi bi-file-text-fill"></i> Nueva Nota de Débito
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva nota de débito electrónica</p>
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
                        <label class="form-label fw-bold">
                            <i class="bi bi-person"></i> Cliente <span class="text-danger">*</span>
                        </label>
                        <select id="cliente_id" class="form-control" style="width: 100%;">
                            <option value="">🔍 Buscar cliente por nombre o documento...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" 
                                    data-documento="{{ $cliente->numero_documento }}"
                                    data-nombre="{{ $cliente->nombre_razon_social }}"
                                    data-direccion="{{ $cliente->direccion ?? '-' }}"
                                    data-telefono="{{ $cliente->telefono ?? '-' }}">
                                    {{ $cliente->numero_documento }} - {{ $cliente->nombre_razon_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-receipt"></i> Venta Original (opcional)
                        </label>
                        <select id="venta_id" class="form-control" style="width: 100%;">
                            <option value="">🔍 Buscar venta por documento...</option>
                            @foreach($ventas as $venta)
                                <option value="{{ $venta->id }}" 
                                    data-documento="{{ $venta->documento }}"
                                    data-total="{{ $venta->total }}"
                                    data-cliente="{{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}">
                                    {{ $venta->documento }} - {{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }} - S/ {{ number_format($venta->total, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Serie del Documento</label>
                        <input type="text" id="serie_documento" class="form-control bg-light" readonly>
                        <input type="hidden" id="serie" name="serie">
                        <input type="hidden" id="numero" name="numero">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Fecha de Emisión</label>
                        <input type="date" id="fecha_emision" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Tipo de Nota</label>
                        <select id="tipo_nota" class="form-control">
                            <option value="INTERESES">💰 Intereses moratorios</option>
                            <option value="GASTOS">📄 Gastos administrativos</option>
                            <option value="OTRO">📝 Otros conceptos</option>
                        </select>
                    </div>
                </div>

                <!-- Info del Cliente Seleccionado -->
                <div id="infoClienteContainer" style="display: none;">
                    <div class="info-cliente-card">
                        <div class="row">
                            <div class="col-md-4">
                                <small>DOCUMENTO</small>
                                <p class="mb-0 fw-bold" id="infoDocumento">-</p>
                            </div>
                            <div class="col-md-4">
                                <small>CLIENTE</small>
                                <p class="mb-0 fw-bold" id="infoNombreCliente">-</p>
                            </div>
                            <div class="col-md-4">
                                <small>TELÉFONO</small>
                                <p class="mb-0 fw-bold" id="infoTelefono">-</p>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <small>DIRECCIÓN</small>
                                <p class="mb-0 fw-bold" id="infoDireccion">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Motivo <span class="text-danger">*</span></label>
                        <textarea id="motivo" class="form-control" rows="2" placeholder="Describa el motivo de la nota de débito..."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-2">
                            <input type="checkbox" id="detraccion" class="form-check-input" value="1">
                            <label class="form-check-label fw-bold" for="detraccion">
                                <i class="bi bi-calculator"></i> Aplicar detracción
                            </label>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-receipt"></i> Conceptos a Debitar
                    </h6>
                    <button type="button" class="btn btn-agregar" id="btnAgregarConcepto">
                        <i class="bi bi-plus-circle"></i> Agregar Concepto
                    </button>
                </div>

                <div id="conceptosList" class="mb-3">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> Haga clic en "Agregar Concepto" para comenzar
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td class="fw-bold">OP. Gravadas:</td>
                                        <td class="text-end" id="subtotal">S/ 0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">IGV (18%):</td>
                                        <td class="text-end" id="igv">S/ 0.00</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td class="fw-bold fs-5">Total a Debitar:</td>
                                        <td class="text-end fw-bold fs-5" id="total">S/ 0.00</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label fw-bold">Observaciones</label>
                    <textarea id="observaciones" class="form-control" rows="2" placeholder="Observaciones adicionales..."></textarea>
                </div>

                <div class="text-end">
                    <a href="{{ route('notas-debito.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="button" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar Nota de Débito
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Procesando...
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let conceptos = [];

    // ========== SELECT2 PARA CLIENTE ==========
    $('#cliente_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Buscar cliente por nombre o documento...',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron clientes";
            }
        }
    });

    // ========== SELECT2 PARA VENTA ==========
    $('#venta_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Buscar venta por documento...',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron ventas";
            }
        }
    });

    // Mostrar info del cliente cuando se selecciona
    $('#cliente_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const clienteId = $(this).val();
        
        if (!clienteId) {
            $('#infoClienteContainer').hide();
            return;
        }
        
        const documento = selectedOption.data('documento') || '-';
        const nombre = selectedOption.data('nombre') || '-';
        const direccion = selectedOption.data('direccion') || '-';
        const telefono = selectedOption.data('telefono') || '-';
        
        $('#infoDocumento').text(documento);
        $('#infoNombreCliente').text(nombre);
        $('#infoDireccion').text(direccion);
        $('#infoTelefono').text(telefono);
        $('#infoClienteContainer').show();
    });

    // Cargar serie del documento
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
                    $('#serie_documento').val(response.message || 'Error al cargar serie');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    });
                }
            },
            error: function() {
                $('#serie_documento').val('Error al cargar serie');
            }
        });
    }
    cargarSerie();

    // Renderizar conceptos
    function renderizarConceptos() {
        let html = '';
        
        if (conceptos.length === 0) {
            html = '<div class="alert alert-info text-center"><i class="bi bi-info-circle"></i> Haga clic en "Agregar Concepto" para comenzar</div>';
        } else {
            conceptos.forEach((item, idx) => {
                html += `
                    <div class="concepto-row" data-index="${idx}">
                        <div class="row align-items-center">
                            <div class="col-md-5 mb-2 mb-md-0">
                                <label class="form-label fw-bold small text-muted">Concepto</label>
                                <input type="text" class="form-control concepto-input" data-index="${idx}" value="${escapeHtml(item.concepto)}" placeholder="Descripción del concepto...">
                            </div>
                            <div class="col-md-2 mb-2 mb-md-0">
                                <label class="form-label fw-bold small text-muted">Cantidad</label>
                                <input type="number" class="form-control cantidad-input" data-index="${idx}" value="${item.cantidad}" min="1" step="1">
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label fw-bold small text-muted">Precio Unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" class="form-control precio-input" data-index="${idx}" value="${item.precio_unitario.toFixed(2)}" min="0">
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <label class="form-label small text-muted">&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-concepto w-100" data-index="${idx}">
                                        <i class="bi bi-trash3"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">Total: <strong class="text-success">S/ ${(item.cantidad * item.precio_unitario).toFixed(2)}</strong></small>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#conceptosList').html(html);
        
        // Eventos
        $('.concepto-input').off('change').on('change', function() {
            const idx = $(this).data('index');
            conceptos[idx].concepto = $(this).val();
        });
        
        $('.cantidad-input').off('change').on('change', function() {
            const idx = $(this).data('index');
            conceptos[idx].cantidad = parseInt($(this).val()) || 1;
            renderizarConceptos();
            actualizarTotales();
        });
        
        $('.precio-input').off('change').on('change', function() {
            const idx = $(this).data('index');
            conceptos[idx].precio_unitario = parseFloat($(this).val()) || 0;
            renderizarConceptos();
            actualizarTotales();
        });
        
        $('.remove-concepto').off('click').on('click', function() {
            const idx = $(this).data('index');
            Swal.fire({
                title: '¿Eliminar concepto?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    conceptos.splice(idx, 1);
                    renderizarConceptos();
                    actualizarTotales();
                    Swal.fire('Eliminado', 'Concepto eliminado correctamente', 'success');
                }
            });
        });
    }

    function actualizarTotales() {
        let subtotal = 0;
        conceptos.forEach(item => {
            subtotal += (item.cantidad || 0) * (item.precio_unitario || 0);
        });
        const igv = subtotal * 0.18;
        const total = subtotal + igv;
        
        $('#subtotal').text(`S/ ${subtotal.toFixed(2)}`);
        $('#igv').text(`S/ ${igv.toFixed(2)}`);
        $('#total').text(`S/ ${total.toFixed(2)}`);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Agregar concepto
    $('#btnAgregarConcepto').click(function() {
        conceptos.push({
            concepto: '',
            cantidad: 1,
            precio_unitario: 0
        });
        renderizarConceptos();
        actualizarTotales();
        
        // Auto-focus en el nuevo concepto
        setTimeout(() => {
            $('.concepto-input').last().focus();
        }, 100);
    });

    // Guardar Nota de Débito
    $('#btnGuardar').click(function() {
        const clienteId = $('#cliente_id').val();
        const motivo = $('#motivo').val().trim();
        
        if (!clienteId) {
            Swal.fire({
                icon: 'warning',
                title: 'Cliente no seleccionado',
                text: 'Debe seleccionar un cliente',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        if (!motivo) {
            Swal.fire({
                icon: 'warning',
                title: 'Motivo requerido',
                text: 'Debe ingresar el motivo de la nota de débito',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        if (conceptos.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin conceptos',
                text: 'Debe agregar al menos un concepto',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        const detalles = [];
        let hasError = false;
        
        for (const item of conceptos) {
            if (!item.concepto.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Concepto incompleto',
                    text: 'Todos los conceptos deben tener una descripción',
                    confirmButtonColor: '#3085d6'
                });
                hasError = true;
                return;
            }
            if (item.cantidad <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cantidad inválida',
                    text: 'La cantidad debe ser mayor a 0',
                    confirmButtonColor: '#3085d6'
                });
                hasError = true;
                return;
            }
            if (item.precio_unitario <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Precio inválido',
                    text: 'El precio unitario debe ser mayor a 0',
                    confirmButtonColor: '#3085d6'
                });
                hasError = true;
                return;
            }
            
            detalles.push({
                concepto: item.concepto,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario
            });
        }
        
        if (hasError) return;
        
        const data = {
            cliente_id: parseInt(clienteId),
            venta_id: $('#venta_id').val() ? parseInt($('#venta_id').val()) : null,
            motivo: motivo,
            tipo_nota: $('#tipo_nota').val(),
            detraccion: $('#detraccion').is(':checked'),
            observaciones: $('#observaciones').val(),
            detalles: detalles
        };
        
        $('#btnGuardar').hide();
        $('#btnLoading').show();
        
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
                    Swal.fire({
                        icon: 'success',
                        title: '¡Nota de Débito Registrada!',
                        text: response.message || 'La nota de débito ha sido creada exitosamente',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        window.location.href = '/notas-debito';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al guardar la nota de débito'
                    });
                    $('#btnGuardar').show();
                    $('#btnLoading').hide();
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al guardar la nota de débito';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch(e) {}
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
                $('#btnGuardar').show();
                $('#btnLoading').hide();
            }
        });
    });
});
</script>
@endsection