$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#almacenesTable", "/almacenes/data", [
        { data: "correlativo" },
        { data: "descripcion" },
        { data: "establecimiento" },
        { data: "created_at" },
        Crud.cruda("acciones"),
    ]);

    $(document).on("click", "#btnNuevoAlmacen, .btn-nuevo", function () {
        Crud.limpiarErrores("#formCreate");
        $("#formCreate")[0].reset();
        Crud.ventana("#modalCreate").show();
    });

    $("#formCreate").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/almacenes", method: "POST", tabla: tabla,
            modal: "#modalCreate", guardar: "#btnGuardarCreate", cargando: "#btnLoadingCreate",
        });
    });

    $("#almacenesTable").on("click", ".btn-edit", function () {
        $.get("/almacenes/" + $(this).data("id"), function (res) {
            const d = res.data;
            Crud.limpiarErrores("#formEdit");
            $("#formEdit").find('[name="id"]').val(d.id);
            $("#formEdit").find('[name="descripcion"]').val(d.descripcion);
            $("#formEdit").find('[name="establecimiento"]').val(d.establecimiento);
            Crud.ventana("#modalEdit").show();
        });
    });

    $("#formEdit").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this, url: "/almacenes/" + $(this).find('[name="id"]').val(), method: "PUT",
            tabla: tabla, modal: "#modalEdit", guardar: "#btnGuardarEdit", cargando: "#btnLoadingEdit",
        });
    });

    $("#almacenesTable").on("click", ".btn-view", function () {
        $.get("/almacenes/" + $(this).data("id"), function (res) {
            const d = res.data;
            $("#almacenDetails, #detalleBody, .modal-detalle-body").html(
                Crud.detalle([
                    ["Descripción", d.descripcion],
                    ["Establecimiento", d.establecimiento],
                    ["Creado", d.created_at],
                    ["Actualizado", d.updated_at],
                ])
            );
            Crud.ventana("#modalView").show();
        });
    });

    $("#almacenesTable").on("click", ".btn-delete", function () {
        $("#delete_id").val($(this).data("id"));
        $("#delete-descripcion").text($(this).data("descripcion") || "");
        Crud.ventana("#modalDelete").show();
    });

    $("#btnConfirmDelete").on("click", function () {
        Crud.eliminar({ url: "/almacenes/" + $("#delete_id").val(), tabla: tabla });
    });
});
