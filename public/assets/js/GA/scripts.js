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
                displayMessage(form, "Acción realizada correctamente");
                console.log(response);
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

    $("#btn-consultar").on("click", function () {
        const button = $(this);
        let tableBody = $("#tableStudents tbody");

        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getTableStudents" },
            beforeSend: function () {
                button.prop("disabled", true);
                tableBody.empty();
            },
            success: function (response) {
                if (response.data) {
                    response.data.forEach((student) => {
                        let row = `<tr>
                                <td>${student.ulsaID}</td>
                                <td>${student.firstName} ${student.lastName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                            </tr>`;
                        tableBody.append(row);
                    });
                } else {
                    tableBody.append(
                        '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
                    );
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || "Error al procesar la solicitud";
                displayMessage(divError, errorMsg, "error");
            },
            complete: function () {
                button.prop("disabled", false);
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

function setupBtnsGA(name) {
    if (!name) {
        throw new Error("Missing name - setupBtnsGA");
    }

    $("#" + name).on("click", function () {
        $(".alert").remove();
        $(".forms-result").hide();
        $(".sectionsGA button").removeClass("btn-primary").addClass("btn-outline-primary");
        $(this).removeClass("btn-outline-primary").addClass("btn-primary");

        const div = name.split("-").slice(1).join("-");
        hideSectionsGA();
        $("#" + div).show();
    });
}

function hideSectionsGA() {
    $(".sectionGA").each(function () {
        $(this).hide();
    });
}

// Llamadas para inicializar
setupBtnsGA("btn-crear");
setupBtnsGA("btn-consultar");
setupBtnsGA("btn-eliminar");