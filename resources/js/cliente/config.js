$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#clientesTable", "/clientes/data", [
        { data: "correlativo" },
        { data: "tipo_documento" },
        { data: "numero_documento" },
        { data: "nombre_razon_social" },
        { data: "telefono" },
        { data: "departamento" },
        Crud.cruda("estado_badge"),
        { data: "created_at" },
        Crud.cruda("acciones"),
    ]);

    $("#preloader-table").hide();

    const campos = ["tipo_documento", "numero_documento", "nombre_razon_social", "direccion",
                    "telefono", "departamento", "provincia", "distrito", "estado"];

    $(document).on("click", "#btnNuevoCliente, .btn-nuevo", function () {
        Crud.limpiarErrores("#formCreate");
        $("#formCreate")[0].reset();
        Crud.ventana("#modalCreate").show();
    });

    $("#formCreate").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/clientes", method: "POST", tabla: tabla,
            modal: "#modalCreate", guardar: "#btnGuardarCreate", cargando: "#btnLoadingCreate",
        });
    });

    $("#clientesTable").on("click", ".btn-edit", function () {
        const b = $(this);
        Crud.limpiarErrores("#formEdit");
        $("#formEdit").find('[name="id"]').val(b.data("id"));
        campos.forEach(function (c) {
            $("#formEdit").find('[name="' + c + '"]').val(b.data(c));
        });
        Crud.ventana("#modalEdit").show();
    });

    $("#formEdit").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/clientes/" + $(this).find('[name="id"]').val(), method: "PUT",
            tabla: tabla, modal: "#modalEdit", guardar: "#btnGuardarEdit", cargando: "#btnLoadingEdit",
        });
    });

    $("#clientesTable").on("click", ".btn-delete", function () {
        $("#delete_id").val($(this).data("id"));
        $("#delete-nombre, #delete-descripcion").text($(this).data("nombre") || $(this).data("nombre_razon_social") || "");
        Crud.ventana("#modalDelete").show();
    });

    $("#btnConfirmDelete").on("click", function () {
        Crud.eliminar({ url: "/clientes/" + $("#delete_id").val(), tabla: tabla });
    });
});
