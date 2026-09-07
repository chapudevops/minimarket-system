$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#guiasTable", "/guias-remision/data", [
        { data: "correlativo" },
        { data: "documento" },
        { data: "fecha_emision" },
        { data: "cliente" },
        { data: "motivo" },
        Crud.cruda("estado_sunat"),
        Crud.cruda("xml"),
        Crud.cruda("acciones"),
    ]);

    $("#guiasTable").on("click", ".btn-view", function () {
        $.get("/guias-remision/" + $(this).data("id"), function (res) {
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
                    ["Emisión", d.fecha_emision],
                    ["Traslado", d.fecha_traslado],
                    ["Destinatario", d.cliente.nombre],
                    ["Dirección", d.cliente.direccion],
                    ["Motivo", d.motivo_traslado],
                    ["Modalidad", d.modalidad_traslado],
                    ["Peso bruto", d.peso_bruto_total],
                ]) +
                '<div class="table-responsive mt-3"><table class="table table-sm">' +
                "<thead><tr><th>Código</th><th>Descripción</th><th class='text-center'>Cant.</th>" +
                "<th class='text-end'>P. Unit.</th><th class='text-end'>Total</th></tr></thead><tbody>" +
                (filas || Crud.vacio(5, "Sin detalle")) + "</tbody></table></div>"
            );
            Crud.ventana("#modalView").show();
        });
    });

    $("#guiasTable").on("click", ".btn-pdf", function () {
        window.open("/guias-remision/" + $(this).data("id") + "/pdf", "_blank");
    });
});
