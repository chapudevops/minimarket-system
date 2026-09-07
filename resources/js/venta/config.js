$(function () {
    "use strict";
    Crud.init();

    let ventaActual = null;

    const tabla = Crud.tabla("#ventasTable", "/ventas/data", [
        { data: "documento" },
        { data: "fecha_emision" },
        { data: "ruc_dni" },
        { data: "cliente" },
        { data: "total", className: "text-end" },
        Crud.cruda("xml"),
        Crud.cruda("cdr"),
        Crud.cruda("sunat"),
        { data: "tipo_comprobante" },
        Crud.cruda("acciones"),
    ]);

    $("#ventasTable").on("click", ".btn-view", function () {
        $.get("/ventas/" + $(this).data("id"), function (res) {
            const d = res.data;
            const filas = (d.detalles || []).map(function (x) {
                return "<tr><td>" + x.codigo + "</td><td>" + x.producto +
                    '</td><td class="text-center">' + x.cantidad +
                    '</td><td class="text-end">' + x.precio_unitario +
                    '</td><td class="text-end">' + x.total + "</td></tr>";
            }).join("");

            ventaActual = d.id;
            // El boton vive en el pie del modal y arranca oculto: solo tiene
            // sentido sobre ventas completadas.
            $("#btnAnularVenta").toggle(d.estado === "COMPLETADA");

            $("#ventaDetails").html(
                Crud.detalle([
                    ["Documento", d.documento],
                    ["Fecha", d.fecha_emision],
                    ["Cliente", d.cliente.nombre + " (" + d.cliente.documento + ")"],
                    ["Dirección", d.cliente.direccion],
                    ["Tipo de venta", d.tipo_venta],
                    ["Forma de pago", d.forma_pago],
                    ["Caja", d.caja],
                    ["Vendedor", d.usuario],
                    ["Estado", d.estado_badge],
                ]) +
                '<div class="table-responsive mt-3"><table class="table table-sm">' +
                "<thead><tr><th>Código</th><th>Producto</th><th class='text-center'>Cant.</th>" +
                "<th class='text-end'>P. Unit.</th><th class='text-end'>Total</th></tr></thead><tbody>" +
                (filas || Crud.vacio(5, "Sin detalle")) + "</tbody></table></div>" +
                Crud.detalle([
                    ["Subtotal", "S/ " + d.subtotal],
                    ["IGV", "S/ " + d.igv],
                    ["Total", "<strong>S/ " + d.total + "</strong>"],
                    ["Pagado", "S/ " + d.pagado],
                    ["Cambio", "S/ " + d.cambio],
                ])
            );
            Crud.ventana("#modalView").show();
        });
    });

    $("#ventasTable").on("click", ".btn-pdf", function () {
        window.open("/ventas/" + $(this).data("id") + "/pdf", "_blank");
    });

    $("#ventasTable").on("click", ".btn-ticket", function () {
        window.open("/ventas/" + $(this).data("id") + "/ticket", "_blank");
    });

    $("#btnAnularVenta").on("click", function () {
        if (!ventaActual || !confirm("Se anulara la venta y se devolvera el stock. Continuar?")) return;

        $.post("/ventas/" + ventaActual + "/anular")
            .done(function (res) {
                Crud.cerrar("#modalView");
                Crud.aviso("success", res.message);
                tabla.ajax.reload(null, false);
            })
            .fail(function (xhr) {
                Crud.aviso("danger", (xhr.responseJSON && xhr.responseJSON.message) || "No se pudo anular la venta.");
            });
    });
});
