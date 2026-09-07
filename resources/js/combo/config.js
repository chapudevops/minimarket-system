$(function () {
    "use strict";
    Crud.init();

    // Alta y edicion viven en /combos/create y /combos/{id}/edit, no en modal.
    const tabla = Crud.tabla("#combosTable", "/combos/data", [
        { data: "correlativo" },
        Crud.cruda("foto"),
        { data: "nombre" },
        { data: "descripcion" },
        { data: "precio_combo", className: "text-end" },
        { data: "precio_regular", className: "text-end" },
        { data: "ahorro", className: "text-end" },
        { data: "descuento", className: "text-end" },
        { data: "productos_count", className: "text-center" },
        Crud.cruda("estado_texto"),
        Crud.cruda("acciones"),
    ]);

    $("#combosTable").on("click", ".btn-view", function () {
        $.get("/combos/" + $(this).data("id"), function (res) {
            const d = res.data;
            const items = (d.productos || d.detalles || []).map(function (p) {
                return "<tr><td>" + (p.codigo_interno || "") + "</td><td>" + (p.descripcion || "") +
                    '</td><td class="text-center">' + (p.cantidad || 0) +
                    '</td><td class="text-end">' + Crud.soles(p.precio_unitario) +
                    '</td><td class="text-end">' + Crud.soles(p.subtotal) + "</td></tr>";
            }).join("");

            $("#comboDetails, #detalleBody").html(
                Crud.detalle([
                    ["Nombre", d.nombre],
                    ["Descripción", d.descripcion],
                    ["Precio combo", Crud.soles(d.precio_combo)],
                    ["Precio regular", Crud.soles(d.precio_regular)],
                ]) +
                '<div class="table-responsive mt-3"><table class="table table-sm">' +
                "<thead><tr><th>Código</th><th>Producto</th><th class='text-center'>Cant.</th>" +
                "<th class='text-end'>P. Unit.</th><th class='text-end'>Subtotal</th></tr></thead><tbody>" +
                (items || Crud.vacio(5, "Sin productos")) + "</tbody></table></div>"
            );
            Crud.ventana("#modalView").show();
        });
    });

    $("#combosTable").on("click", ".btn-delete", function () {
        $("#delete_id").val($(this).data("id"));
        $("#delete-nombre, #delete-descripcion").text($(this).data("nombre") || "");
        Crud.ventana("#modalDelete").show();
    });

    $("#btnConfirmDelete").on("click", function () {
        Crud.eliminar({ url: "/combos/" + $("#delete_id").val(), tabla: tabla });
    });
});
