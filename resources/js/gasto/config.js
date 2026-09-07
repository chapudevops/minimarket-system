$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#gastosTable", "/gastos/data", [
        { data: "correlativo" },
        { data: "fecha_emision" },
        { data: "cuenta" },
        { data: "motivo" },
        { data: "monto", className: "text-end" },
        { data: "usuario" },
        Crud.cruda("acciones"),
    ]);

    $("#btnNuevoGasto").on("click", function () {
        Crud.limpiarErrores("#formCreate");
        $("#formCreate")[0].reset();
        $("#formCreate").find('[name="fecha_emision"]').val(new Date().toISOString().slice(0, 10));
        Crud.ventana("#modalCreate").show();
    });

    $("#formCreate").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/gastos", method: "POST", tabla: tabla,
            modal: "#modalCreate", guardar: "#btnGuardarCreate", cargando: "#btnLoadingCreate",
        });
    });

    $("#gastosTable").on("click", ".btn-delete", function () {
        $("#delete_id").val($(this).data("id"));
        $("#delete-motivo, #delete-descripcion").text($(this).data("motivo") || "");
        Crud.ventana("#modalDelete").show();
    });

    $("#btnConfirmDelete").on("click", function () {
        Crud.eliminar({ url: "/gastos/" + $("#delete_id").val(), tabla: tabla });
    });
});
