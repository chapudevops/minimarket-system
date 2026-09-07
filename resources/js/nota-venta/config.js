$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#notasTable", "/notas-venta/data", [
        { data: "correlativo" },
        { data: "documento" },
        { data: "fecha_emision" },
        { data: "ruc_dni" },
        { data: "cliente" },
        { data: "total", className: "text-end" },
        Crud.cruda("estado_badge"),
        Crud.cruda("acciones"),
    ]);

    $("#notasTable").on("click", ".btn-view", function () {
        $.get("/notas-venta/" + $(this).data("id"), function (res) {
            const d = res.data;
            const filas = (d.detalles || []).map(function (x) {
                return "<tr><td>" + (x.codigo || "-") + "</td><td>" + (x.producto || x.concepto || "-") +
                    '</td><td class="text-center">' + x.cantidad +
                    '</td><td class="text-end">' + x.precio_unitario +
                    '</td><td class="text-end">' + x.total + "</td></tr>";
            }).join("");

            $("#notaDetails, #guiaDetails").html(
                Crud.detalle([
                    ["Documento", d.documento],
                    ["Fecha", d.fecha_emision],
                    ["Cliente", d.cliente.nombre],
                    ["Dirección", d.cliente.direccion],
                    ["Subtotal", "S/ " + d.subtotal],
                    ["IGV", "S/ " + d.igv],
                    ["Total", "<strong>S/ " + d.total + "</strong>"],
                ]) +
                '<div class="table-responsive mt-3"><table class="table table-sm">' +
                "<thead><tr><th>Código</th><th>Descripción</th><th class='text-center'>Cant.</th>" +
                "<th class='text-end'>P. Unit.</th><th class='text-end'>Total</th></tr></thead><tbody>" +
                (filas || Crud.vacio(5, "Sin detalle")) + "</tbody></table></div>"
            );
            Crud.ventana("#modalView").show();
        });
    });

    $("#notasTable").on("click", ".btn-pdf", function () {
        window.open("/notas-venta/" + $(this).data("id") + "/pdf", "_blank");
    });
});
