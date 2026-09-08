/**
 * Helpers compartidos por las pantallas de listado (CRUD).
 *
 * Las vistas de cada modulo siguen la misma convencion de ids:
 *   #alert-messages                          zona de avisos
 *   #modalCreate  #formCreate  #btnGuardarCreate  #btnLoadingCreate
 *   #modalEdit    #formEdit    #btnGuardarEdit    #btnLoadingEdit
 *   #modalDelete  #delete_id   #btnConfirmDelete  #btnLoadingDelete
 *   #error-<campo>                           mensaje de validacion por campo
 *
 * Este archivo lo carga el layout master, antes del config.js de cada modulo.
 */
window.Crud = (function ($) {
    "use strict";

    const idioma = {
        emptyTable: "No hay registros",
        search: "Buscar:",
        lengthMenu: "Mostrar _MENU_ registros",
        info: "Mostrando _START_ a _END_ de _TOTAL_",
        infoEmpty: "Sin registros",
        infoFiltered: "(filtrado de _MAX_)",
        zeroRecords: "Sin coincidencias",
        processing: "Cargando...",
        paginate: { first: "Primero", last: "Ultimo", next: "Siguiente", previous: "Anterior" },
    };

    function init() {
        $.ajaxSetup({ headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") } });
    }

    /**
     * Aviso flotante. Antes se insertaba dentro de #alert-messages, lo que
     * empujaba la tabla hacia abajo y obligaba a subir el scroll para leerlo:
     * el conjunto se sentia como una recarga de pagina.
     */
    function aviso(tipo, mensaje) {
        let zona = document.getElementById("crud-avisos");

        if (!zona) {
            zona = document.createElement("div");
            zona.id = "crud-avisos";
            zona.style.cssText =
                "position:fixed;top:1rem;right:1rem;z-index:1085;" +
                "display:flex;flex-direction:column;gap:.5rem;max-width:min(24rem,90vw)";
            document.body.appendChild(zona);
        }

        const $aviso = $(
            '<div class="alert alert-' + tipo + ' alert-dismissible fade show shadow-sm mb-0" role="alert">' +
            mensaje +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
        );

        $(zona).append($aviso);

        // Los errores se quedan hasta que el usuario los cierre; el resto se va solo.
        if (tipo !== "danger") {
            setTimeout(function () {
                $aviso.fadeOut(300, () => $aviso.remove());
            }, 4000);
        }
    }

    function tabla(selector, url, columnas, extra) {
        return $(selector).DataTable(
            $.extend(
                {
                    ajax: { url: url, dataSrc: "data" },
                    columns: columnas,
                    order: [[0, "asc"]],
                    language: idioma,
                    responsive: true,
                },
                extra || {}
            )
        );
    }

    /** Columna que se pinta tal cual viene del servidor (badges, botones). */
    function cruda(data) {
        return { data: data, orderable: false, searchable: false };
    }

    function limpiarErrores(form) {
        $(form).find(".is-invalid").removeClass("is-invalid");
        $(form).find(".invalid-feedback").text("");
    }

    /** Laravel responde 422 con {errors: {campo: [msg]}}. */
    function pintarErrores(form, xhr, generico) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            $.each(xhr.responseJSON.errors, function (campo, msgs) {
                const base = campo.split(".")[0];
                $(form).find('[name="' + campo + '"], [name^="' + base + '"]').addClass("is-invalid");
                $("#error-" + base).text(msgs[0]);
            });
            return;
        }
        aviso("danger", (xhr.responseJSON && xhr.responseJSON.message) || generico);
    }

    function ocupado(guardar, cargando, on) {
        $(guardar).toggle(!on);
        $(cargando).toggle(on);
    }

    function ventana(selector) {
        const el = $(selector)[0];
        return el ? bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el) : null;
    }

    function cerrar(selector) {
        const m = $(selector)[0] && bootstrap.Modal.getInstance($(selector)[0]);
        if (m) m.hide();
    }

    /**
     * Envia un formulario por AJAX. Con `archivos: true` usa FormData, que es lo
     * que necesitan los formularios con imagenes; PUT viaja como POST + _method
     * porque PHP no parsea multipart en PUT.
     */
    function enviar(o) {
        const form = $(o.form);
        limpiarErrores(form);
        ocupado(o.guardar, o.cargando, true);

        let datos = form.serialize();
        let extras = {};

        if (o.archivos) {
            datos = new FormData(form[0]);
            if (o.method === "PUT") datos.append("_method", "PUT");
            extras = { processData: false, contentType: false };
        }

        $.ajax(
            $.extend(
                {
                    url: o.url,
                    method: o.archivos && o.method === "PUT" ? "POST" : o.method || "POST",
                    data: datos,
                    success: function (res) {
                        if (o.modal) cerrar(o.modal);
                        aviso("success", res.message || "Guardado");
                        if (o.tabla) o.tabla.ajax.reload(null, false);
                        if (o.luego) o.luego(res);
                    },
                    error: function (xhr) {
                        pintarErrores(form, xhr, o.mensajeError || "No se pudo guardar.");
                    },
                    complete: function () {
                        ocupado(o.guardar, o.cargando, false);
                    },
                },
                extras
            )
        );
    }

    function eliminar(o) {
        ocupado("#btnConfirmDelete", "#btnLoadingDelete", true);

        $.ajax({
            url: o.url,
            method: "DELETE",
            success: function (res) {
                cerrar("#modalDelete");
                aviso("success", res.message || "Eliminado");
                if (o.tabla) o.tabla.ajax.reload(null, false);
            },
            error: function (xhr) {
                cerrar("#modalDelete");
                aviso("danger", (xhr.responseJSON && xhr.responseJSON.message) || "No se pudo eliminar.");
            },
            complete: function () {
                ocupado("#btnConfirmDelete", "#btnLoadingDelete", false);
            },
        });
    }

    /** Construye las filas de un detalle a partir de pares etiqueta/valor. */
    function detalle(pares) {
        return (
            '<dl class="row mb-0">' +
            pares
                .map(function (p) {
                    return '<dt class="col-sm-4 text-muted">' + p[0] + "</dt>" +
                           '<dd class="col-sm-8">' + (p[1] === null || p[1] === undefined || p[1] === "" ? "-" : p[1]) + "</dd>";
                })
                .join("") +
            "</dl>"
        );
    }

    function soles(n) {
        return "S/ " + Number(n || 0).toFixed(2);
    }

    function vacio(colspan, texto) {
        return '<tr><td colspan="' + colspan + '" class="text-center text-muted py-3">' + texto + "</td></tr>";
    }

    return {
        init: init,
        aviso: aviso,
        tabla: tabla,
        cruda: cruda,
        limpiarErrores: limpiarErrores,
        pintarErrores: pintarErrores,
        ocupado: ocupado,
        ventana: ventana,
        cerrar: cerrar,
        enviar: enviar,
        eliminar: eliminar,
        detalle: detalle,
        soles: soles,
        vacio: vacio,
    };
})(jQuery);
