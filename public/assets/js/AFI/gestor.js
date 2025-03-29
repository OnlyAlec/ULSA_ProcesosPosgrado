$(document).ready(function () {
    setupBtns("btn-gestor");

    $('#btn-gestor').on("click", function () {
        const button = $(this);
        const divError = $('.sectionsAFI');
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getTableStudents' },
            beforeSend: function () {
                button.prop('disabled', true);
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(divError, response.message, 'error');
                    return;
                }
                $('#missingStudentsConfirm').empty();

                if (response.data) {
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
        filterTableByCarrer(selectedOption, "tableStudentsConfirm");
    });
    $('#onlyMissing').on("click", function () {
        const button = $(this);
        const divError = $('.sectionsAFI');
        $('#selectMasterConfirm, #selectSpecialtyConfirm').val("all");
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getMissing' },
            beforeSend: function () {
                button.prop('disabled', true);
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(divError, response.message, 'error');
                    return;
                }
                $('#missingStudentsConfirm').empty();

                if (response.data) {
                    const tableContainer = $('#tableStudentsConfirm');
                    response.data.forEach(student => {
                        const fullName = `${student.firstName} ${student.lastName}`;
                        let row = `<tr>
                                <td>${student.ulsaID}</td>
                                <td>${fullName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                                <td class="row">
                                    <button class="col btn btn-success btn-sm text-white statusAFI" data-ulsaID=${student.ulsaID}>
                                        <i class="fas fa-check-square"></i>
                                    </button>
                                    <button class="col btn btn-info btn-sm text-white sendEmail" data-email=${student.email}>
                                        <i class= "fas fa-paper-plane"></i>
                                    </button>
                                </tr>`;

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
    $('#onlyConfirm').on("click", function () {
        const button = $(this);
        const divError = $('.sectionsAFI');
        $('#selectMasterConfirm, #selectSpecialtyConfirm').val("all");
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getConfirm' },
            beforeSend: function () {
                button.prop('disabled', true);
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(divError, response.message, 'error');
                    return;
                }
                $('#missingStudentsConfirm').empty();

                if (response.data) {
                    const tableContainer = $('#tableStudentsConfirm');
                    response.data.forEach(student => {
                        const fullName = `${student.firstName} ${student.lastName}`;
                        let row = `<tr>
                                <td>${student.ulsaID}</td>
                                <td>${fullName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                                <td class="row">
                                    <button class="col btn btn-danger btn-sm text-white statusAFI" data-ulsaID=${student.ulsaID}>
                                        <i class="fas fa-minus-square"></i>
                                    </button>
                                </tr>`;

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
});

function setupActions() {
    $('.statusAFI').off('click').on("click", function () {
        const button = $(this);
        const ulsaID = button.data('ulsaid');
        const divError = $('.sectionsAFI');

        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'setStatus', ulsaID: ulsaID },
            beforeSend: function () {
                button.prop('disabled', true);
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(divError, response.message, 'error');
                    return;
                }
                const newStatus = response.data.newStatus;
                const newIcon = newStatus ? '<i class="fas fa-minus-square"></i>' : '<i class="fas fa-check-square"></i>';
                const newColor = newStatus ? 'btn-danger' : 'btn-success';
                button.html(newIcon);
                button.removeClass('btn-success btn-danger');
                button.addClass(newColor);

                if (newStatus)
                    button.parent().parent().find('.sendEmail').remove();
                else
                    button.parent().append(`<button class="col btn btn-info btn-sm text-white sendEmail" data-email=${response.data.email}><i class= "fas fa-paper-plane"></i></button>`);
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

    $('.sendEmail').off('click').on("click", function () {
        const btn = $(this);
        const email = btn.data('email');
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'sendEmail', email: email },
        })
    })
}