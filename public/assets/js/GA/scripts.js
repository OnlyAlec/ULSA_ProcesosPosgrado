$(document).ready(function () {
    $.ajax({
        url: "", // Asegúrate de que la URL sea correcta
        type: "POST",
        data: { action: "getPrograms" },
        success: function (response) {
            console.log(response);
            const carreraOptions = $("#carreraOptions");
            carreraOptions.empty(); // Limpiar opciones existentes
            if (response.success && Array.isArray(response.data)) {
                response.data.forEach((program) => {
                    carreraOptions.append(`<li data-value="${program}">${program}</li>`);
                });
            } else {
                console.error("Error: La respuesta no contiene datos válidos.");
            }
        },
        error: function () {
            console.error("Error al cargar las opciones de carrera.");
        },
    });

    // Manejar la selección de una opción
    $("#carreraOptions").on("click", "li", function () {
        const selectedValue = $(this).data("value");
        $("#carrera").val(selectedValue);
        $("#carreraOptions").hide();
    });

    // Mostrar/ocultar el dropdown al hacer clic en el input
    $("#carrera").on("click", function (e) {
        e.stopPropagation();
        $("#carreraOptions").toggle();
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest(".datalist").length) {
            $("#carreraOptions").hide();
        }
    });

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
