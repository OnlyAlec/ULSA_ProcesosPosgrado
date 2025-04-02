$(function () {
    setupBtns("btn-config");
    setupDates();

    $(".form-control.date").datepicker();
    $(".dateForm").on("submit", function (e) {
        e.preventDefault();
        const form = $(this);
        const divError = $(".sectionsAFI");
        const date = form.find(".date").val();
        const type = form.data("type");

        if (date == "") {
            displayMessage(divError, "Debe seleccionar una fecha", "error");
            return;
        }

        $.ajax({
            url: "",
            type: "POST",
            data: { action: "setConfigDate", date: date, type: type },
            beforeSend: function () {
                $(".alert").remove();
                form.find("button").prop("disabled", true);
            },
            success: function (response) {
                if (response.success) {
                    displayMessage(divError, "Guardado exitosamente");
                } else {
                    displayMessage(divError, response, "error");
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || "Error al procesar la solicitud";
                displayMessage(divError, errorMsg, "error");
            },
            complete: function () {
                form.find("button").prop("disabled", false);
            },
        });
    });
});

function setupDates() {
    const forms = $(".dateForm");
    forms.each(function () {
        const dateForm = $(this).find(".date");
        const date = dateForm.data("set");
        if (date) {
            dateForm.val(date);
        }
    });
}
