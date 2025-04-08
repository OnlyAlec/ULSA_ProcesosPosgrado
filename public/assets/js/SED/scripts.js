$(document).ready(function () {
    $("form").submit(function (e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);

        $.ajax({
            url: "",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $(".alert").remove();
                form.find("button").prop("disabled", true);
            },
            success: function (response) {
                if (response.success) {
                    displayMessage(form, "Archivo procesado correctamente");
                    window.location.href = "table.php";
                }
            },
            error: function (xhr) {
                const errorMsg = "Error al procesar la solicitud";
                displayMessage(form, errorMsg, "error");
            },
            complete: function () {
                form.find("button").prop("disabled", false);
            },
        });
    });

    function displayMessage(pos, message, type = "success") {
        const newDiv = document.createElement("div");
        newDiv.className = type == "success" ? "alert alert-success" : "alert alert-danger";
        newDiv.innerHTML = message;
        pos.before(newDiv);
    }
});

$(function () {
    // 1) Cerrar dropdown al clicar fuera
    $(document).on("click", function (e) {
        if (!$(e.target).closest(".datalist").length) {
            $(".datalist ul").hide();
        }
    });

    // 3) Mostrar/ocultar lista solo al clicar en el input
    $(".datalist-input").on("click", function (e) {
        e.stopPropagation();
        const $d = $(this).closest(".datalist");
        $d.find("ul").toggle();
    });

    // 4) Seleccionar un elemento (delegación)
    $(".datalist ul").on("click", "li:not(.not-selectable)", function (e) {
        e.stopPropagation();
        const $d = $(this).closest(".datalist");
        const $input = $d.find(".datalist-input");
        $input.val($(this).text()).trigger("input");
        $d.find("ul").hide();
        // Cambiar ícono a “limpiar”
        $d.find(".filter").removeClass("fa-search").addClass("fa-times");
    });

    // 5) Clic en el ícono:
    // - Si es “×” (fa-times), limpia el input y cambia el ícono a lupa.
    // - Si es “lupa” (fa-search), abre/oculta la lista.
    $(".datalist .filter").on("click", function (e) {
        e.stopPropagation();
        const $icon = $(this);
        const $d = $icon.closest(".datalist");
        const $input = $d.find(".datalist-input");
        if ($icon.hasClass("fa-times")) {
            // Si es "×" limpiamos y cambiamos el ícono sin abrir la lista.
            $input.val("").trigger("input");
            $icon.removeClass("fa-times").addClass("fa-search");
        } else {
            // Si es lupa, se abre o cierra la lista.
            $d.find("ul").toggle();
        }
    });
});
