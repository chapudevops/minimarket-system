$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#cajasTable", "/cajas/data", [
        { data: "correlativo" },
        { data: "descripcion" },
        { data: "created_at" },
        Crud.cruda("acciones"),
    ]);

    $(document).on("click", "#btnNuevaCaja, .btn-nuevo", function () {
        Crud.limpiarErrores("#formCreate");
        $("#formCreate")[0].reset();
        Crud.ventana("#modalCreate").show();
    });

    $("#formCreate").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/cajas", method: "POST", tabla: tabla,
            modal: "#modalCreate", guardar: "#btnGuardarCreate", cargando: "#btnLoadingCreate",
        });
    });

    $("#cajasTable").on("click", ".btn-edit", function () {
        Crud.limpiarErrores("#formEdit");
        $("#formEdit").find('[name="id"]').val($(this).data("id"));
        $("#formEdit").find('[name="descripcion"]').val($(this).data("descripcion"));
        Crud.ventana("#modalEdit").show();
    });

    $("#formEdit").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/cajas/" + $(this).find('[name="id"]').val(), method: "PUT",
            tabla: tabla, modal: "#modalEdit", guardar: "#btnGuardarEdit", cargando: "#btnLoadingEdit",
        });
    });

    $("#cajasTable").on("click", ".btn-delete", function () {
        $("#delete_id").val($(this).data("id"));
        $("#delete-descripcion").text($(this).data("descripcion") || "");
        Crud.ventana("#modalDelete").show();
    });

    $("#btnConfirmDelete").on("click", function () {
        Crud.eliminar({ url: "/cajas/" + $("#delete_id").val(), tabla: tabla });
    });
});
