$(function () {
    "use strict";
    Crud.init();

    const tabla = Crud.tabla("#auditoriaTable", "/auditoria/data", [
        { data: "fecha" },
        { data: "usuario" },
        Crud.cruda("accion"),
        { data: "entidad" },
        Crud.cruda("descripcion"),
        Crud.cruda("cambios"),
        { data: "ip" },
    ], {
        order: [[0, "desc"]],
        // Los filtros viajan al servidor: la bitacora es demasiado grande para
        // traerla entera y filtrar en el navegador.
        ajax: {
            url: "/auditoria/data",
            dataSrc: "data",
            data: function (d) {
                d.usuario_id = $("#filtro_usuario").val();
                d.accion = $("#filtro_accion").val();
                d.entidad = $("#filtro_entidad").val();
                d.desde = $("#filtro_desde").val();
                d.hasta = $("#filtro_hasta").val();
            },
        },
    });

    $("#filtro_usuario, #filtro_accion, #filtro_entidad, #filtro_desde, #filtro_hasta")
        .on("change", () => tabla.ajax.reload());
});
