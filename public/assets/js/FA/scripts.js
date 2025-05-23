$(document).ready(function () {
    let currentCommentId = null;
    const commentModal = new bootstrap.Modal(document.getElementById('commentModal'));

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
                displayMessage(form, 'Acción realizada correctamente');
                console.log(response);
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

    function displayMessage(pos, message, type = 'success') {
        const newDiv = document.createElement('div');
        newDiv.className = type == 'success' ? 'alert alert-success' : 'alert alert-danger';
        newDiv.innerHTML = message;
        pos.before(newDiv);
    }

    // Cargar datos de la tabla
    loadProgramSubjects();

    function loadProgramSubjects() {
        $.ajax({
            url: '', // Actualiza con la ruta de tu script PHP
            type: 'POST',
            data: { action: 'getProgramSubjects' },
            dataType: 'json',
            success: function (response) {
                if (!response.success || !Array.isArray(response.data)) {
                    displayMessage($('.sectionsFA'), 'Ocurrió un problema', 'error');
                    return;
                }

                const tbody = $('#programsTableBody');
                tbody.empty();

                response.data.forEach((program) => {
                    const row = `<tr data-id="${program.id}">
                        <td>${program.program_name}</td>
                        <td>${program.subject_name}</td>
                        <td>${program.professor_name}</td>
                        <td>
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-sm mx-1 toggle-signed ${program.has_signed ? 'btn-success' : 'btn-danger'}" data-toggle="tooltip" title="${program.has_signed ? 'Acta firmada' : 'Acta sin firma'}">
                                    <i class="fas ${program.has_signed ? 'fa-check' : 'fa-times'}"></i>
                                </button>
                                <button class="btn btn-sm mx-1 toggle-absent ${program.will_be_absent ? 'btn-warning' : 'btn-secondary'}" data-toggle="tooltip" title="${program.will_be_absent ? 'Estará ausente' : 'Estará presente'}">
                                    <i class="fas ${program.will_be_absent ? 'fa-user-times' : 'fa-user-check'}"></i>
                                </button>
                                <button class="btn btn-sm mx-1 btn-info add-comment" data-toggle="tooltip" title="Añadir un comentario">
                                    <i class="fas fa-comment"></i>
                                </button>
                                <button class="btn btn-sm mx-1 btn-primary upload-evidence" data-toggle="tooltip" title="Subir evidencia">
                                    <i class="fas fa-upload"></i>
                                </button>
                                <button class='btn btn-sm mx-1 btn-view btn-outline-primary view-comments-evidence' data-toggle="tooltip" title="Ver comentarios y evidencias">
                                    <i class='fas fa-eye'></i>
                                </button>
                            </div>
                        </td>
                    </tr>`;
                    tbody.append(row);
                });
                $('[data-toggle="tooltip"]').tooltip();
            },
            error: function () {
                displayMessage($('.sectionsFA'), 'Error al procesar la solicitud', 'error');
            },
        });
    }

    // Funcionalidad de los botones
    $(document).on('click', '.toggle-signed', function () {
        const button = $(this);
        const row = button.closest('tr');
        const id = row.data('id');
        const newState = !button.hasClass('btn-success');

        $.ajax({
            url: '', // Actualiza con la ruta de tu script PHP
            type: 'POST',
            data: { action: 'toggleSigned', id: id, state: newState },
            success: function (response) {
                if (response.success) {
                    button.toggleClass('btn-success btn-danger');
                    button.find('i').toggleClass('fa-check fa-times');
                    const newTitle = newState ? 'Acta firmada' : 'Acta sin firma';
                    button
                        .attr('title', newTitle)
                        .attr('data-original-title', newTitle)
                        .tooltip('dispose') // Destruye el tooltip existente
                        .tooltip(); // Vuelve a inicializar
                } else {
                    alert('Error al actualizar el estado de firma.');
                }
            },
            error: function () {
                alert('Error al procesar la solicitud.');
            },
        });
    });

    $(document).on('click', '.toggle-absent', function () {
        const button = $(this);
        const row = button.closest('tr');
        const id = row.data('id');
        const newState = !button.hasClass('btn-warning');

        $.ajax({
            url: '', // Actualiza con la ruta de tu script PHP
            type: 'POST',
            data: { action: 'toggleAbsent', id: id, state: newState },
            success: function (response) {
                if (response.success) {
                    button.toggleClass('btn-warning btn-secondary');
                    button.find('i').toggleClass('fa-user-times fa-user-check');
                    const newTitle = newState ? 'Estará ausente' : 'Estará presente';
                    button
                        .attr('title', newTitle)
                        .attr('data-original-title', newTitle)
                        .tooltip('dispose') // Destruye el tooltip existente
                        .tooltip();
                } else {
                    alert('Error al actualizar el estado de ausencia.');
                }
            },
            error: function () {
                alert('Error al procesar la solicitud.');
            },
        });
    });

    $(document).on('click', '.add-comment', function () {
        currentCommentId = $(this).closest('tr').data('id');
        $('#commentText, #commentAuthor').val('');
        commentModal.show();
    });

    $('#saveCommentBtn').on('click', function () {
        const comment = $('#commentText').val().trim();
        const author = $('#commentAuthor').val().trim();

        if (comment && author) {
            $.ajax({
                url: '', // Actualiza con la ruta de tu script PHP
                type: 'POST',
                data: {
                    action: 'addComment',
                    id: currentCommentId,
                    comment: comment,
                    author: author,
                },
                success: function (response) {
                    if (response.success) {
                        alert('Comentario agregado correctamente.');
                        commentModal.hide();
                    } else {
                        alert('Error al agregar el comentario.');
                    }
                },
                error: function () {
                    alert('Error al procesar la solicitud.');
                },
            });
        } else {
            alert('Por favor, completa todos los campos.');
        }
    });

    $(document).on('click', '.upload-evidence', function () {
        const row = $(this).closest('tr');
        const id = row.data('id');
        const fileInput = $('<input type="file" accept="*/*">');

        fileInput.on('change', function () {
            const file = this.files[0];
            const formData = new FormData();
            formData.append('file', file, file.name);
            formData.append('id', id);
            formData.append('action', 'uploadEvidence');

            $.ajax({
                url: '', // Actualiza con la ruta de tu script PHP
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        alert('Evidencia subida correctamente.');
                    } else {
                        alert('Error al subir la evidencia.');
                    }
                },
                error: function () {
                    alert('Error al procesar la solicitud.');
                },
            });
        });

        fileInput.trigger('click');
    });

    $(document).on('click', '.view-comments-evidence', function () {
        const row = $(this).closest('tr');
        const id = row.data('id');

        // Eliminar el modal anterior si existe
        $('#commentsEvidenceModal').remove();

        $.ajax({
            url: '', // Actualiza con la ruta de tu script PHP
            type: 'POST',
            data: { action: 'getCommentsAndEvidence', id: id },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    let modalContent = '<div class="modal-body bg-light">';

                    // Agregar comentarios
                    modalContent += '<h5>Comentarios</h5>';
                    if (response.data.comments && response.data.comments.length > 0) {
                        response.data.comments.forEach((comment) => {
                            modalContent += `<p><strong>${comment.author}:</strong> ${comment.comment}</p>`;
                        });
                    } else {
                        modalContent += '<p>No hay comentarios disponibles.</p>';
                    }

                    // Agregar evidencias
                    modalContent += '<h5>Evidencias</h5>';
                    if (response.data.evidence && response.data.evidence.length > 0) {
                        response.data.evidence.forEach((evidence) => {
                            modalContent += `<p><a href="${evidence.path}" download>${evidence.name}</a></p>`;
                        });
                    } else {
                        modalContent += '<p>No hay evidencias disponibles.</p>';
                    }

                    modalContent += '</div>';

                    const modalHtml = `
                        <div class="modal fade" id="commentsEvidenceModal" tabindex="-1" aria-labelledby="commentsEvidenceModalLabel">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content shadow-lg rounded-3">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title fw-bold" id="commentsEvidenceModalLabel">Comentarios y Evidencias</h5>
                                        <button type="button" class="btn-close btn-close-white" data-dismiss="modal" aria-label="Cerrar">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    ${modalContent}
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>`;

                    $('body').append(modalHtml);
                    const commentsEvidenceModal = new bootstrap.Modal(
                        document.getElementById('commentsEvidenceModal')
                    );
                    commentsEvidenceModal.show();
                } else {
                    alert('Error al obtener los comentarios y evidencias.');
                }
            },
            error: function () {
                alert('Error al procesar la solicitud.');
            },
        });
    });

    // Seleccionar por defecto el checkbox de 'Todos'
    $('#selectAll').prop('checked', true);

    // Funciones para manejar los checkboxes de filtrado
    function updateTableFilter() {
        // Usamos let para poder re-asignar showAll más abajo
        let showAll = $('#selectAll').is(':checked');
        const showSigned = $('#filterSigned').is(':checked');
        const showAbsent = $('#filterAbsent').is(':checked');

        // Si ninguno de los filtros específicos está marcado,
        // forzamos 'Todos' y por tanto showAll = true
        if (!showSigned && !showAbsent) {
            $('#selectAll').prop('checked', true);
            showAll = true;
        }

        $('#programsTableBody tr').each(function () {
            const row = $(this);
            const hasSigned = row.find('.toggle-signed').hasClass('btn-success');
            const willBeAbsent = row.find('.toggle-absent').hasClass('btn-warning');

            let showRow;
            if (showAll) {
                // Cuando 'Todos' está activo, muestro todo
                showRow = true;
            } else if (showSigned && showAbsent) {
                // Si ambos filtros están activos, muestro sólo
                // las filas que cumplan *ambas* condiciones
                showRow = hasSigned && willBeAbsent;
            } else if (showSigned) {
                // Sólo Firmados
                showRow = hasSigned;
            } else if (showAbsent) {
                // Sólo Ausentes
                showRow = willBeAbsent;
            } else {
                // Caso residual (no debería entrar aquí)
                showRow = true;
            }

            row.toggle(showRow);
        });
    }

    $('#selectAll').on('change', function () {
        if (this.checked) {
            $('#filterSigned, #filterAbsent').prop('checked', false);
        }
        updateTableFilter();
    });

    $('#filterSigned, #filterAbsent').on('change', function () {
        if (this.checked) {
            $('#selectAll').prop('checked', false);
        }
        updateTableFilter();
    });

    // Inicializar el filtro de la tabla
    updateTableFilter();
});
