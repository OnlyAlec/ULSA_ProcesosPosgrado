$(function () {
    setupBtns('btn-gestor');

    $('#btn-gestor').on('click', function () {
        const button = $(this);
        const divError = $('.sectionsAFI');
        const tableContainer = $('#tableStudentsConfirm');
        const tableBody = tableContainer.find('tbody');

        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getTableStudents' },
            beforeSend: function () {
                button.prop('disabled', true);
                tableContainer.hide();
                tableBody.empty();
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(divError, response.message, 'error');
                    return;
                }

                if (response.data) {
                    // @ts-ignore
                    response.data.forEach((student) => {
                        let row = `<tr data-carrer="${student.carrer}">
                                <th scope='row'>${student.ulsaID}</th>
                                <td>${student.firstName} ${student.lastName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                                <td>
                                    <div class="d-flex" style="gap: 8px;">
                                        <button class="btn ??? btn-sm text-white border-0 flex-fill statusAFI" data-ulsaID="${student.ulsaID}">
                                            ###
                                        </button>
                                        ~~~
                                    </div>
                                </td>
                            </tr>`;
                        const afiStatusStyle = student.afi ? 'btn-danger' : 'btn-success';
                        const afiStatusIcon = student.afi
                            ? `<i class="fas fa-minus-square fa-lg"></i>`
                            : `<i class="fas fa-check-square fa-lg"></i>`;
                        if (!student.afi)
                            row = row.replace(
                                '~~~',
                                `<button class="btn btn-info btn-sm text-white border-0 flex-fill sendEmail" data-email="${student.email}"><i class="fas fa-paper-plane"></i>`
                            );
                        else row = row.replace('~~~', '');

                        row = row.replace('???', afiStatusStyle);
                        row = row.replace('###', afiStatusIcon);
                        tableBody.append(row);
                    });
                    setupActions();
                } else {
                    tableBody.append(
                        '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
                    );
                }
                tableContainer.show();
            },
            error: function (xhr) {
                const errorMsg = 'Error al procesar la solicitud';
                displayMessage(divError, errorMsg, 'error');
            },
            complete: function () {
                button.prop('disabled', false);
            },
        });
    });

    $('#generateReport_AFI').on('click', function () {
        let allStudents = [];
        let filename = $(this).data('filename');

        $('#tableStudentsConfirm tbody tr').each(function () {
            let studentID = $(this).find('th').text();
            let fullName = $(this).find('td').eq(0).text();
            let carrer = $(this).find('td').eq(1).text();
            let email = $(this).find('td').eq(2).text();
            let afiStatus = $(this).find('.statusAFI i').hasClass('fa-minus-square');

            let student = {
                id: studentID,
                fullName: fullName,
                carrer: carrer,
                email: email,
                afiStatus: afiStatus,
            };

            allStudents.push(student);
        });

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                action: 'generateReport',
                students: JSON.stringify(allStudents),
                statusField: 'afiStatus',
                filename: filename,
            },
            success: function () {
                const publicUrl = `/assets/pdf/${filename}.pdf?t=${Date.now()}`;
                window.open(publicUrl, '_blank');
            },
            error: function (xhr) {
                const errorMsg =
                    xhr.responseText || 'Error al procesar la solicitud del Reporte de Avisos';
                displayMessage($('.sectionsAFI'), errorMsg, 'error');
            },
        });
    });
});

function setupActions() {
    $('.statusAFI')
        .off('click')
        .on('click', function () {
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
                    const newIcon = newStatus
                        ? `<i class="fas fa-minus-square fa-lg"></i>`
                        : `<i class="fas fa-check-square fa-lg"></i>`;
                    const newColor = newStatus ? 'btn-danger' : 'btn-success';

                    button.html(newIcon);
                    button.removeClass('btn-success btn-danger');
                    button.addClass(newColor);

                    if (newStatus) button.parent().parent().find('.sendEmail').remove();
                    else
                        button
                            .parent()
                            .append(
                                `<button class="btn btn-info btn-sm text-white border-0 flex-fill sendEmail" data-email=${response.data.email}><i class= "fas fa-paper-plane"></i></button>`
                            );
                    setupActions();
                },
                error: function (xhr) {
                    const errorMsg = 'Error al procesar la solicitud';
                    displayMessage(divError, errorMsg, 'error');
                },
                complete: function () {
                    button.prop('disabled', false);
                },
            });
        });

    $('.sendEmail')
        .off('click')
        .on('click', function () {
            const button = $(this);
            const buttonConfirm = button.parent().find('.statusAFI');
            const ulsaID = button.data('ulsaid');
            const divError = $('.sectionsAFI');

            $.ajax({
                url: '',
                type: 'POST',
                data: { action: 'sendEmail', ulsaID: ulsaID },
                beforeSend: function () {
                    $('.alert').remove();
                    button.prop('disabled', true);
                    buttonConfirm.prop('disabled', true);
                },
                success: function (response) {
                    if (!response.success || !response.data.delivered) {
                        displayMessage(
                            divError,
                            response.message ?? 'No se pudo mandar el correo',
                            'error'
                        );
                        return;
                    }
                    displayMessage(
                        divError,
                        'Correo enviado correctamente: ' + response.data.receipt
                    );
                    divError[0].scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                        inline: 'nearest',
                    });
                },
                error: function (xhr) {
                    const errorMsg = 'Error al procesar la solicitud';
                    displayMessage(divError, errorMsg, 'error');
                },
                complete: function () {
                    button.prop('disabled', false);
                    buttonConfirm.prop('disabled', false);
                },
            });
        });
}