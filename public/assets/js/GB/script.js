$(document).ready(function () {
    // Manejo general de envío de formularios
    $(document).on('submit', 'form', function (e) {
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
                form.find("button[type='submit']").prop('disabled', true);
            },
            success: function (response) {
                let res;
                try {
                    res = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    console.error('Error al parsear la respuesta:', response);
                    displayMessage(form, 'Respuesta inválida del servidor.', 'error');
                    return;
                }

                if (res.success) {
                    if (form.find('input[name="action"]').val() === 'registerQuit') {
                        displayMessage(
                            $('#registrar'),
                            'Baja registrada correctamente.',
                            'success'
                        );
                        form[0].reset();
                        $('.quit-form-row').remove();
                        $('#tableActiveStudents tbody')
                            .find('.btn-primary')
                            .removeClass('btn-primary')
                            .addClass('btn-outline-primary');
                        loadActiveStudents();

                        // Scroll hacia arriba para mostrar el mensaje
                        $('html, body').animate(
                            {
                                scrollTop: $('#registrar').offset().top - 100,
                            },
                            500
                        );
                    } else {
                        displayMessage(form, 'Acción realizada correctamente');
                    }

                    // Recargar tabla si estamos en la sección de consulta
                    if ($('#consultar').is(':visible')) {
                        loadQuittedTable();
                    }
                } else {
                    const errorMsg = res.message || 'Error al procesar la solicitud';
                    if (form.find('input[name="action"]').val() === 'registerQuit') {
                        displayMessage($('#registrar'), errorMsg, 'error');
                    } else {
                        displayMessage(form, errorMsg, 'error');
                    }
                }
                console.log(response);
            },
            error: function (xhr) {
                const errorMsg = 'Error al procesar la solicitud. Código: ' + xhr.status;
                if (form.find('input[name="action"]').val() === 'registerQuit') {
                    displayMessage($('#registrar'), errorMsg, 'error');
                } else {
                    displayMessage(form, errorMsg, 'error');
                }
                console.error('Error AJAX:', xhr.responseText);
            },
            complete: function () {
                form.find("button[type='submit']").prop('disabled', false);
            },
        });
    });

    // Función para mostrar mensajes de alerta
    function displayMessage(pos, message, type = 'success') {
        if (pos && pos.length > 0) {
            // Remover mensajes anteriores
            pos.find('.alert').remove();

            const newDiv = document.createElement('div');
            newDiv.className =
                type == 'success' ? 'alert alert-success mt-3' : 'alert alert-danger mt-3';
            newDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;

            // Si es un contenedor, agregar al inicio; si es un formulario, agregar antes
            if (pos.hasClass('sectionGB')) {
                pos.prepend(newDiv);
            } else {
                pos.before(newDiv);
            }

            // Auto-ocultar mensaje después de 5 segundos
            setTimeout(() => {
                $(newDiv).fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    // Configuración de botones de navegación
    function setupBtnsGB(name) {
        if (!name) {
            console.error('Missing name - setupBtnsGB');
            return;
        }

        $('#' + name).on('click', function () {
            $('.alert').remove();
            $('.sectionGB').hide();
            $('.sectionsGB button').removeClass('btn-primary').addClass('btn-outline-primary');

            $(this).removeClass('btn-outline-primary').addClass('btn-primary');

            const targetSectionId = name.split('-').slice(1).join('-');
            $('#' + targetSectionId).show();

            // Lógica específica para cada sección
            if (targetSectionId === 'registrar') {
                loadActiveStudents();
                loadDropdowns();
            } else if (targetSectionId === 'consultar') {
                loadQuittedTable();
            }
        });
    }

    // Cargar estudiantes activos
    function loadActiveStudents() {
        const tableBody = $('#tableActiveStudents tbody');

        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getActiveStudents' },
            beforeSend: function () {
                tableBody
                    .empty()
                    .html('<tr><td colspan="4" class="text-center">Cargando...</td></tr>');
            },
            success: function (response) {
                let res;
                try {
                    res = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    console.error('Error al parsear la respuesta:', response);
                    tableBody
                        .empty()
                        .html(
                            '<tr><td colspan="4" class="text-center">Error al cargar datos.</td></tr>'
                        );
                    return;
                }

                tableBody.empty();

                if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                    res.data.forEach(function (student) {
                        let row = `<tr data-student='${JSON.stringify(student)}'>
                                <td>${student.ulsaID || 'N/A'}</td>
                                <td>${student.fullName}</td>
                                <td>${student.programName}</td>
                                <td class="text-center">
                                    <button class='btn-select-student btn btn-sm btn-outline-primary' data-student-id='${student.id}' title="Registrar baja para este estudiante">
                                        <i class='fas fa-door-open'></i>
                                    </button>
                                </td>
                            </tr>`;
                        tableBody.append(row);
                    });
                } else {
                    tableBody.html(
                        '<tr><td colspan="4" class="text-center">No se encontraron estudiantes activos.</td></tr>'
                    );
                }
            },
            error: function (xhr) {
                console.error('Error AJAX:', xhr.responseText);
                tableBody
                    .empty()
                    .html(
                        '<tr><td colspan="4" class="text-center">Error al cargar datos.</td></tr>'
                    );
            },
        });
    }

    // Cargar dropdowns para el formulario dinámico
    function loadDropdownsForForm() {
        // Cargar tipos de baja
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getQuitDescriptions' },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;
                if (res.success && Array.isArray(res.data)) {
                    const select = $('.quit-form-row #quitDescriptionID');
                    select.empty();
                    select.append('<option value="">Seleccionar tipo de baja</option>');
                    res.data.forEach(function (item) {
                        select.append(`<option value="${item.id}">${item.description}</option>`);
                    });
                }
            },
        });

        // Cargar razones de baja
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getQuitReasons' },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;
                if (res.success && Array.isArray(res.data)) {
                    const select = $('.quit-form-row #quitReasonID');
                    select.empty();
                    select.append('<option value="">Seleccionar razón</option>');
                    res.data.forEach(function (item) {
                        select.append(`<option value="${item.id}">${item.description}</option>`);
                    });
                }
            },
        });

        // Cargar estados de baja
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getQuitStatuses' },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;
                if (res.success && Array.isArray(res.data)) {
                    const select = $('.quit-form-row #quitStatusID');
                    select.empty();
                    select.append('<option value="">Seleccionar estado</option>');
                    res.data.forEach(function (item) {
                        select.append(`<option value="${item.id}">${item.description}</option>`);
                    });
                }
            },
        });
    }

    // Cargar dropdowns (función original para compatibilidad)
    function loadDropdowns() {
        loadDropdownsForForm();
    }

    // Manejar selección de estudiante
    $(document).on('click', '.btn-select-student', function () {
        const button = $(this);
        const row = button.closest('tr');
        const studentData = row.data('student');

        // Remover formularios existentes
        $('.quit-form-row').remove();

        // Marcar como seleccionado
        $('#tableActiveStudents tbody .btn-select-student')
            .removeClass('btn-primary')
            .addClass('btn-outline-primary');
        button.removeClass('btn-outline-primary').addClass('btn-primary');

        // Crear formulario de baja
        const formHtml = createQuitFormCard(studentData);
        const formRow = `<tr class='quit-form-row'>
            <td colspan='4'>${formHtml}</td>
        </tr>`;

        // Insertar después de la fila seleccionada
        row.after(formRow);

        // Cargar dropdowns para el formulario
        loadDropdownsForForm();

        // Hacer scroll al formulario
        $('html, body').animate(
            {
                scrollTop: $('.quit-form-row').offset().top - 100,
            },
            500
        );
    });

    // Crear formulario de baja
    function createQuitFormCard(studentData) {
        return `
            <div class="quit-form-card">
                <h5 class="mb-4"><i class="fas fa-file-alt"></i> Datos de la Baja</h5>
                
                <div class="student-info mb-3">
                    <strong>Estudiante seleccionado:</strong><br>
                    Clave ULSA: ${studentData.ulsaID || 'N/A'}<br>
                    Nombre: ${studentData.fullName}<br>
                    Programa: ${studentData.programName}
                </div>

                <form id="quitForm" action="" method="post">
                    <input type="hidden" name="action" value="registerQuit">
                    <input type="hidden" id="studentID" name="studentID" value="${studentData.id}">

                    <div class="form-group row mb-3">
                        <label for="quitDescriptionID" class="col-md-3 col-form-label">Tipo de Baja:</label>
                        <div class="col-md-8 ml-2">
                            <select class="form-control" id="quitDescriptionID" name="quitDescriptionID" required>
                                <option value="">Seleccionar tipo de baja</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="requestedAt" class="col-md-3 col-form-label">Fecha de Solicitud:</label>
                        <div class="col-md-8 ml-2">
                            <input type="datetime-local" class="form-control" id="requestedAt" name="requestedAt">
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="officialApplyingAt" class="col-md-3 col-form-label">Fecha de Aplicación Oficial:</label>
                        <div class="col-md-8 ml-2">
                            <input type="datetime-local" class="form-control" id="officialApplyingAt" name="officialApplyingAt">
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="nofficialApplyingAt" class="col-md-3 col-form-label">Fecha de Aplicación No Oficial:</label>
                        <div class="col-md-8 ml-2">
                            <input type="datetime-local" class="form-control" id="nofficialApplyingAt" name="nofficialApplyingAt">
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="returningAt" class="col-md-3 col-form-label">Fecha de Retorno:</label>
                        <div class="col-md-8 ml-2">
                            <input type="datetime-local" class="form-control" id="returningAt" name="returningAt">
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="quitReasonID" class="col-md-3 col-form-label">Razón de Baja:</label>
                        <div class="col-md-8 ml-2">
                            <select class="form-control" id="quitReasonID" name="quitReasonID">
                                <option value="">Seleccionar razón</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="quitStatusID" class="col-md-3 col-form-label">Estado de la Baja:</label>
                        <div class="col-md-8 ml-2">
                            <select class="form-control" id="quitStatusID" name="quitStatusID" required>
                                <option value="">Seleccionar estado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row mb-3" style="display: none;">
                        <input type="hidden" id="status" name="status" value="">
                    </div>

                    <div class="form-group row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Nota:</strong> El estado de la baja podrá modificarse posteriormente desde la sección de consulta.
                            </div>
                        </div>
                    </div>



                    <div class="text-center mt-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="btnCancelQuit" style="width: 150px;">
                            <i class="fas fa-times mr-2"></i>
                            <span>Cancelar</span>
                        </button>
                        <button type="submit" class="btn btn-outline-danger" style="width: 200px;">
                            <i class="fas fa-user-times mr-2"></i>
                            <span>Registrar Baja</span>
                        </button>
                    </div>
                </form>
            </div>
        `;
    }

    // Manejar cancelación de registro
    $(document).on('click', '#btnCancelQuit', function () {
        $('.quit-form-row').remove();
        $('#tableActiveStudents tbody .btn-select-student')
            .removeClass('btn-primary')
            .addClass('btn-outline-primary');
    });

    // Cargar tabla de bajas
    function loadQuittedTable() {
        const tableBody = $('#tableQuitted tbody');

        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getQuittedStudents' },
            beforeSend: function () {
                tableBody
                    .empty()
                    .html('<tr><td colspan="5" class="text-center">Cargando...</td></tr>');
            },
            success: function (response) {
                let res;
                try {
                    res = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    console.error('Error al parsear la respuesta:', response);
                    tableBody
                        .empty()
                        .html(
                            '<tr><td colspan="5" class="text-center">Error al cargar datos.</td></tr>'
                        );
                    return;
                }

                tableBody.empty();

                if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                    res.data.forEach(function (quitted) {
                        let statusBadge = quitted.quitStatusName
                            ? `<span class="status-badge ${getStatusClass(quitted.quitStatusName)}">${quitted.quitStatusName}</span>`
                            : '<span class="status-badge pending">Sin estado</span>';

                        let row = `<tr>
                                <td>${quitted.studentUlsaID || 'N/A'}</td>
                                <td>${quitted.studentName}</td>
                                <td>${quitted.programName}</td>
                                <td class="text-center">${statusBadge}</td>
                                <td class="text-center">
                                    <button class='btn-view-quitted btn btn-sm btn-outline-primary' data-quitted-id='${quitted.id}'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                </td>
                            </tr>`;
                        tableBody.append(row);
                    });
                } else {
                    tableBody.html(
                        '<tr><td colspan="5" class="text-center">No se encontraron bajas registradas.</td></tr>'
                    );
                }
            },
            error: function (xhr) {
                console.error('Error AJAX:', xhr.responseText);
                tableBody
                    .empty()
                    .html(
                        '<tr><td colspan="5" class="text-center">Error al cargar datos.</td></tr>'
                    );
            },
        });
    }

    // Obtener clase CSS según el estado
    function getStatusClass(status) {
        const lowerStatus = status.toLowerCase();
        if (lowerStatus.includes('aplicada') || lowerStatus.includes('activa')) {
            return 'active';
        } else if (lowerStatus.includes('cancelada') || lowerStatus.includes('rechazada')) {
            return 'inactive';
        } else {
            return 'pending';
        }
    }

    // Manejar clic en botón de ver detalles
    $(document).on('click', '.btn-view-quitted', function () {
        const button = $(this);
        const icon = button.find('i');
        const quittedId = $(this).data('quittedId');
        const row = $(this).closest('tr');
        const existingDetails = row.next('.quitted-details-row');

        if (existingDetails.length) {
            existingDetails.remove();
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $.ajax({
                url: '',
                type: 'POST',
                data: { action: 'getQuittedDetails', quittedID: quittedId },
                beforeSend: function () {
                    button.prop('disabled', true);
                },
                success: function (response) {
                    let res = typeof response === 'string' ? JSON.parse(response) : response;

                    if (res.success && res.data) {
                        const quitted = res.data;
                        const detailsHtml = createQuittedDetailsCard(quitted);

                        const detailsRow = `<tr class='quitted-details-row'>
                            <td colspan='5'>${detailsHtml}</td>
                        </tr>`;

                        row.after(detailsRow);
                        icon.removeClass('fa-eye').addClass('fa-eye-slash');

                        // Cargar dropdowns para los selectores
                        loadDropdownsForQuitted(quitted.id, quitted);

                        // Cargar comentarios y evidencias
                        loadQuittedCommentsAndEvidence(quitted.id);
                    } else {
                        displayMessage(row, 'Error al obtener detalles de la baja', 'error');
                    }
                },
                error: function () {
                    displayMessage(row, 'Error al procesar la solicitud', 'error');
                },
                complete: function () {
                    button.prop('disabled', false);
                },
            });
        }
    });

    // Crear tarjeta de detalles de baja
    function createQuittedDetailsCard(quitted) {
        return `
            <div class='quitted-details' data-quitted-id='${quitted.id}'>
                <h5 class='mb-4'>Detalles de la Baja</h5>
                
                <!-- Información del Estudiante -->
                <div class='field-group'>
                    <h6><i class='fas fa-user'></i> Información del Estudiante</h6>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Clave ULSA:</span>
                        <div class='field-value'>
                            <span class='field-text'>${quitted.studentUlsaID || 'N/A'}</span>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Nombre:</span>
                        <div class='field-value'>
                            <span class='field-text'>${quitted.studentName}</span>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Programa:</span>
                        <div class='field-value'>
                            <span class='field-text'>${quitted.programName}</span>
                        </div>
                    </div>
                </div>

                <!-- Información de la Baja -->
                <div class='field-group'>
                    <h6><i class='fas fa-file-alt'></i> Información de la Baja</h6>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Tipo de Baja:</span>
                        <div class='field-value' data-field='quitdescription_id'>
                            <span class='field-text'>${quitted.quitDescriptionName || 'No especificado'}</span>
                            <select class='form-control form-control-sm quit-description-select'>
                                <option value=''>Cargando...</option>
                            </select>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Razón de Baja:</span>
                        <div class='field-value' data-field='quitreason_id'>
                            <span class='field-text'>${quitted.quitReasonName || 'No especificada'}</span>
                            <select class='form-control form-control-sm quit-reason-select'>
                                <option value=''>Cargando...</option>
                            </select>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Estado de la Baja:</span>
                        <div class='field-value' data-field='quitstatus_id'>
                            <span class='field-text'>${quitted.quitStatusName || 'No especificado'}</span>
                            <select class='form-control form-control-sm quit-status-select'>
                                <option value=''>Cargando...</option>
                            </select>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Fechas -->
                <div class='field-group'>
                    <h6><i class='fas fa-calendar'></i> Fechas</h6>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Fecha de Solicitud:</span>
                        <div class='field-value' data-field='requested_at'>
                            <span class='field-text'>${formatDateTime(quitted.requestedAt, true)}</span>
                            <input type='datetime-local' class='form-control form-control-sm' value='${formatDateTimeForInput(quitted.requestedAt)}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Aplicación Oficial:</span>
                        <div class='field-value' data-field='official_applying_at'>
                            <span class='field-text'>${formatDateTime(quitted.officialApplyingAt, true)}</span>
                            <input type='datetime-local' class='form-control form-control-sm' value='${formatDateTimeForInput(quitted.officialApplyingAt)}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Aplicación No Oficial:</span>
                        <div class='field-value' data-field='nofficial_applying_at'>
                            <span class='field-text'>${formatDateTime(quitted.nofficialApplyingAt, true)}</span>
                            <input type='datetime-local' class='form-control form-control-sm' value='${formatDateTimeForInput(quitted.nofficialApplyingAt)}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Fecha de Retorno:</span>
                        <div class='field-value' data-field='returning_at'>
                            <span class='field-text'>${formatDateTime(quitted.returningAt, true)}</span>
                            <input type='datetime-local' class='form-control form-control-sm' value='${formatDateTimeForInput(quitted.returningAt)}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Comentarios -->
                <div class='field-group'>
                    <h6><i class='fas fa-comments'></i> Comentarios</h6>
                    <div class='mb-3'>
                        <button class='btn btn-sm btn-primary add-comment' data-quitted-id='${quitted.id}'>
                            <i class='fas fa-plus'></i> Agregar Comentario
                        </button>
                    </div>
                    <div class='comments-list' id='comments-list-${quitted.id}'>
                        <p class='text-muted'>Cargando comentarios...</p>
                    </div>
                </div>

                <!-- Evidencias -->
                <div class='field-group'>
                    <h6><i class='fas fa-paperclip'></i> Evidencias</h6>
                    <div class='evidence-section'>
                        <div class='mb-3'>
                            <button class='btn btn-sm btn-primary upload-evidence' data-quitted-id='${quitted.id}'>
                                <i class='fas fa-upload'></i> Subir Evidencia
                            </button>
                        </div>
                        <div class='evidence-list' id='evidence-list-${quitted.id}'>
                            <p class='text-muted'>Cargando evidencias...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Formatear fecha y hora
    function formatDateTime(dateTimeStr, includeTime = false) {
        if (!dateTimeStr) return 'No especificada';

        const date = new Date(dateTimeStr);
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        };

        if (includeTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }

        return date.toLocaleDateString('es-MX', options);
    }

    // Formatear fecha y hora para input datetime-local
    function formatDateTimeForInput(dateTimeStr) {
        if (!dateTimeStr) return '';
        const date = new Date(dateTimeStr);
        return date.toISOString().slice(0, 16);
    }

    // Cargar dropdowns para la baja
    function loadDropdownsForQuitted(quittedId, currentData) {
        // Cargar tipos de baja
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getQuitDescriptions' },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;
                if (res.success && Array.isArray(res.data)) {
                    const select = $(
                        `.quitted-details[data-quitted-id='${quittedId}'] .quit-description-select`
                    );
                    select.empty();
                    res.data.forEach(function (item) {
                        const selected = item.id == currentData.quitDescriptionID ? 'selected' : '';
                        select.append(
                            `<option value="${item.id}" ${selected}>${item.description}</option>`
                        );
                    });
                }
            },
        });

        // Cargar razones
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getQuitReasons' },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;
                if (res.success && Array.isArray(res.data)) {
                    const select = $(
                        `.quitted-details[data-quitted-id='${quittedId}'] .quit-reason-select`
                    );
                    select.empty();
                    select.append('<option value="">Sin especificar</option>');
                    res.data.forEach(function (item) {
                        const selected = item.id == currentData.quitReasonID ? 'selected' : '';
                        select.append(
                            `<option value="${item.id}" ${selected}>${item.description}</option>`
                        );
                    });
                }
            },
        });

        // Cargar estados
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getQuitStatuses' },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;
                if (res.success && Array.isArray(res.data)) {
                    const select = $(
                        `.quitted-details[data-quitted-id='${quittedId}'] .quit-status-select`
                    );
                    select.empty();
                    select.append('<option value="">Sin especificar</option>');
                    res.data.forEach(function (item) {
                        const selected = item.id == currentData.quitStatusID ? 'selected' : '';
                        select.append(
                            `<option value="${item.id}" ${selected}>${item.description}</option>`
                        );
                    });
                }
            },
        });
    }

    // Cargar comentarios y evidencias
    function loadQuittedCommentsAndEvidence(quittedId) {
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getQuittedCommentsAndEvidence', quittedID: quittedId },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;

                if (res.success && res.data) {
                    // Mostrar comentarios
                    const commentsList = $(`#comments-list-${quittedId}`);
                    commentsList.empty();

                    if (res.data.comments && res.data.comments.length > 0) {
                        res.data.comments.forEach(function (comment) {
                            commentsList.append(`
                                <div class='comment-box'>
                                    <div class='comment-author'>${comment.author}</div>
                                    <div class='comment-text'>${comment.comment}</div>
                                </div>
                            `);
                        });
                    } else {
                        commentsList.html('<p class="text-muted">No hay comentarios.</p>');
                    }

                    // Mostrar evidencias
                    const evidenceList = $(`#evidence-list-${quittedId}`);
                    evidenceList.empty();

                    if (res.data.evidence && res.data.evidence.length > 0) {
                        res.data.evidence.forEach(function (evidence) {
                            evidenceList.append(`
                                <div class='evidence-item'>
                                    <i class='fas fa-file-alt text-primary'></i>
                                    <a href='${evidence.path}' download='${evidence.name}' class='ml-2'>
                                        ${evidence.name}
                                    </a>
                                </div>
                            `);
                        });
                    } else {
                        evidenceList.html('<p class="text-muted">No hay evidencias.</p>');
                    }
                }
            },
            error: function () {
                $(`#comments-list-${quittedId}`).html(
                    '<p class="text-danger">Error al cargar comentarios.</p>'
                );
                $(`#evidence-list-${quittedId}`).html(
                    '<p class="text-danger">Error al cargar evidencias.</p>'
                );
            },
        });
    }

    // Manejar edición de campos
    $(document).on('click', '.btn-edit-field', function () {
        const button = $(this);
        const fieldValue = button.closest('.field-value');
        const isEditing = fieldValue.hasClass('editing');

        if (isEditing) {
            // Guardar cambios
            const quittedId = button.closest('.quitted-details').data('quittedId');
            const field = fieldValue.data('field');
            let value;

            if (fieldValue.find('input').length) {
                value = fieldValue.find('input').val();
            } else if (fieldValue.find('select').length) {
                value = fieldValue.find('select').val();
            }

            // Actualizar en la base de datos
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'updateQuittedField',
                    quittedID: quittedId,
                    field: field,
                    value: value || null,
                },
                beforeSend: function () {
                    button.prop('disabled', true);
                },
                success: function (response) {
                    let res = typeof response === 'string' ? JSON.parse(response) : response;

                    if (res.success) {
                        // Actualizar texto mostrado
                        if (fieldValue.find('select').length) {
                            const selectedText = fieldValue.find('select option:selected').text();
                            fieldValue.find('.field-text').text(selectedText || 'Sin especificar');
                        } else {
                            fieldValue.find('.field-text').text(value || 'No especificado');
                        }

                        fieldValue.removeClass('editing');
                        button.html('<i class="fas fa-edit"></i>');

                        // Recargar tabla
                        loadQuittedTable();
                    } else {
                        alert(
                            'Error al actualizar el campo: ' + (res.message || 'Error desconocido')
                        );
                    }
                },
                error: function () {
                    alert('Error al procesar la solicitud');
                },
                complete: function () {
                    button.prop('disabled', false);
                },
            });
        } else {
            // Entrar en modo edición
            fieldValue.addClass('editing');
            button.html('<i class="fas fa-save"></i>');

            // Enfocar el campo de entrada
            const input = fieldValue.find('input, select');
            if (input.length) {
                input.focus();
            }
        }
    });

    // Manejar agregar comentario
    $(document).on('click', '.add-comment', function () {
        const quittedId = $(this).data('quittedId');
        const commentText = prompt('Ingrese el comentario:');

        if (commentText && commentText.trim()) {
            const author = prompt('Ingrese su nombre:') || 'Anónimo';

            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'addQuittedComment',
                    quittedID: quittedId,
                    comment: commentText.trim(),
                    author: author.trim(),
                },
                success: function (response) {
                    let res = typeof response === 'string' ? JSON.parse(response) : response;

                    if (res.success) {
                        loadQuittedCommentsAndEvidence(quittedId);
                    } else {
                        alert('Error al agregar comentario');
                    }
                },
                error: function () {
                    alert('Error al procesar la solicitud');
                },
            });
        }
    });

    // Manejar subida de evidencias
    $(document).on('click', '.upload-evidence', function () {
        const quittedId = $(this).data('quittedId');
        const fileInput = $('<input type="file" accept="*/*">');

        fileInput.on('change', function () {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('quittedID', quittedId);
            formData.append('action', 'uploadQuittedEvidence');

            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $(`#evidence-list-${quittedId}`).html(
                        '<p class="text-info">Subiendo archivo...</p>'
                    );
                },
                success: function (response) {
                    let res = typeof response === 'string' ? JSON.parse(response) : response;

                    if (res.success) {
                        displayMessage(
                            $(`.quitted-details[data-quitted-id='${quittedId}']`),
                            'Evidencia subida correctamente',
                            'success'
                        );
                        loadQuittedCommentsAndEvidence(quittedId);
                    } else {
                        alert('Error al subir la evidencia.');
                        loadQuittedCommentsAndEvidence(quittedId);
                    }
                },
                error: function () {
                    alert('Error al procesar la solicitud.');
                    loadQuittedCommentsAndEvidence(quittedId);
                },
            });
        });

        fileInput.trigger('click');
    });

    // Inicializar navegación
    setupBtnsGB('btn-registrar');
    setupBtnsGB('btn-consultar');

    // Mostrar sección de registro por defecto
    $('#btn-registrar').click();
});
