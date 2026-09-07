/**
 * Arqueo de cajas.
 *
 * Las vistas cargan este archivo desde build/js/apertura-caja/config.js; el
 * copiado a public/build lo hace vite-plugin-static-copy en `npm run build`.
 */
$(function () {
    "use strict";

    const csrf = $('meta[name="csrf-token"]').attr("content");
    let cerrandoId = null;

    $.ajaxSetup({ headers: { "X-CSRF-TOKEN": csrf } });

    const tabla = $("#cajasTable").DataTable({
        ajax: { url: "/apertura-caja/data", dataSrc: "data" },
        columns: [
            { data: "correlativo" },
            { data: "fecha_apertura" },
            { data: "responsable" },
            { data: "monto_inicial" },
            { data: "estado_badge" },
            { data: "acciones", orderable: false, searchable: false },
        ],
        order: [[0, "asc"]],
        language: {
            emptyTable: "Todavia no hay aperturas registradas",
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_",
            infoEmpty: "Sin registros",
            zeroRecords: "Sin coincidencias",
            paginate: { first: "Primero", last: "Ultimo", next: "Siguiente", previous: "Anterior" },
        },
    });

    function avisar(tipo, mensaje) {
        $("#alert-messages").html(
            '<div class="alert alert-' + tipo + ' alert-dismissible fade show" role="alert">' +
            mensaje +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
        );
    }

    function limpiarErrores(form) {
        $(form).find(".is-invalid").removeClass("is-invalid");
        $(form).find(".invalid-feedback").text("");
    }

    // Laravel devuelve 422 con {errors: {campo: [mensaje]}}; cada campo tiene su
    // propio div #error-<campo> en el modal.
    function mostrarErrores(form, xhr, mensajeGenerico) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            $.each(xhr.responseJSON.errors, function (campo, mensajes) {
                $(form).find('[name="' + campo + '"]').addClass("is-invalid");
                $("#error-" + campo).text(mensajes[0]);
            });
            return;
        }
        avisar("danger", (xhr.responseJSON && xhr.responseJSON.message) || mensajeGenerico);
    }

    function alternarBotones(guardar, cargando, ocupado) {
        $(guardar).toggle(!ocupado);
        $(cargando).toggle(ocupado);
    }

    /* ---------- abrir ---------- */

    $("#btnAbrirCaja").on("click", function () {
        $.get("/apertura-caja/verificar", function (res) {
            if (res.caja_abierta) {
                avisar("warning", "Ya tienes una caja abierta. Cierrala antes de abrir otra.");
                return;
            }
            limpiarErrores("#formAbrir");
            $("#formAbrir")[0].reset();
            new bootstrap.Modal($("#modalAbrir")[0]).show();
        });
    });

    $("#formAbrir").on("submit", function (e) {
        e.preventDefault();
        limpiarErrores(this);
        alternarBotones("#btnGuardarAbrir", "#btnLoadingAbrir", true);

        $.ajax({
            url: "/apertura-caja",
            method: "POST",
            data: $(this).serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance($("#modalAbrir")[0]).hide();
                avisar("success", res.message);
                tabla.ajax.reload(null, false);
            },
            error: function (xhr) {
                mostrarErrores("#formAbrir", xhr, "No se pudo abrir la caja.");
            },
            complete: function () {
                alternarBotones("#btnGuardarAbrir", "#btnLoadingAbrir", false);
            },
        });
    });

    /* ---------- cerrar ---------- */

    $("#cajasTable").on("click", ".btn-cerrar", function () {
        cerrandoId = $(this).data("id");
        limpiarErrores("#formCerrar");
        $("#formCerrar")[0].reset();
        $("#cerrar_responsable").val($(this).data("responsable"));
        $("#cerrar_monto_inicial").val("S/ " + $(this).data("monto_inicial"));
        new bootstrap.Modal($("#modalCerrar")[0]).show();
    });

    $("#formCerrar").on("submit", function (e) {
        e.preventDefault();
        limpiarErrores(this);
        alternarBotones("#btnGuardarCerrar", "#btnLoadingCerrar", true);

        $.ajax({
            url: "/apertura-caja/" + cerrandoId + "/cerrar",
            method: "POST",
            data: $(this).serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance($("#modalCerrar")[0]).hide();
                avisar("success", res.message);
                tabla.ajax.reload(null, false);
            },
            error: function (xhr) {
                mostrarErrores("#formCerrar", xhr, "No se pudo cerrar la caja.");
            },
            complete: function () {
                alternarBotones("#btnGuardarCerrar", "#btnLoadingCerrar", false);
            },
        });
    });

    /* ---------- detalle ---------- */

    $("#cajasTable").on("click", ".btn-detalle", function () {
        $.get("/apertura-caja/" + $(this).data("id") + "/detalle", function (res) {
            const d = res.data;
            $("#detalle_responsable").text(d.responsable);
            $("#detalle_fecha_apertura").text(d.fecha_apertura + " " + d.hora_apertura);
            $("#detalle_monto_inicial").text("S/ " + Number(d.monto_inicial).toFixed(2));
            $("#detalle_estado").text(d.estado);

            const filas = d.ventas.map(function (v, i) {
                return "<tr><td>" + (i + 1) + "</td><td>" + v.fecha + "</td><td>" + v.hora +
                    "</td><td>" + v.cliente + "</td><td>" + v.documento + "</td><td>" + v.numero +
                    '</td><td class="text-end">' + Number(v.monto).toFixed(2) + "</td></tr>";
            });

            $("#detalleBody").html(
                filas.length ? filas.join("") : '<tr><td colspan="7" class="text-center text-muted">Sin ventas en este arqueo</td></tr>'
            );
            new bootstrap.Modal($("#modalDetalle")[0]).show();
        });
    });

    /* ---------- resumen ---------- */

    $("#cajasTable").on("click", ".btn-resumen", function () {
        $.get("/apertura-caja/" + $(this).data("id") + "/resumen", function (res) {
            const d = res.data;
            $("#resumen_id").val(d.id);
            $("#resumen_responsable").text(d.responsable);
            $("#resumen_fecha_apertura").text(d.fecha_apertura);
            $("#resumen_monto_inicial").text("S/ " + Number(d.monto_inicial).toFixed(2));
            $("#resumen_total_ventas").text("S/ " + Number(d.total_ventas).toFixed(2));
            $("#resumen_cantidad_ventas").text(d.cantidad_ventas);
            $("#resumen_total_gastos").text("S/ " + Number(d.total_gastos).toFixed(2));
            $("#resumen_total").text("S/ " + Number(d.total).toFixed(2));

            const filas = d.gastos.map(function (g) {
                return "<tr><td>" + g.motivo + '</td><td class="text-end">' + Number(g.monto).toFixed(2) + "</td></tr>";
            });

            $("#resumenGastosBody").html(
                filas.length ? filas.join("") : '<tr><td colspan="2" class="text-center text-muted">Sin gastos registrados</td></tr>'
            );
            new bootstrap.Modal($("#modalResumen")[0]).show();
        });
    });

    /* ---------- descargas ---------- */

    $("#cajasTable").on("click", ".btn-reporte", function () {
        window.open("/apertura-caja/" + $(this).data("id") + "/reporte", "_blank");
    });

    $("#btnExportarExcel").on("click", function () {
        window.open("/apertura-caja/" + $("#resumen_id").val() + "/excel", "_blank");
    });
});
