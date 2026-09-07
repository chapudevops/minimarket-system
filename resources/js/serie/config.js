$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#seriesTable", "/series/data", [
        { data: "correlativo" },
        { data: "serie" },
        { data: "numero_correlativo" },
        { data: "tipo_comprobante" },
        { data: "caja" },
        Crud.cruda("acciones"),
    ]);

    // Las cajas alimentan los dos selects del modulo.
    $.get("/series/form-data", function (res) {
        const opciones = ['<option value="">Selecciona una caja</option>'].concat(
            (res.cajas || []).map(function (c) {
                return '<option value="' + c.id + '">' + c.descripcion + "</option>";
            })
        ).join("");
        $('#formCreate [name="caja_id"], #formEdit [name="caja_id"]').html(opciones);
    });

    $("#btnNuevaSerie").on("click", function () {
        Crud.limpiarErrores("#formCreate");
        $("#formCreate")[0].reset();
        Crud.ventana("#modalCreate").show();
    });

    $("#formCreate").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/series", method: "POST", tabla: tabla,
            modal: "#modalCreate", guardar: "#btnGuardarCreate", cargando: "#btnLoadingCreate",
        });
    });

    $("#seriesTable").on("click", ".btn-edit", function () {
        const b = $(this);
        Crud.limpiarErrores("#formEdit");
        $("#formEdit").find('[name="id"]').val(b.data("id"));
        $("#formEdit").find('[name="serie"]').val(b.data("serie"));
        $("#formEdit").find('[name="correlativo"]').val(b.data("correlativo"));
        $("#formEdit").find('[name="tipo_comprobante"]').val(b.data("tipo_comprobante"));
        $("#formEdit").find('[name="caja_id"]').val(b.data("caja_id"));
        Crud.ventana("#modalEdit").show();
    });

    $("#formEdit").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/series/" + $(this).find('[name="id"]').val(), method: "PUT",
            tabla: tabla, modal: "#modalEdit", guardar: "#btnGuardarEdit", cargando: "#btnLoadingEdit",
        });
    });

    $("#seriesTable").on("click", ".btn-delete", function () {
        $("#delete_id").val($(this).data("id"));
        $("#delete-serie, #delete-descripcion").text($(this).data("serie") || "");
        Crud.ventana("#modalDelete").show();
    });

    $("#btnConfirmDelete").on("click", function () {
        Crud.eliminar({ url: "/series/" + $("#delete_id").val(), tabla: tabla });
    });
});
