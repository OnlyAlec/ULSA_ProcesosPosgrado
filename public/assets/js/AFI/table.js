$(function () {
    function initDatalist($container) {
        // *Al hacer clic en el input se muestra/oculta la lista
        $container.find('.datalist-input').on('click', function (e) {
            e.stopPropagation();
            $(this).siblings('ul').toggle();
        });

        // *Al hacer clic fuera se cierra la lista
        $(document).on('click', function (e) {
            if (!$(e.target).closest($container).length) {
                $container.find('ul').hide();
            }
        });

        // *Seleccionar una opción de la lista
        $container.find('ul').on('click', 'li:not(.not-selectable)', function (e) {
            e.stopPropagation();
            const $d = $(this).closest('.datalist');
            const $input = $d.find('.datalist-input');
            const selectedText = $(this).text();
            const selectedAction = $(this).data('value');

            $input.val(selectedText).data('value', selectedAction);
            $input.trigger('input');
            $d.find('ul').hide();
            $d.find('.filter').removeClass('fa-search').addClass('fa-times');
        });

        // *Funcionalidad del ícono (limpiar o abrir la lista)
        $container.find('.filter').on('click', function (e) {
            e.stopPropagation();
            const $icon = $(this);
            const $d = $icon.closest('.datalist');
            const $input = $d.find('.datalist-input');
            if ($icon.hasClass('fa-times')) {
                $input.val('').data('value', '');
                $input.trigger('input');
                $icon.removeClass('fa-times').addClass('fa-search');
            } else {
                $d.find('ul').toggle();
            }
        });
    }

    function resetTableAndSelection(tableID = 'tableStudents') {
        const $table = $('#' + tableID);
        const $tbody = $table.find('tbody');
        $tbody.find('tr').show();
        $('.studentCheckbox').prop('checked', false);
        $('#confirmChanges').prop('disabled', true);
        $('#selectedCount').text('0');
        $('#selectAll').prop('checked', false);
    }

    function applyFiltersAreaType(
        tableID = 'tableStudents',
        filterType = 'programType',
        filterArea = 'programArea'
    ) {
        const tBody = $('#' + tableID)?.find('tbody');
        tBody.find('.noResults').remove();
        const rows = tBody.find('tr');

        let selectedType = '';
        const typeValue = $('#' + filterType)?.data('value');
        if (typeValue == 'masters') {
            selectedType = 'MAESTRÍA';
        } else if (typeValue == 'specialties') {
            selectedType = 'ESPECIALIDAD';
        }

        const selectedArea =
            $('#' + filterArea)
                ?.data('value')
                ?.toUpperCase() || '';

        if (rows.length === 0) {
            return;
        }

        rows.each(function () {
            const row = $(this);
            const area = row.data('carrer').toUpperCase();
            const type = area.split(' ')[0].toUpperCase();

            if (selectedType === '') {
                rows.show();
            } else {
                if (selectedArea === '') {
                    if (type === selectedType) {
                        row.show();
                    } else {
                        row.hide();
                    }
                } else {
                    if (area === selectedArea) {
                        row.show();
                    } else {
                        row.hide();
                    }
                }
            }
        });

        if (tBody.find('tr:visible').length == 0)
            tBody.append(
                '<tr class="noResults"><td colspan="6" class="text-center">No se encontraron alumnos</td></tr>'
            );
    }

    function handleProgramTypeChange(typeSelector, areaSelectors, tableId) {
        $(typeSelector).on('input', function () {
            const selectedOption = $(this).data('value') || '';
            resetTableAndSelection(tableId);

            // Seleccionar elementos del área y limpiar la lista de opciones
            const $datalistArea = $(areaSelectors).closest('.datalist');
            const $ulArea = $datalistArea.find('ul');
            $ulArea.empty();
            $ulArea.append('<li data-value="">Todos</li>');

            // Determinar programas a mostrar según la selección
            let programsToShow = [];
            if (selectedOption === 'masters') {
                programsToShow = window.afiPreloadedData.masters || [];
            } else if (selectedOption === 'specialties') {
                programsToShow = window.afiPreloadedData.specialties || [];
            } else if (selectedOption === '') {
                programsToShow = window.afiPreloadedData.masters.concat(
                    window.afiPreloadedData.specialties
                );
            }

            // Agregar opciones a la lista
            if (programsToShow.length > 0) {
                programsToShow.forEach((element) => {
                    $ulArea.append(`<li data-value="${element}">${element}</li>`);
                });
            }

            // Actualizar UI según la selección
            if (selectedOption !== '') {
                $(areaSelectors).val('Todos').data('value', '');
                $(areaSelectors).siblings('.filter').removeClass('fa-times').addClass('fa-search');
                $(areaSelectors).trigger('input');

                $('#filterArea, #filterAreaGestor').show();
            } else {
                $('#filterArea, #filterAreaGestor').hide();
            }
        });
    }

    initDatalist($('#programType, #programArea').closest('.datalist'));
    initDatalist($('#programTypeGestor, #programAreaGestor').closest('.datalist'));

    handleProgramTypeChange('#programType', '#programArea', 'tableStudents');
    handleProgramTypeChange('#programTypeGestor', '#programAreaGestor', 'tableStudentsConfirm');

    $('#programArea').on('input', function () {
        resetTableAndSelection();
        applyFiltersAreaType();
    });

    $('#programAreaGestor').on('input', function () {
        resetTableAndSelection('tableStudentsConfirm');
        applyFiltersAreaType('tableStudentsConfirm', 'programTypeGestor', 'programAreaGestor');
    });

    $('.studentCheckbox').on('change', function () {
        const checked = $(this).is(':checked');
        const row = $(this).closest('tr');

        const selectedCount = $('.studentCheckbox:checked').length;
        $('#selectedCount').text(selectedCount);

        if (checked) {
            row.addClass('selected');
            $('#confirmChanges').prop('disabled', false);
        } else {
            row.removeClass('selected');
            if (selectedCount === 0) {
                $('#confirmChanges').prop('disabled', true);
            }
        }
    });

    $('#selectAll').on('change', function () {
        const checked = $(this).is(':checked');
        $('.studentCheckbox:visible').prop('checked', checked);
        $('.studentCheckbox:visible').trigger('change');
    });

    $('#confirmChanges').on('click', function () {
        let selectedStudents = [];

        $('.studentCheckbox:checked').each(function () {
            let studentID = $(this).closest('tr').find('.changeSED').data('student-id');
            if (studentID) selectedStudents.push(studentID);
        });

        if (selectedStudents.length === 0) {
            alert('Selecciona al menos a un estudiante.');
            return;
        }

        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'updateSED', studentIDS: selectedStudents },
            success: function (response) {
                if (response.success) {
                    alert('EXITO: El estatus SED de los alumnos ha sido actualizado.');

                    $('.studentCheckbox:checked').each(function () {
                        let icon = $(this).closest('tr').find('.changeSED i');
                        let button = icon.closest('button');
                        icon.removeClass('fa-check-square').addClass('fa-minus-square');
                        button.removeClass('btn-success').addClass('btn-danger');
                    });

                    $('#programType').val('');
                    $('#programArea').val('');
                    $('#filterArea').hide();

                    $('.studentCheckbox').prop('checked', false);
                    $('tr').removeClass('selected');
                    $('#confirmChanges').prop('disabled', true);
                    $('#selectedCount').text('0');
                    $('#selectAll').prop('checked', false);
                } else {
                    alert('ERROR: Error al actualizar el estatus SED de los alumnos.');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
                displayMessage($('.sectionsAFI'), errorMsg, 'error');
            },
        });
    });

    $('.changeSED').on('click', function () {
        let icon = $(this).find('i');
        let button = icon.closest('button');
        let studentID = $(this).data('student-id');
        let newState = icon.hasClass('fa-check-square') ? 1 : 0;

        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'updateSingleSED', studentID: studentID, state: newState },
            success: function (response) {
                if (response.success) {
                    if (newState) {
                        icon.removeClass('fa-check-square').addClass('fa-minus-square');
                        button.removeClass('btn-success').addClass('btn-danger');
                    } else {
                        icon.removeClass('fa-minus-square').addClass('fa-check-square');
                        button.removeClass('btn-danger').addClass('btn-success');
                    }
                } else {
                    alert('ERROR: Error al actualizar el estado SED.');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
                displayMessage($('.sectionsAFI'), errorMsg, 'error');
            },
        });
    });

    $('.sendEmail').on('click', function () {
        let studentID = $(this).data('student-id');
        let divError = $('.sectionsSED');
        let buttonEmail = $(this);
        let buttonStatus = $('.changeSED');

        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'sendEmail', studentID: studentID },
            beforeSend: function () {
                $('.alert').remove();
                buttonEmail.prop('disabled', true);
                buttonStatus.prop('disabled', true);
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
                displayMessage(divError, 'Correo enviado correctamente: ' + response.data.receipt);
                divError[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                    inline: 'nearest',
                });
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
                displayMessage($('.sectionsAFI'), errorMsg, 'error');
            },
            complete: function () {
                buttonEmail.prop('disabled', false);
                buttonStatus.prop('disable', false);
            },
        });
    });

    $('#generateReport').on('click', function () {
        let allStudents = [];
        let filename = $(this).data('filename');

        $('#studentsTable tbody tr').each(function () {
            let studentID = $(this).find('td').eq(1).text();
            let fullName = $(this).find('td').eq(2).text();
            let email = $(this).find('td').eq(3).text();
            let sedStatus = $(this).find('.changeSED i').hasClass('fa-minus-square') ? true : false;
            let carrer = $(this).data('carrer');

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

        $.ajax({
            url: 'generate_report.php',
            type: 'POST',
            data: { students: JSON.stringify(allStudents), filename: filename },
            success: function (response) {
                const result = JSON.parse(response);
                const fileUrl = result.url;

                window.open(fileUrl, '_blank');
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
                displayMessage($('.sectionsSED'), errorMsg, 'error');
            },
        });
    });

    $('#onlyMissing').on('click', function () {
        const tableBody = $('#tableStudentsConfirm').find('tbody');
        resetTableAndSelection('tableStudentsConfirm');
        applyFiltersAreaType('tableStudentsConfirm', 'programTypeGestor', 'programAreaGestor');

        tableBody
            .find('tr:visible')
            .filter(function () {
                return $(this).find('i').hasClass('fa-minus-square');
            })
            .hide();

        if (tableBody.find('tr:visible').length == 0)
            tableBody.append(
                '<tr class="noResults"><td colspan="6" class="text-center">No se encontraron alumnos</td></tr>'
            );
    });

    $('#onlyConfirm').on('click', function () {
        const tableBody = $('#tableStudentsConfirm').find('tbody');
        resetTableAndSelection();
        applyFiltersAreaType('tableStudentsConfirm', 'programTypeGestor', 'programAreaGestor');

        tableBody
            .find('tr:visible')
            .filter(function () {
                return $(this).find('i').hasClass('fa-check-square');
            })
            .hide();

        if (tableBody.find('tr:visible').length == 0)
            tableBody.append(
                '<tr class="noResults"><td colspan="6" class="text-center">No se encontraron alumnos</td></tr>'
            );
    });

    $('#removeFilter').on('click', function () {
        resetTableAndSelection('tableStudentsConfirm');
        applyFiltersAreaType('tableStudentsConfirm', 'programTypeGestor', 'programAreaGestor');
    });
});
