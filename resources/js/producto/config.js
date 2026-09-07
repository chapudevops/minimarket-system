$(function () {
    "use strict";
    Crud.init();

    let almacenes = [];

    const tabla = Crud.tabla("#productosTable", "/productos/data", [
        { data: "correlativo" },
        { data: "codigo_interno" },
        { data: "descripcion" },
        { data: "unidad" },
        { data: "precio_venta", className: "text-end" },
        { data: "stock_total", className: "text-end" },
        Crud.cruda("estado_texto"),
        { data: "created_at" },
        Crud.cruda("acciones"),
    ]);

    $.get("/productos/almacenes", function (res) {
        almacenes = res.almacenes || [];
    });

    /** Una fila por almacen; el stock viaja como stocks[i][almacen_id|stock]. */
    function filasAlmacenes(stocks) {
        const porAlmacen = {};
        (stocks || []).forEach(function (s) { porAlmacen[s.almacen_id] = s.stock; });

        return almacenes.map(function (a, i) {
            return "<tr><td>" + a.descripcion +
                '<input type="hidden" name="stocks[' + i + '][almacen_id]" value="' + a.id + '"></td>' +
                '<td><input type="number" min="0" class="form-control form-control-sm" ' +
                'name="stocks[' + i + '][stock]" value="' + (porAlmacen[a.id] || 0) + '"></td>' +
                "<td></td></tr>";
        }).join("");
    }

    function abrirFormulario(titulo, datos) {
        Crud.limpiarErrores("#formProducto");
        $("#formProducto")[0].reset();
        $("#modalProductoTitle").text(titulo);
        $("#formProducto").find('[name="id"]').val(datos ? datos.id : "");

        if (datos) {
            Object.keys(datos).forEach(function (k) {
                const campo = $("#formProducto").find('[name="' + k + '"]');
                if (campo.length && campo.attr("type") !== "file") campo.val(datos[k]);
            });
        }

        $("#tbodyAlmacenes").html(filasAlmacenes(datos ? datos.stocks : []));
        Crud.ventana("#modalProducto").show();
    }

    $("#btnNuevoProducto").on("click", function () {
        abrirFormulario("Nuevo Producto", null);
    });

    $("#productosTable").on("click", ".btn-edit", function () {
        $.get("/productos/" + $(this).data("id"), function (res) {
            abrirFormulario("Editar Producto", res.data);
        });
    });

    $("#formProducto").on("submit", function (e) {
        e.preventDefault();
        const id = $(this).find('[name="id"]').val();
        Crud.enviar({
            form: this,
            url: id ? "/productos/" + id : "/productos",
            method: id ? "PUT" : "POST",
            archivos: true,
            tabla: tabla, modal: "#modalProducto", guardar: "#btnGuardar", cargando: "#btnLoading",
        });
    });

    $("#productosTable").on("click", ".btn-view", function () {
        $.get("/productos/" + $(this).data("id"), function (res) {
            const d = res.data;
            const stocks = (d.stocks || []).map(function (s) {
                return s.almacen_nombre + ": " + s.stock;
            }).join(" · ") || "Sin stock registrado";

            $("#productoDetails, #detalleBody").html(
                Crud.detalle([
                    ["Código interno", d.codigo_interno],
                    ["Código de barras", d.codigo_barras],
                    ["Descripción", d.descripcion],
                    ["Marca", d.marca],
                    ["Presentación", d.presentacion],
                    ["Unidad", d.unidad],
                    ["Operación", d.operacion_texto],
                    ["Precio compra", Crud.soles(d.precio_compra)],
                    ["Precio venta", Crud.soles(d.precio_venta)],
                    ["Stock por almacén", stocks],
                ])
            );
            Crud.ventana("#modalView").show();
        });
    });

    $("#productosTable").on("click", ".btn-delete", function () {
        $("#delete_id").val($(this).data("id"));
        $("#delete-descripcion, #delete-nombre").text($(this).data("nombre") || "");
        Crud.ventana("#modalDelete").show();
    });

    $("#btnConfirmDelete").on("click", function () {
        Crud.eliminar({ url: "/productos/" + $("#delete_id").val(), tabla: tabla });
    });
});
