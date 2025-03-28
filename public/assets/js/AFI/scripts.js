$(document).ready(function () {
    setupBtns("btn-forms");
    setupBtns("btn-forms-msf");
    setupBtns("btn-forms-lst");

    setupBtns("btn-gestor");
    setupBtns("btn-config");

    $('form').on("submit", function (e) {
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
                if (!response.success) {
                    displayMessage(form, response.message, 'error');
                    return;
                }

                displayMessage(form, "Archivos procesados correctamente");
                $('#missingStudents').empty();

                if (response.data && response.data.students && response.data.students.length > 0) {
                    const tableContainer = $('div[style="display:none"]');
                    response.data.students.forEach(student => {
                        const fullName = `${student.firstName} ${student.lastName}`;
                        const row = `<tr>
                                <td>${student.ulsaID}</td>
                                <td>${fullName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                            </tr>`;
                        $('#missingStudents').append(row);
                    });
                    tableContainer.show();
                    if (response.data.excel) {
                        const downloadLink = $('#downloadExcel');
                        downloadLink.attr('href', response.data.excel);
                        downloadLink.show();
                    }
                    if (response.data.totalDB && response.data.totalFiltered) {
                        $('#totalDB').text(response.data.totalDB)
                        $('#totalFiltered').text(response.data.totalFiltered)
                    }
                    if (response.data.graphData) {
                        generateCharts(response.data.graphData);
                    }
                } else {
                    // No students found
                    $('#missingStudents').append('<tr><td colspan="4" class="text-center">No se encontraron alumnos faltantes</td></tr>');
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

    $('#selectMaster, #selectSpecialty').on("change", function () {
        const selectedOption = $(this).val().toUpperCase();
        if (this.id === "selectMaster") {
            $('#selectSpecialty').val("all");
        } else {
            $('#selectMaster').val("all");
        }
        filterTable(selectedOption, "tableStudents");
    });

    $('#btn-gestor').on("click", function () {
        const button = $(this);
        const divError = $('.sectionsAFI');
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'showMissingStudentsAFI' },
            beforeSend: function () {
                button.prop('disabled', true);
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(divError, response.message, 'error');
                    return;
                }

                $('#missingStudentsConfirm').empty();
                if (response.data && response.data.length > 0) {
                    const tableContainer = $('#tableStudentsConfirm');
                    response.data.forEach(student => {
                        const fullName = `${student.firstName} ${student.lastName}`;
                        let row = `<tr>
                                <td>${student.ulsaID}</td>
                                <td>${fullName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                                <td class="row">
                                    <button class="col btn ??? btn-sm text-white statusAFI" data-ulsaID=${student.ulsaID}>
                                        ###
                                    </button>
                                    ~~~
                            </tr>`;
                        const afiStatusBtn = student.afi ? '<i class="fas fa-minus-square"></i>' : '<i class="fas fa-check-square"></i>';
                        const afiStatusColor = student.afi ? 'btn-danger' : 'btn-success';
                        if (!student.afi)
                            row = row.replace('~~~', `<button class="col btn btn-info btn-sm text-white sendEmail" data-email=${student.email}><i class= "fas fa-paper-plane"></i></button>`);
                        else
                            row = row.replace('~~~', '');

                        row = row.replace('###', afiStatusBtn);
                        row = row.replace('???', afiStatusColor);
                        $('#missingStudentsConfirm').append(row);
                    });
                    setupActions();
                    tableContainer.show();
                } else {
                    $('#missingStudentsConfirm').append('<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
                displayMessage(divError, errorMsg, 'error');
            },
            complete: function () {
                button.prop('disabled', false);
            }
        });
    });

    $('#selectMasterConfirm, #selectSpecialtyConfirm').on("change", function () {
        const selectedOption = $(this).val().toUpperCase();
        if (this.id === "selectMasterConfirm") {
            $('#selectSpecialtyConfirm').val("all");
        } else {
            $('#selectMasterConfirm').val("all");
        }
        filterTable(selectedOption, "tableStudentsConfirm");
    });
});

function displayMessage(pos, message, type = 'success') {
    const newDiv = document.createElement('div');
    newDiv.className = type == 'success' ? 'alert alert-success my-3' : 'alert alert-danger my-3';
    newDiv.innerHTML = message;
    pos.before(newDiv);
}

function setupBtns(name) {
    if (name == "" || name == undefined) {
        throw new Error("Missing name - setupBtns");
    }

    $('#' + name).on("click", function () {
        $('.alert').remove();
        $('.sectionsAFI button').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        const div = name.split("-").slice(1).join("-");
        if (name.split("-").length <= 2)
            hideSections();
        else
            hideSections(true);
        $('#' + div).show();
    });
}

function setupActions() {
    $('.statusAFI').on("click", function () {
        const btn = $(this);
        const ulsaID = btn.data('ulsaID');
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'updateStatusAFI', ulsaID: ulsaID },
        })
    })

    $('.sendEmail').on("click", function () {
        const btn = $(this);
        const email = btn.data('email');
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'sendEmailAFI', email: email },
        })
    })
}

function hideSections(subsection = false) {
    const className = subsection ? ".subSectionAFI" : ".sectionAFI";
    $(className).each(function () {
        $(this).hide();
    });
}

function generateCharts(graphData) {
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

function filterTable(filter, tableName) {
    const table = document.getElementById(tableName);
    const rows = table.getElementsByTagName("tr");

    if (filter === "ALL") {
        Array.from(rows).forEach(row => row.style.display = "");
        return;
    }

    Array.from(rows).forEach(row => {
        const cell = row.getElementsByTagName("td")[2];
        if (cell) {
            const txtValue = cell.textContent || cell.innerText;
            row.style.display = txtValue.toUpperCase().includes(filter) ? "" : "none";
        }
    });
}