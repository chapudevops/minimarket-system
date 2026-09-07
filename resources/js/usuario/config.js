$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#usuariosTable", "/usuarios/data", [
        { data: "correlativo" },
        { data: "name" },
        { data: "email" },
        { data: "caja" },
        { data: "almacen" },
        { data: "roles" },
        Crud.cruda("estado_badge"),
        { data: "created_at" },
        Crud.cruda("acciones"),
    ]);

    // Cajas, almacenes y roles alimentan el formulario.
    $.get("/usuarios/form-data", function (res) {
        $("#caja_id").html(
            ['<option value="">Sin asignar</option>'].concat(
                (res.cajas || []).map(function (c) {
                    return '<option value="' + c.id + '">' + c.descripcion + "</option>";
                })
            ).join("")
        );

        $("#almacen_id").html(
            ['<option value="">Sin asignar</option>'].concat(
                (res.almacenes || []).map(function (a) {
                    return '<option value="' + a.id + '">' + a.descripcion + "</option>";
                })
            ).join("")
        );

        $("#rolesContainer").html(
            (res.roles || []).map(function (r) {
                return '<div class="col-md-6"><div class="form-check">' +
                    '<input class="form-check-input" type="checkbox" name="roles[]" ' +
                    'value="' + r.id + '" id="rol_' + r.id + '">' +
                    '<label class="form-check-label" for="rol_' + r.id + '">' + r.nombre + "</label>" +
                    "</div></div>";
            }).join("")
        );
    });

    function abrirFormulario(titulo, datos) {
        Crud.limpiarErrores("#formUsuario");
        $("#formUsuario")[0].reset();
        $("#rolesContainer input").prop("checked", false);
        $("#modalUsuarioTitle").text(titulo);
        $("#usuario_id").val(datos ? datos.id : "");

        // Al editar, la contrasena es opcional: solo se cambia si se escribe.
        $("#passwordRequired").toggle(!datos);
        $("#password").prop("required", !datos);

        if (datos) {
            $("#name").val(datos.name);
            $("#email").val(datos.email);
            $("#caja_id").val(datos.caja_id || "");
            $("#almacen_id").val(datos.almacen_id || "");
            $("#estado").val(datos.estado ? 1 : 0);
            (datos.roles || []).forEach(function (id) {
                $("#rol_" + id).prop("checked", true);
            });
        }

        Crud.ventana("#modalUsuario").show();
    }

    $("#btnNuevoUsuario").on("click", function () {
        abrirFormulario("Nuevo Usuario", null);
    });

    $("#usuariosTable").on("click", ".btn-edit", function () {
        $.get("/usuarios/" + $(this).data("id") + "/edit", function (res) {
            abrirFormulario("Editar Usuario", res.data || res);
        });
    });

    $("#formUsuario").on("submit", function (e) {
        e.preventDefault();
        const id = $("#usuario_id").val();
        Crud.enviar({
            form: this,
            url: id ? "/usuarios/" + id : "/usuarios",
            method: id ? "PUT" : "POST",
            tabla: tabla, modal: "#modalUsuario", guardar: "#btnGuardar", cargando: "#btnLoading",
        });
    });

    $("#usuariosTable").on("click", ".btn-view", function () {
        $.get("/usuarios/" + $(this).data("id"), function (res) {
            const d = res.data;
            $("#usuarioDetails").html(
                Crud.detalle([
                    ["Nombre", d.name],
                    ["Email", d.email],
                    ["Caja", d.caja],
                    ["Almacén", d.almacen],
                    ["Roles", d.roles],
                    ["Estado", d.estado_badge],
                    ["Creado", d.created_at],
                ])
            );
            Crud.ventana("#modalView").show();
        });
    });

    $("#usuariosTable").on("click", ".btn-toggle", function () {
        $("#toggle_id").val($(this).data("id"));
        $("#toggle-nombre").text($(this).data("nombre") || "");
        $("#toggle-action").text($(this).data("estado") == 1 ? "desactivar" : "activar");
        Crud.ventana("#modalToggleStatus").show();
    });

    $("#btnConfirmToggle").on("click", function () {
        $.post("/usuarios/" + $("#toggle_id").val() + "/toggle-status", function (res) {
            Crud.cerrar("#modalToggleStatus");
            Crud.aviso("success", res.message);
            tabla.ajax.reload(null, false);
        }).fail(function (xhr) {
            Crud.cerrar("#modalToggleStatus");
            Crud.aviso("danger", (xhr.responseJSON && xhr.responseJSON.message) || "No se pudo cambiar el estado.");
        });
    });

    $("#usuariosTable").on("click", ".btn-delete", function () {
        $("#delete_id").val($(this).data("id"));
        $("#delete-nombre").text($(this).data("nombre") || "");
        Crud.ventana("#modalDelete").show();
    });

    $("#btnConfirmDelete").on("click", function () {
        Crud.eliminar({ url: "/usuarios/" + $("#delete_id").val(), tabla: tabla });
    });
});
