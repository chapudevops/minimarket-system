<!-- Modal Resumen de Caja -->
<div class="modal fade" id="modalResumen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-graph-up"></i> Resumen de Caja
                </h5>
                <input type="hidden" id="resumen_id" value="">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Responsable:</strong> <span id="resumen_responsable"></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Fecha Apertura:</strong> <span id="resumen_fecha_apertura"></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Monto Inicial:</strong> <span id="resumen_monto_inicial"></span>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total de Ventas</h6>
                                <h3 class="text-success" id="resumen_total_ventas">S/ 0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Cantidad de Ventas</h6>
                                <h3 class="text-primary" id="resumen_cantidad_ventas">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h6 class="fw-bold mt-3"><i class="bi bi-cash"></i> Gastos</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Motivo</th>
                                <th>Monto S/</th>
                            </tr>
                        </thead>
                        <tbody id="resumenGastosBody">
                            <tr>
                                <td colspan="2" class="text-center">No hay gastos registrados</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="fw-bold">Total Gastos</td>
                                <td class="fw-bold text-danger" id="resumen_total_gastos">S/ 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card bg-dark text-white">
                            <div class="card-body text-center">
                                <h5>Total</h5>
                                <h2 id="resumen_total">S/ 0.00</h2>
                                <small>El total refiere al monto de ventas más el monto inicial menos los gastos.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" id="btnExportarExcel">
                <i class="bi bi-file-excel"></i> Exportar a Excel
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
        </div>
    </div>
</div>