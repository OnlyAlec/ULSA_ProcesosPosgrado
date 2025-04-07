$(function () {
    setupBtns("btn-forms");
    setupBtns("btn-forms-msf");
    setupBtns("btn-forms-lst");

    $(".formsForm").on("submit", function (e) {
        e.preventDefault();
        const form = $(this);
        // @ts-ignore
        const formData = new FormData(this);
        const tableContainer = $("#forms-result");
        const tableBody = $("#tableStudents").find("tbody");

        $.ajax({
            url: "",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                tableContainer.hide();
                $(".alert").remove();
                tableBody.empty();
                form.find("button").prop("disabled", true);
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(form, response.message, "error");
                    return;
                }

                displayMessage($(".subSectionAFI:visible"), "Archivo procesado correctamente");
                if (response.data && response.data.students && response.data.students.length > 0) {
                    // @ts-ignore
                    response.data.students.forEach((student) => {
                        const row = `<tr>
                                <th scope='row'>${student.ulsaID}</th>
                                <td>${student.firstName} ${student.lastName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                            </tr>`;
                        tableBody.append(row);
                    });
                    if (response.data.excel) {
                        const downloadLink = $("#downloadExcel");
                        downloadLink.attr("href", response.data.excel);
                        downloadLink.show();
                    }
                    if (response.data.totalDB && response.data.totalFiltered) {
                        $("#totalDB").text(response.data.totalDB);
                        $("#totalFiltered").text(response.data.totalFiltered);
                    }
                    if (response.data.graphData) {
                        generateCharts(response.data.graphData);
                    }
                } else
                    tableBody.append(
                        '<tr><td colspan="4" class="text-center">No se encontraron alumnos faltantes</td></tr>'
                    );
                tableContainer.show();
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

    $("#selectMaster, #selectSpecialty").on("input", function () {
        const value = String($(this).val())?.toUpperCase();
        const icon = $(this).closest(".datalist").find("i");

        value
            ? icon.removeClass("fa-search").addClass("fa-times")
            : icon.removeClass("fa-times").addClass("fa-search");
        const otherSelect = $(this).is("#selectMaster") ? "#selectSpecialty" : "#selectMaster";
        $(otherSelect).val("");
        $(otherSelect).closest(".datalist").find("i").removeClass("fa-times").addClass("fa-search");

        filterTableByCarrer(value, "tableStudents");
    });
});

/**
 * @param {{ especialidad: { [s: string]: any; } | ArrayLike<any>; maestria: { [s: string]: any; } | ArrayLike<any>; }} graphData
 */
function generateCharts(graphData) {
    const especialidadLabels = Object.keys(graphData.especialidad);
    const especialidadValues = Object.values(graphData.especialidad);

    const maestriaLabels = Object.keys(graphData.maestria);
    const maestriaValues = Object.values(graphData.maestria);

    $("#especialidadGraph").remove();
    $("#maestriaGraph").remove();
    $("#especialidadTitle").after('<canvas id="especialidadGraph"></canvas>');
    $("#maestriaTitle").after('<canvas id="maestriaGraph"></canvas>');

    /* --> Especialidades */
    // @ts-ignore
    new Chart(document.getElementById("especialidadGraph"), {
        type: "bar",
        data: {
            labels: especialidadLabels,
            datasets: [
                {
                    label: "Alumnos sin firmar",
                    data: especialidadValues,
                    backgroundColor: "rgba(255, 99, 132, 0.5)",
                    borderColor: "rgba(255, 99, 132, 1)",
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                x: { title: { display: true, text: "Programas" } },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: "Cantidad de alumnos sin firmar" },
                },
            },
        },
    });

    /* --> Maestrías */
    // @ts-ignore
    new Chart(document.getElementById("maestriaGraph"), {
        type: "bar",
        data: {
            labels: maestriaLabels,
            datasets: [
                {
                    label: "Alumnos sin firmar",
                    data: maestriaValues,
                    backgroundColor: "rgba(54, 162, 235, 0.5)",
                    borderColor: "rgba(54, 162, 235, 1)",
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                x: { title: { display: true, text: "Programas" } },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: "Cantidad de alumnos sin firmar" },
                },
            },
        },
    });
}
