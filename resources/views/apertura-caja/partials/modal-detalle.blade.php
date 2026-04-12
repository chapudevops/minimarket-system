<!-- Modal Detalle de Caja -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-eye"></i> Detalle de Caja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Responsable:</strong> <span id="detalle_responsable"></span>
                    </div>
                    <div class="col-md-3">
                        <strong>Fecha Apertura:</strong> <span id="detalle_fecha_apertura"></span>
                    </div>
                    <div class="col-md-3">
                        <strong>Monto Inicial:</strong> <span id="detalle_monto_inicial"></span>
                    </div>
                    <div class="col-md-3">
                        <strong>Estado:</strong> <span id="detalle_estado"></span>
                    </div>
                </div>
                <hr>
                <h6 class="fw-bold mb-3"><i class="bi bi-receipt"></i> Ventas realizadas</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th>Número</th>
                                <th>Monto S/</th>
                            </tr>
                        </thead>
                        <tbody id="detalleBody">
                            <tr>
                                <td colspan="7" class="text-center">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>