// !FIXME: Use non deprecated functions
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
                const errorMsg = "Error al procesar la solicitud";
                displayMessage(form, errorMsg, "error");
            },
            complete: function () {
                form.find("button").prop("disabled", false);
            },
        });
    });

    $("#btn-consultar").on("click", function () {
        const button = $(this);
        const tableContainer = $("#tableStudents");
        const tableBody = tableContainer.find("tbody");

        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getTableStudents" },
            beforeSend: function () {
                button.prop("disabled", true);
                tableContainer.hide();
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
                tableContainer.show();
            },
            error: function (xhr) {
                const errorMsg = "Error al procesar la solicitud";
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

$(function () {
    $(".custom-file-input").on("change", function (e) {
        const fileName = $(e.target).prop("files")[0]?.name
            ? $(e.target).prop("files")[0].name.length > 70
                ? $(e.target).prop("files")[0].name.substring(0, 68) + "..."
                : $(e.target).prop("files")[0].name
            : "Seleccionar archivo...";
        $(e.target).next().text(fileName);
    });
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
