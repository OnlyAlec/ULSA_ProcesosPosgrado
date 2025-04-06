$("#programType").on("change", function () {
    const selectedOption = $(this).val();
    const table = $("#studentsTable");
    const tbody = table.find("tbody");
    const rows = tbody.find("tr");
    rows.show();

    $(".studentCheckbox").prop("checked", false);
    $("#confirmChanges").prop("disabled", true);
    $("#selectedCount").text("0");
    $("#selectAll").prop("checked", false);

    //console.log(selectedOption);

    $.ajax({
        url: "",
        type: "POST",
        data: { action: selectedOption },
        success: function (response) {
            console.log(response);

            //if (!Array.isArray(response))
            //response = Object.values(response);

            if (!response.success || !Array.isArray(response.data)) {
                displayMessage($(".sectionsSED"), "Ocurrio un problema", "error");
                return;
            }

            /*if (response.length == 0) {
                displayMessage($('.sectionsAFI'), "No hay áreas disponibles para el tipo de programa seleccionado", 'error');
                return;
            }*/

            $("#programArea").empty();
            const option = document.createElement("option");
            option.text = "Seleccione un tipo de programa primero";
            $("#programArea").append(option);

            response.data.forEach((element) => {
                const option = document.createElement("option");
                option.value = element;
                option.text = element;
                $("#programArea").append(option);
            });

            if (selectedOption != "") {
                $("#filterArea").show();
            } else {
                $("#filterArea").hide();
            }
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || "Error al procesar la solicitud";
            displayMessage($(".sectionsAFI"), errorMsg, "error");
        },
    });
});

$("#programArea").on("change", function () {
    const selectedOption = $(this).val();
    const table = $("#studentsTable");
    const tbody = table.find("tbody");
    const rows = tbody.find("tr");

    $(".studentCheckbox").prop("checked", false);
    $("#confirmChanges").prop("disabled", true);
    $("#selectedCount").text("0");
    $("#selectAll").prop("checked", false);

    let selectedType = $("#programType").val();
    if (selectedType === "getMasters") {
        selectedType = "MAESTRÍA";
    } else if (selectedType === "getSpecialty") {
        selectedType = "ESPECIALIDAD";
    } else {
        selectedType = "";
    }

    rows.each(function () {
        const row = $(this);
        const area = row.data("carrer").toUpperCase();

        const type = area.split(" ")[0].toUpperCase();

        if (selectedOption === "" || selectedOption === "Seleccione un tipo de programa primero") {
            if (selectedType != "" && type === selectedType.toUpperCase()) {
                row.show();
            } else {
                row.hide();
            }
        } else {
            if (area === selectedOption.toUpperCase()) {
                row.show();
            } else {
                row.hide();
            }
        }
    });
});

$(".studentCheckbox").on("change", function () {
    const checked = $(this).is(":checked");
    const row = $(this).closest("tr");

    const selectedCount = $(".studentCheckbox:checked").length;
    $("#selectedCount").text(selectedCount);

    if (checked) {
        row.addClass("selected");
        $("#confirmChanges").prop("disabled", false);
    } else {
        row.removeClass("selected");
        if (selectedCount === 0) {
            $("#confirmChanges").prop("disabled", true);
        }
    }
});

$("#selectAll").on("change", function () {
    const checked = $(this).is(":checked");
    $(".studentCheckbox:visible").prop("checked", checked);
    $(".studentCheckbox:visible").trigger("change");
});

$("#confirmChanges").on("click", function () {
    let selectedStudents = [];

    $(".studentCheckbox:checked").each(function () {
        let studentID = $(this).closest("tr").find(".changeSED").data("student-id");
        if (studentID) selectedStudents.push(studentID);
    });

    if (selectedStudents.length === 0) {
        alert("Selecciona al menos a un estudiante.");
        return;
    }

    $.ajax({
        url: "",
        type: "POST",
        data: { action: "updateSED", studentIDS: selectedStudents },
        success: function (response) {
            if (response.success) {
                alert("EXITO: El estatus SED de los alumnos ha sido actualizado.");

                $(".studentCheckbox:checked").each(function () {
                    let btn = $(this).closest("tr").find(".changeSED i");
                    btn.removeClass("fa-minus-square")
                        .addClass("fa-check-square")
                        .css("color", "#36b18c");
                });

                $("#programType").val("");
                $("#programArea").val("");
                $("#filterArea").hide();

                $(".studentCheckbox").prop("checked", false);
                $("tr").removeClass("selected");
                $("#confirmChanges").prop("disabled", true);
                $("#selectedCount").text("0");
                $("#selectAll").prop("checked", false);
            } else {
                alert("ERROR: Error al actualizar el estatus SED de los alumnos.");
            }
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || "Error al procesar la solicitud";
            displayMessage($(".sectionsAFI"), errorMsg, "error");
        },
    });
});

$(".changeSED").on("click", function () {
    let icon = $(this).find("i");
    let button = icon.closest("button");
    let studentID = $(this).data("student-id");
    let newState = icon.hasClass("fa-minus-square") ? 1 : 0;

    $.ajax({
        url: "",
        type: "POST",
        data: { action: "updateSingleSED", studentID: studentID, state: newState },
        success: function (response) {
            if (response.success) {
                if (newState) {
                    icon.removeClass("fa-minus-square").addClass("fa-check-square");
                    button.removeClass("btn-danger").addClass("btn-success");
                } else {
                    icon.removeClass("fa-check-square").addClass("fa-minus-square");
                    button.removeClass("btn-success").addClass("btn-danger");
                }
            } else {
                alert("ERROR: Error al actualizar el estado SED.");
            }
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || "Error al procesar la solicitud";
            displayMessage($(".sectionsAFI"), errorMsg, "error");
        },
    });
});

$(".sendEmail").on("click", function () {
    let studentID = $(this).data("student-id");
    let divError = $(".sectionsSED");
    let buttonEmail = $(this);
    let buttonStatus = $(".changeSED");

    console.log(studentID);

    $.ajax({
        url: "",
        type: "POST",
        data: { action: "sendEmail", studentID: studentID },
        beforeSend: function () {
            $(".alert").remove();
            buttonEmail.prop("disabled", true);
            buttonStatus.prop("disabled", true);
        },
        success: function (response) {
            if (!response.success || !response.data.delivered) {
                displayMessage(
                    divError,
                    response.message ?? "No se pudo mandar el correo",
                    "error"
                );
                return;
            }
            displayMessage(divError, "Correo enviado correctamente: " + response.data.receipt);
            divError[0].scrollIntoView({
                behavior: "smooth",
                block: "start",
                inline: "nearest",
            });
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || "Error al procesar la solicitud";
            displayMessage($(".sectionsAFI"), errorMsg, "error");
        },
        complete: function () {
            buttonEmail.prop("disabled", false);
            buttonStatus.prop("disable", false);
        },
    });
});

$(".sendEmail").on("click", function () {
    let studentID = $(this).data("student-id");
    let divError = $(".sectionsSED");
    let buttonEmail = $(this);
    let buttonStatus = $(".changeSED");

    console.log(studentID);

    $.ajax({
        url: "",
        type: "POST",
        data: { action: "sendEmail", studentID: studentID },
        beforeSend: function () {
            $(".alert").remove();
            buttonEmail.prop("disabled", true);
            buttonStatus.prop("disabled", true);
        },
        success: function (response) {
            if (!response.success || !response.data.delivered) {
                displayMessage(
                    divError,
                    response.message ?? "No se pudo mandar el correo",
                    "error"
                );
                return;
            }
            displayMessage(divError, "Correo enviado correctamente: " + response.data.receipt);
            divError[0].scrollIntoView({
                behavior: "smooth",
                block: "start",
                inline: "nearest",
            });
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || "Error al procesar la solicitud";
            displayMessage($(".sectionsAFI"), errorMsg, "error");
        },
        complete: function () {
            buttonEmail.prop("disabled", false);
            buttonStatus.prop("disable", false);
        },
    });
});

$(".sendEmail").on("click", function () {
    let studentID = $(this).data("student-id");
    let divError = $(".sectionsSED");
    let buttonEmail = $(this);
    let buttonStatus = $(".changeSED");

    console.log(studentID);

    $.ajax({
        url: "",
        type: "POST",
        data: { action: "sendEmail", studentID: studentID },
        beforeSend: function () {
            $(".alert").remove();
            buttonEmail.prop("disabled", true);
            buttonStatus.prop("disabled", true);
        },
        success: function (response) {
            if (!response.success || !response.data.delivered) {
                displayMessage(
                    divError,
                    response.message ?? "No se pudo mandar el correo",
                    "error"
                );
                return;
            }
            displayMessage(divError, "Correo enviado correctamente: " + response.data.receipt);
            divError[0].scrollIntoView({
                behavior: "smooth",
                block: "start",
                inline: "nearest",
            });
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || "Error al procesar la solicitud";
            displayMessage($(".sectionsAFI"), errorMsg, "error");
        },
        complete: function () {
            buttonEmail.prop("disabled", false);
            buttonStatus.prop("disable", false);
        },
    });
});

$("#generateReport").on("click", function () {
    let allStudents = [];
    let filename = $(this).data("filename");

    $("#studentsTable tbody tr").each(function () {
        let studentID = $(this).find("td").eq(1).text(); // Clave ULSA
        let fullName = $(this).find("td").eq(2).text(); // Nombre Completo
        let email = $(this).find("td").eq(3).text(); // Correo
        let sedStatus = $(this).find(".changeSED i").hasClass("fa-check-square") ? true : false; // Estatus SED
        let carrer = $(this).data("carrer"); // Carrera (programa de maestría o especialidad)

        fullName = fullName.replace(/(?:^|\s)\S/g, (match) => match.toUpperCase());

        let student = {
            id: studentID,
            fullName: fullName,
            email: email,
            sedStatus: sedStatus,
            carrer: carrer,
        };

        allStudents.push(student);
    });

    //console.log(allStudents);

    $.ajax({
        url: "generate_report.php",
        type: "POST",
        data: { students: JSON.stringify(allStudents), filename: filename },
        success: function (response) {
            const result = JSON.parse(response);
            const fileUrl = result.url;

            window.open(fileUrl, "_blank");
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || "Error al procesar la solicitud";
            displayMessage($(".sectionsSED"), errorMsg, "error");
        },
    });
});
