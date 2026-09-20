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
        // Estado comercial y estado SUNAT son columnas distintas a proposito:
        // una venta puede estar APROBADA y rechazada por SUNAT a la vez.
        {
            data: "estado_badge",
            orderable: false,
            render: function (dato, tipo, fila) {
                if (tipo !== "display") return fila.estado;
                var extra = "";
                if (fila.devolucion_badge) extra += "<div class='mt-1'>" + fila.devolucion_badge + "</div>";
                if (fila.pago_badge && fila.pago_badge.indexOf("Pagada") === -1) {
                    extra += "<div class='mt-1'>" + fila.pago_badge + "</div>";
                }
                return dato + extra;
            },
        },
        Crud.cruda("xml"),
        Crud.cruda("cdr"),
        Crud.cruda("sunat"),
        { data: "tipo_comprobante" },
        Crud.cruda("acciones"),
    ]);

    $("#ventasTable").on("click", ".btn-view", function () {
        $.get("/ventas/" + $(this).data("id"), function (res) {
            const d = res.data;
            const hayDevolucion = d.devolucion && d.devolucion.tiene;

            const filas = (d.detalles || []).map(function (x) {
                return "<tr><td>" + x.codigo + "</td><td>" + x.producto +
                    '</td><td class="text-center">' + x.cantidad +
                    (hayDevolucion
                        ? '</td><td class="text-center text-danger">' + (x.devueltas || 0)
                        : "") +
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
                    ["Estado comercial", d.estado_badge],
                    ["Estado SUNAT", d.estado_sunat_badge],
                    ["Cobro", d.estado_pago_badge],
                ]) +
                '<div class="table-responsive mt-3"><table class="table table-sm">' +
                "<thead><tr><th>Código</th><th>Producto</th><th class='text-center'>Cant.</th>" +
                (hayDevolucion ? "<th class='text-center'>Devueltas</th>" : "") +
                "<th class='text-end'>P. Unit.</th><th class='text-end'>Total</th></tr></thead><tbody>" +
                (filas || Crud.vacio(hayDevolucion ? 6 : 5, "Sin detalle")) + "</tbody></table></div>" +
                Crud.detalle([
                    ["Subtotal", "S/ " + d.subtotal],
                    ["IGV", "S/ " + d.igv],
                    ["Total", "<strong>S/ " + d.total + "</strong>"],
                    ["Pagado", "S/ " + d.pagado],
                    ["Cambio", "S/ " + d.cambio],
                ]) +
                // Bloque de devolucion: solo cuando existe, con el neto ya
                // calculado por el servidor para no repetir la resta aqui.
                (hayDevolucion
                    ? '<hr><h6 class="fw-bold">Devolución</h6>' +
                      Crud.detalle([
                          ["Estado", d.estado_devolucion_badge],
                          ["Monto original", "S/ " + d.devolucion.monto_original],
                          ["Monto devuelto", '<span class="text-danger">S/ ' + d.devolucion.monto_devuelto + "</span>"],
                          ["Saldo neto", "<strong>S/ " + d.devolucion.monto_neto + "</strong>"],
                          ["Unidades devueltas",
                              d.devolucion.unidades_devueltas + " de " + d.devolucion.unidades_vendidas],
                      ]) +
                      '<div class="table-responsive mt-2"><table class="table table-sm">' +
                      "<thead><tr><th>Nota de crédito</th><th>Fecha</th><th>Motivo</th>" +
                      "<th class='text-end'>Total</th><th>SUNAT</th></tr></thead><tbody>" +
                      d.devolucion.notas.map(function (n) {
                          return "<tr><td>" + n.documento + "</td><td>" + n.fecha +
                              "</td><td>" + n.motivo +
                              '</td><td class="text-end">S/ ' + n.total +
                              "</td><td>" + n.estado_sunat + "</td></tr>";
                      }).join("") +
                      "</tbody></table></div>"
                    : "")
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
