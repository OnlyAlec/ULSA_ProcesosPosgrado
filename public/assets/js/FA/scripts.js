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
                                <button class="btn btn-sm mx-1 toggle-signed ${program.has_signed ? 'btn-success' : 'btn-danger'}" data-toggle="tooltip" title="Intercambiar estado de firma">
                                    <i class="fas ${program.has_signed ? 'fa-check' : 'fa-times'}"></i>
                                </button>
                                <button class="btn btn-sm mx-1 toggle-absent ${program.will_be_absent ? 'btn-warning' : 'btn-secondary'}" data-toggle="tooltip" title="Intercambiar estado de asistencia">
                                    <i class="fas ${program.will_be_absent ? 'fa-user-times' : 'fa-user-check'}"></i>
                                </button>
                                <button class="btn btn-sm mx-1 btn-info add-comment" data-toggle="tooltip" title="Agregar comentario">
                                    <i class="fas fa-comment"></i>
                                </button>
                                <button class="btn btn-sm mx-1 btn-primary upload-evidence" data-toggle="tooltip" title="Subir evidencia">
                                    <i class="fas fa-upload"></i>
                                </button>
                                <button class='btn btn-sm mx-1 btn-view btn-outline-primary' data-toggle="tooltip" title="Ver comentarios y evidencias">
                                    <i class='fas fa-eye'></i>
                                </button>
                            </div>
                        </td>
                    </tr>`;
                    tbody.append(row);
                });
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
            formData.append('file', file);
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
});
