$(function () {
    "use strict";
    Crud.init();

    // El formulario ya viene lleno desde Blade: aca solo se envia y se
    // previsualizan los archivos. La ruta de actualizacion es POST, no PUT.
    $("#empresaForm").on("submit", function (e) {
        e.preventDefault();
        Crud.enviar({
            form: this,
            url: "/empresa/" + $("#empresaId").val(),
            method: "POST",
            archivos: true,
            guardar: "#btnGuardar",
            cargando: "#btnLoading",
            mensajeError: "No se pudo guardar la configuración.",
            luego: function (res) {
                if (res.data && res.data.certificado_pfx) {
                    $("#certificado-badge").removeClass("bg-secondary").addClass("bg-success").text("Cargado");
                    $("#certificado-info").text(res.data.certificado_pfx);
                }
            },
        });
    });

    $("#logo").on("change", function () {
        const archivo = this.files[0];
        if (!archivo) return;

        const lector = new FileReader();
        lector.onload = function (ev) {
            // Blade solo imprime #logo-img cuando ya hay un logo guardado, asi
            // que la primera vez hay que crearlo.
            if (!$("#logo-img").length) {
                $("#logo-preview").prepend(
                    '<div class="mb-3" id="logo-actual"><label class="form-label fw-bold">Logo</label>' +
                    '<div><img id="logo-img" width="150" height="150" class="border rounded p-2" alt="Logo"></div></div>'
                );
            }
            $("#logo-img").attr("src", ev.target.result);
            $("#logo-preview, #logo-actual").show();
        };
        lector.readAsDataURL(archivo);
    });

    $("#certificado_pfx").on("change", function () {
        if (this.files[0]) $("#certificado-info").text(this.files[0].name);
    });

    $("#link_ubicacion").on("input", function () {
        const url = $(this).val().trim();
        $("#link_ubicacion_preview").attr("href", url).toggle(url.length > 0);
    });
});
