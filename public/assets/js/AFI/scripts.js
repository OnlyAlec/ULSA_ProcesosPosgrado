$(document).ready(function () {
    $('form').submit(function (e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);

        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $('.alert').remove();
                form.find('button').prop('disabled', true);
            },
            success: function (response) {
                displayMessage(form, "Archivos procesados correctamente");
                if (response.success) {
                    $('#missingStudents').empty();

                    if (response.data && response.data.students && response.data.students.length > 0) {
                        response.data.students.forEach(student => {
                            const fullName = `${student.firstName} ${student.paternalSurname} ${student.maternalSurname}`;
                            const row = `<tr>
                                <td>${fullName}</td>
                                <td>${student.typeDesc}</td>
                                <td>${student.area}</td>
                                <td>${student.email}</td>
                            </tr>`;
                            $('#missingStudents').append(row);
                        });

                        const tableContainer = $('div[style="display:none"]');
                        tableContainer.show();

                        if (response.data.excel) {
                            const downloadLink = `<div class="mt-3">
                                <a href="${response.data.excel}" class="btn btn-success" download>
                                    <i class="fas fa-download"></i> Descargar Excel
                                </a>
                            </div>`;
                            $("#tableStudents").after(downloadLink);
                        }

                        if (response.data.totalDB && response.data.totalFiltered) {
                            $('#totalDB').text(response.data.totalDB)
                            $('#totalFiltered').text(response.data.totalFiltered)
                        }

                        /*Generate graphs*/
                        if(response.data.graphData){
                            generateCharts(response.data.graphData);
                        }

                    } else {
                        // No students found
                        $('#missingStudents').append('<tr><td colspan="4" class="text-center">No se encontraron alumnos faltantes</td></tr>');
                    }
                } else {
                    displayMessage(form, response.message, 'error');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
                displayMessage(form, errorMsg, 'error');
            },
            complete: function () {
                form.find('button').prop('disabled', false);
            }
        });
    });

    function displayMessage(pos, message, type = 'success') {
        const newDiv = document.createElement('div');
        newDiv.className = type == 'success' ? 'alert alert-success' : 'alert alert-danger';
        newDiv.innerHTML = message;
        pos.before(newDiv);
    }
});

function generateCharts (graphData) {
    const especialidadLabels = Object.keys(graphData.especialidad); 
    const especialidadValues = Object.values(graphData.especialidad);

    const maestriaLabels = Object.keys(graphData.maestria);
    const maestriaValues = Object.values(graphData.maestria);

    $('#especialidadGraph').remove(); 
    $('#maestriaGraph').remove();
    $('#especialidadTitle').after('<canvas id="especialidadGraph"></canvas>'); 
    $('#maestriaTitle').after('<canvas id="maestriaGraph"></canvas>');

    /* --> Especialidades */
    new Chart(document.getElementById("especialidadGraph"), {
        type: "bar",
        data: {
            labels: especialidadLabels,
            datasets: [{
                label: "Alumnos sin firmar",
                data: especialidadValues,
                backgroundColor: "rgba(255, 99, 132, 0.5)",
                borderColor: "rgba(255, 99, 132, 1)",
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { 
                x: { title: { display: true, text: "Programas" }, }, 
                y: { beginAtZero: true, title: { display: true, text: "Cantidad de alumnos sin firmar" } } 
            }
        }
    });

    /* --> Maestrías */
    new Chart(document.getElementById("maestriaGraph"), {
        type: "bar",
        data: {
            labels: maestriaLabels,
            datasets: [{
                label: "Alumnos sin firmar",
                data: maestriaValues,
                backgroundColor: "rgba(54, 162, 235, 0.5)",
                borderColor: "rgba(54, 162, 235, 1)",
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { 
                x: { title: { display: true, text: "Programas" } }, 
                y: { beginAtZero: true, title: { display: true, text: "Cantidad de alumnos sin firmar" } } 
            }
        }
    });
}