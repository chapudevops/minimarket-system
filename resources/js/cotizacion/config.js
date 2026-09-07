$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#cotizacionesTable", "/cotizaciones/data", [
        { data: "correlativo" },
        { data: "documento" },
        { data: "fecha_emision" },
        { data: "fecha_validez" },
        { data: "cliente" },
        { data: "total", className: "text-end" },
        Crud.cruda("estado_badge"),
        Crud.cruda("acciones"),
    ]);

    $("#cotizacionesTable").on("click", ".btn-view", function () {
        $.get("/cotizaciones/" + $(this).data("id"), function (res) {
            const d = res.data;
            const filas = (d.detalles || []).map(function (x) {
                return "<tr><td>" + x.codigo + "</td><td>" + x.producto +
                    '</td><td class="text-center">' + x.cantidad +
                    '</td><td class="text-end">' + x.precio_unitario +
                    '</td><td class="text-end">' + x.total + "</td></tr>";
            }).join("");

            $("#cotizacionDetails").html(
                Crud.detalle([
                    ["Documento", d.documento],
                    ["Emisión", d.fecha_emision],
                    ["Validez", d.fecha_validez],
                    ["Cliente", d.cliente.nombre],
                    ["Dirección", d.cliente.direccion],
                    ["Estado", d.estado_badge || d.estado],
                ]) +
                '<div class="table-responsive mt-3"><table class="table table-sm">' +
                "<thead><tr><th>Código</th><th>Producto</th><th class='text-center'>Cant.</th>" +
                "<th class='text-end'>P. Unit.</th><th class='text-end'>Total</th></tr></thead><tbody>" +
                (filas || Crud.vacio(5, "Sin detalle")) + "</tbody></table></div>" +
                Crud.detalle([
                    ["Subtotal", "S/ " + d.subtotal],
                    ["IGV", "S/ " + d.igv],
                    ["Total", "<strong>S/ " + d.total + "</strong>"],
                ])
            );
            Crud.ventana("#modalView").show();
        });
    });

    $("#cotizacionesTable").on("click", ".btn-pdf", function () {
        window.open("/cotizaciones/" + $(this).data("id") + "/pdf", "_blank");
    });

    function accion(id, ruta, exito) {
        $.post("/cotizaciones/" + id + "/" + ruta)
            .done(function (res) {
                Crud.aviso("success", res.message || exito);
                tabla.ajax.reload(null, false);
            })
            .fail(function (xhr) {
                Crud.aviso("danger", (xhr.responseJSON && xhr.responseJSON.message) || "No se pudo completar la acción.");
            });
    }

    $("#cotizacionesTable").on("click", ".btn-aprobar", function () {
        accion($(this).data("id"), "aprobar", "Cotización aprobada");
    });

    $("#cotizacionesTable").on("click", ".btn-rechazar", function () {
        accion($(this).data("id"), "rechazar", "Cotización rechazada");
    });
});
