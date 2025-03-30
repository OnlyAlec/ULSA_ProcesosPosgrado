$(function () {
    setupBtns("btn-forms");
    setupBtns("btn-forms-msf");
    setupBtns("btn-forms-lst");

    $(".formsForm").on("submit", function (e) {
        e.preventDefault();
        const form = $(this);
        // @ts-ignore
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
                if (!response.success) {
                    displayMessage(form, response.message, "error");
                    return;
                }

                displayMessage(form, "Archivos procesados correctamente");
                $("#missingStudents").empty();

                if (response.data && response.data.students && response.data.students.length > 0) {
                    const tableContainer = $('div[style="display:none"]');
                    // @ts-ignore
                    response.data.students.forEach((student) => {
                        const fullName = `${student.firstName} ${student.lastName}`;
                        const row = `<tr>
                                <td>${student.ulsaID}</td>
                                <td>${fullName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                            </tr>`;
                        $("#missingStudents").append(row);
                    });
                    tableContainer.show();
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
                } else {
                    // No students found
                    $("#missingStudents").append(
                        '<tr><td colspan="4" class="text-center">No se encontraron alumnos faltantes</td></tr>'
                    );
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

    $("#selectMaster, #selectSpecialty").on("change", function () {
        // @ts-ignore
        const selectedOption = $(this).val().toUpperCase();
        if (this.id === "selectMaster") {
            $("#selectSpecialty").val("all");
        } else {
            $("#selectMaster").val("all");
        }
        // @ts-ignore
        filterTable(selectedOption, "tableStudents");
    });
});

// @ts-ignore
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
