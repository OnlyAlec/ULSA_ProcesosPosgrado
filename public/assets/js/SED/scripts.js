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
                const errorMsg = xhr.responseText || "Error al procesar la solicitud";
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
