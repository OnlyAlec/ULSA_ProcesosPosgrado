$(function () {
    function generateCharts(graphData) {
        /**
         * @param {string} canvasId
         * @param {string} titleId
         * @param {string[]} labels
         * @param {any[]} values
         * @param {string} label
         * @param {string} backgroundColor
         * @param {string} borderColor
         */
        function createChart(
            canvasId,
            titleId,
            labels,
            values,
            chartLabel,
            backgroundColor,
            borderColor
        ) {
            $(`#${canvasId}`).remove();
            $(`#${titleId}`).after(`<canvas id="${canvasId}"></canvas>`);
            // @ts-ignore
            new Chart(document.getElementById(canvasId), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: chartLabel,
                            data: values,
                            backgroundColor: backgroundColor,
                            borderColor: borderColor,
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { title: { display: true, text: 'Programas' } },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Cantidad de alumnos sin firmar' },
                        },
                    },
                },
            });
        }

        createChart(
            'especialidadGraph',
            'especialidadTitle',
            Object.keys(graphData.especialidad),
            Object.values(graphData.especialidad),
            'Alumnos sin firmar',
            'rgba(255, 99, 132, 0.5)',
            'rgba(255, 99, 132, 1)'
        );

        createChart(
            'maestriaGraph',
            'maestriaTitle',
            Object.keys(graphData.maestria),
            Object.values(graphData.maestria),
            'Alumnos sin firmar',
            'rgba(54, 162, 235, 0.5)',
            'rgba(54, 162, 235, 1)'
        );
    }
    setupBtns('btn-forms');

    $('.formsForm').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        // @ts-ignore
        const formData = new FormData(this);
        const tableContainer = $('#forms-result');
        const tableBody = $('#tableStudents').find('tbody');

        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                tableContainer.hide();
                $('.alert').remove();
                tableBody.empty();
                form.find('button').prop('disabled', true);
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(form, response.message, 'error');
                    return;
                }

                displayMessage($('.subSectionAFI:visible'), 'Archivo procesado correctamente');
                if (response.data && response.data.students && response.data.students.length > 0) {
                    // @ts-ignore
                    response.data.students.forEach((student) => {
                        const row = `<tr data-carrer="${student.carrer}">
                                <th scope='row'>${student.ulsaID}</th>
                                <td>${student.firstName} ${student.lastName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                            </tr>`;
                        tableBody.append(row);
                    });
                    if (response.data.excel) {
                        const downloadLink = $('#downloadExcel');
                        downloadLink.attr('href', response.data.excel);
                        downloadLink.show();
                    }
                    if (response.data.totalDB && response.data.totalFiltered) {
                        $('#totalDB').text(response.data.totalDB);
                        $('#totalFiltered').text(response.data.totalFiltered);
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
                const errorMsg = 'Error al procesar la solicitud';
                displayMessage(form, errorMsg, 'error');
            },
            complete: function () {
                form.find('button').prop('disabled', false);
            },
        });
    });
});
