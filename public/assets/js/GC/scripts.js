$(document).ready(function () {
    // Manejo general de envío de formularios
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
                    displayMessage(form, 'Acción realizada correctamente');

                    // Limpiar formularios después de acciones exitosas
                    if (form.find('input[name="action"]').val() === 'registerOneCandidate') {
                        form[0].reset();
                        $('#claveUlsaGroup').hide();
                        $('#tieneClaveUlsa').prop('checked', false);
                        loadProgramOptions(); // Recargar opciones de programas
                    } else if (form.find('input[name="action"]').val() === 'deleteOneCandidate') {
                        form[0].reset();
                    }

                    // Recargar tabla si estamos en la sección de consulta
                    if ($('#consultar').is(':visible')) {
                        loadCandidatesTable();
                    }
                } else {
                    const errorMsg = res.message || 'Error al procesar la solicitud';
                    displayMessage(form, errorMsg, 'error');
                }
                console.log(response);
            },
            error: function (xhr) {
                const errorMsg = 'Error al procesar la solicitud. Código: ' + xhr.status;
                displayMessage(form, errorMsg, 'error');
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
            const newDiv = document.createElement('div');
            newDiv.className =
                type == 'success' ? 'alert alert-success mt-3' : 'alert alert-danger mt-3';
            newDiv.innerHTML = message;
            pos.before(newDiv);

            // Auto-ocultar mensaje después de 5 segundos
            setTimeout(() => {
                $(newDiv).fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    // Configuración de botones de navegación
    function setupBtnsGC(name) {
        if (!name) {
            console.error('Missing name - setupBtnsGC');
            return;
        }

        $('#' + name).on('click', function () {
            $('.alert').remove();
            $('.sectionGC').hide();
            $('.sectionsGC button').removeClass('btn-primary').addClass('btn-outline-primary');

            $(this).removeClass('btn-outline-primary').addClass('btn-primary');

            const targetSectionId = name.split('-').slice(1).join('-');
            $('#' + targetSectionId).show();

            // Lógica específica para cada sección
            if (targetSectionId === 'crear') {
                loadProgramOptions();
            } else if (targetSectionId === 'consultar') {
                loadCandidatesTable();
            }
        });
    }

    // Cargar opciones de programas académicos
    function loadProgramOptions() {
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getPrograms' },
            success: function (response) {
                let res;
                try {
                    res = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    console.error('Error al parsear la respuesta de programas:', response);
                    return;
                }

                if (res.success && Array.isArray(res.data)) {
                    const select = $('#programaAcademico');
                    select.empty();
                    select.append('<option value="">Seleccionar programa</option>');

                    res.data.forEach(function (program) {
                        select.append(`<option value="${program.id}">${program.name}</option>`);
                    });
                } else {
                    console.error(
                        'Error al obtener programas:',
                        res.message || 'Respuesta inesperada'
                    );
                }
            },
            error: function (xhr) {
                console.error('Error AJAX al cargar programas:', xhr.responseText);
            },
        });
    }

    // Cargar tabla de candidatos
    function loadCandidatesTable() {
        const tableBody = $('#tableCandidates tbody');

        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getTableCandidates' },
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
                    console.error('Error al parsear la respuesta de candidatos:', response);
                    tableBody
                        .empty()
                        .html(
                            '<tr><td colspan="5" class="text-center">Error al cargar datos.</td></tr>'
                        );
                    return;
                }

                tableBody.empty();

                if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                    res.data.forEach(function (candidate) {
                        let statusIcon;
                        let statusClass;
                        
                        // Determinar icono y clase según el status
                        switch(candidate.status) {
                            case 1: // Pendiente
                                statusIcon = '<span class="status-badge pending"><i class="fas fa-clock"></i> Pendiente</span>';
                                break;
                            case 2: // Inscrito
                                statusIcon = '<span class="status-badge active"><i class="fas fa-check"></i> Inscrito</span>';
                                break;
                            case 3: // Baja
                                statusIcon = '<span class="status-badge inactive"><i class="fas fa-times"></i> Baja</span>';
                                break;
                            default:
                                statusIcon = '<span class="status-badge pending"><i class="fas fa-question"></i> Desconocido</span>';
                        }
                        
                        let row = `<tr>
                                <td>${candidate.admissionFolio}</td>
                                <td>${candidate.fullName}</td>
                                <td>${candidate.programName || ''}</td>
                                <td class="text-center">${statusIcon}</td>
                                <td class="text-center">
                                    <button class='btn-view btn btn-sm btn-outline-primary' data-candidate-id='${candidate.id}'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                </td>
                            </tr>`;
                        tableBody.append(row);
                    });
                } else {
                    tableBody.html(
                        '<tr><td colspan="5" class="text-center">No se encontraron candidatos.</td></tr>'
                    );
                }
            },
            error: function (xhr) {
                console.error('Error AJAX al cargar tabla de candidatos:', xhr.responseText);
                tableBody
                    .empty()
                    .html(
                        '<tr><td colspan="5" class="text-center">Error al cargar datos.</td></tr>'
                    );
            },
        });
    }

    // Manejar clic en botón de ver detalles
    $(document).on('click', '.btn-view', function () {
        const button = $(this);
        const icon = button.find('i');
        const candidateId = $(this).data('candidateId');
        const row = $(this).closest('tr');
        const existingDetails = row.next('.candidate-details-row');

        if (existingDetails.length) {
            existingDetails.remove();
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $.ajax({
                url: '',
                type: 'POST',
                data: { action: 'getCandidateDetails', candidateID: candidateId },
                beforeSend: function () {
                    button.prop('disabled', true);
                },
                success: function (response) {
                    let res = typeof response === 'string' ? JSON.parse(response) : response;

                    if (res.success && res.data) {
                        const candidate = res.data;
                        console.log('Datos del candidato:', candidate); // Debug log
                        const detailsHtml = createCandidateDetailsCard(candidate);

                        const detailsRow = `<tr class='candidate-details-row'>
                            <td colspan='5'>${detailsHtml}</td>
                        </tr>`;

                        row.after(detailsRow);
                        icon.removeClass('fa-eye').addClass('fa-eye-slash');

                        // Cargar programas para el selector
                        loadProgramsForCandidate(candidate.id, candidate.programID);
                        
                        // Cargar descripciones de status
                        loadStatusDescriptions(candidate.id, candidate.status);
                        
                        // Cargar evidencias
                        loadCandidateEvidence(candidate.id);
                        
                        // Verificar y actualizar estado del dropdown de status
                        updateStatusDropdownState(candidate.id);
                    } else {
                        displayMessage(row, 'Error al obtener detalles del candidato', 'error');
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

    // Crear tarjeta de detalles del candidato
    function createCandidateDetailsCard(candidate) {
        return `
            <div class='candidate-details' data-candidate-id='${candidate.id}'>
                <h5 class='mb-4'>Detalles del Candidato</h5>
                
                <!-- Información Personal -->
                <div class='field-group'>
                    <h6><i class='fas fa-user'></i> Información Personal</h6>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Nombre:</span>
                        <div class='field-value' data-field='first_name'>
                            <span class='field-text'>${candidate.firstName}</span>
                            <input type='text' class='form-control form-control-sm' value='${candidate.firstName}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Apellidos:</span>
                        <div class='field-value' data-field='last_name'>
                            <span class='field-text'>${candidate.lastName}</span>
                            <input type='text' class='form-control form-control-sm' value='${candidate.lastName}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Clave ULSA:</span>
                        <div class='field-value' data-field='ulsa_id'>
                            <span class='field-text'>${candidate.ulsaID || 'Sin asignar'}</span>
                            <input type='text' class='form-control form-control-sm' value='${candidate.ulsaID || ''}' maxlength='6'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Correo Principal:</span>
                        <div class='field-value' data-field='email'>
                            <span class='field-text'>${candidate.email}</span>
                            <input type='email' class='form-control form-control-sm' value='${candidate.email}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Correo Alternativo:</span>
                        <div class='field-value' data-field='email'>
                            <span class='field-text'>${candidate.email2 || 'No especificado'}</span>
                            <input type='email' class='form-control form-control-sm' value='${candidate.email2 || ''}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Celular:</span>
                        <div class='field-value' data-field='mobile_phone'>
                            <span class='field-text'>${candidate.mobilePhone}</span>
                            <input type='tel' class='form-control form-control-sm' value='${candidate.mobilePhone}' maxlength='10'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Información de Admisión -->
                <div class='field-group'>
                    <h6><i class='fas fa-file-alt'></i> Información de Admisión</h6>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Folio de Admisión:</span>
                        <div class='field-value' data-field='admission_folio'>
                            <span class='field-text'>${candidate.admissionFolio}</span>
                            <input type='text' class='form-control form-control-sm' value='${candidate.admissionFolio}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Número de Bloque:</span>
                        <div class='field-value' data-field='admission_block_number'>
                            <span class='field-text'>${candidate.admissionBlockNumber}</span>
                            <select class='form-control form-control-sm'>
                                ${[1, 2, 3, 4, 5]
                                    .map(
                                        (n) =>
                                            `<option value='${n}' ${candidate.admissionBlockNumber == n ? 'selected' : ''}>${n}</option>`
                                    )
                                    .join('')}
                            </select>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Programa Académico:</span>
                        <div class='field-value' data-field='program_id'>
                            <span class='field-text'>${candidate.programName || ''}</span>
                            <select class='form-control form-control-sm program-select'>
                                <option value=''>Cargando...</option>
                            </select>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Fechas de Entrevista -->
                <div class='field-group'>
                    <h6><i class='fas fa-calendar'></i> Fechas de Entrevista</h6>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Fecha de Solicitud:</span>
                        <div class='field-value' data-field='interview_request_date'>
                            <span class='field-text'>${formatDateTime(candidate.interviewRequestDate, false)}</span>
                            <input type='date' class='form-control form-control-sm' value='${formatDateForInput(candidate.interviewRequestDate)}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Fecha y Hora de Entrevista:</span>
                        <div class='field-value' data-field='interview_datetime'>
                            <span class='field-text'>${formatDateTime(candidate.interviewDateTime, true)}</span>
                            <input type='datetime-local' class='form-control form-control-sm' value='${formatDateTimeForInput(candidate.interviewDateTime)}'>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estados y Pendientes -->
                <div class='field-group'>
                    <h6><i class='fas fa-exclamation-triangle'></i> Estados y Pendientes</h6>
                    
                    <!-- Estados -->
                    <div class='editable-field'>
                        <span class='field-label'>Status:</span>
                        <div class='field-value' data-field='status'>
                            <span class='field-text status-text'>${candidate.statusDescription || 'pendiente'}</span>
                            <select class='form-control form-control-sm status-select'>
                                <option value=''>Cargando...</option>
                            </select>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Aprobación Coordinador:</span>
                        <div class='field-value' data-field='program_coordinator_approval_flag'>
                            <div class='checkbox-wrapper'>
                                <input type='checkbox' class='form-check-input' 
                                       ${candidate.programCoordinatorApprovalFlag || false ? 'checked' : ''}>
                                <span class='field-text'>
                                    ${candidate.programCoordinatorApprovalFlag || false ? 'Aprobado' : 'Pendiente'}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class='editable-field'>
                        <span class='field-label'>Decisión del Coordinador:</span>
                        <div class='field-value' data-field='program_coordinator_decision'>
                            <span class='field-text'>${candidate.programCoordinatorDecision || 'Sin decisión'}</span>
                            <textarea class='form-control form-control-sm' rows='2' maxlength='500'>${candidate.programCoordinatorDecision || ''}</textarea>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Grupos de Flag + Descripción -->
                    ${createPendingFieldGroup('Promoción y Admisiones', 'admissions_pending_flag', candidate.admissionsPendingFlag || false, 'admissions_pending_description', candidate.admissionsPendingDescription)}
                    
                    ${createPendingFieldGroup('Gestión Escolar', 'registrar_pending_flag', candidate.registrarPendingFlag || false, 'registrar_pending_description', candidate.registrarPendingDescription)}
                    
                    ${createPendingFieldGroup('Facultad de Ingeniería', 'engineering_faculty_pending_flag', candidate.engineeringFacultyPendingFlag || false, 'engineering_faculty_pending_description', candidate.engineeringFacultyPendingDescription)}
                    
                    ${createPendingFieldGroup('Jefe Posgrado', 'grad_chief_pending_flag', candidate.gradChiefPendingFlag || false, 'grad_chief_pending_description', candidate.gradChiefPendingDescription)}
                    
                    ${createPendingFieldGroup('Coordinador Programa', 'program_coordinator_pending_flag', candidate.programCoordinatorPendingFlag || false, 'program_coordinator_pending_description', candidate.programCoordinatorPendingDescription)}
                </div>

                <!-- Evidencias -->
                <div class='field-group'>
                    <h6><i class='fas fa-paperclip'></i> Evidencias</h6>
                    <div class='evidence-section'>
                        <div class='mb-3'>
                            <button class='btn btn-sm btn-primary upload-evidence' data-candidate-id='${candidate.id}'>
                                <i class='fas fa-upload'></i> Subir Evidencia
                            </button>
                        </div>
                        <div class='evidence-list' id='evidence-list-${candidate.id}'>
                            <p class='text-muted'>Cargando evidencias...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Crear campo de pendiente
    function createPendingField(
        label,
        field,
        value,
        descriptionField = null,
        descriptionValue = null
    ) {
        console.log(`Creando campo: ${label}, valor: ${value}, descripción: ${descriptionValue}`); // Debug

        let descriptionHtml = '';

        if (descriptionField) {
            descriptionHtml = `
                <div class='editable-field mt-2'>
                    <span class='field-label'>Descripción:</span>
                    <div class='field-value' data-field='${descriptionField}'>
                        <span class='field-text'>${descriptionValue || 'Sin descripción'}</span>
                        <textarea class='form-control form-control-sm' rows='2' maxlength='200'>${descriptionValue || ''}</textarea>
                        <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                            <i class='fas fa-edit'></i>
                        </button>
                    </div>
                </div>
            `;
        }

        return `
            <div class='editable-field'>
                <span class='field-label'>${label}:</span>
                <div class='field-value' data-field='${field}'>
                    <div class='checkbox-wrapper'>
                        <input type='checkbox' class='form-check-input' ${value ? 'checked' : ''}>
                        <span class='field-text'>${value ? 'Aprobado' : 'Pendiente'}</span>
                    </div>
                </div>
            </div>
            ${descriptionHtml}
        `;
    }

    // Crear grupo de campo pendiente con flag y descripción agrupados visualmente
    function createPendingFieldGroup(
        label,
        flagField,
        flagValue,
        descriptionField,
        descriptionValue
    ) {
        console.log(
            `Creando grupo: ${label}, flag: ${flagValue}, descripción: ${descriptionValue}`
        ); // Debug

        return `
            <div class='pending-group'>
                <div class='pending-group-header'>
                    <h6 class='pending-group-title'><i class='fas fa-clock'></i> ${label}</h6>
                </div>
                <div class='pending-group-content'>
                    <div class='editable-field pending-flag'>
                        <span class='field-label'>Estado:</span>
                        <div class='field-value' data-field='${flagField}'>
                            <div class='checkbox-wrapper'>
                                <input type='checkbox' class='form-check-input' ${flagValue ? 'checked' : ''}>
                                <span class='field-text'>${flagValue ? 'Aprobado' : 'Pendiente'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class='editable-field pending-description'>
                        <span class='field-label'>Descripción:</span>
                        <div class='field-value' data-field='${descriptionField}'>
                            <span class='field-text'>${descriptionValue || 'Sin descripción'}</span>
                            <textarea class='form-control form-control-sm' rows='2' maxlength='200'>${descriptionValue || ''}</textarea>
                            <button class='btn btn-sm btn-outline-primary btn-edit-field'>
                                <i class='fas fa-edit'></i>
                            </button>
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

    // Formatear fecha para input date
    function formatDateForInput(dateTimeStr) {
        if (!dateTimeStr) return '';
        const date = new Date(dateTimeStr);
        return date.toISOString().split('T')[0];
    }

    // Formatear fecha y hora para input datetime-local
    function formatDateTimeForInput(dateTimeStr) {
        if (!dateTimeStr) return '';
        const date = new Date(dateTimeStr);
        return date.toISOString().slice(0, 16);
    }

    // Cargar programas para el selector en detalles
    function loadProgramsForCandidate(candidateId, currentProgramId) {
        $.ajax({
            url: '',
            type: 'POST',
            data: { action: 'getPrograms' },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;

                if (res.success && Array.isArray(res.data)) {
                    const select = $(
                        `.candidate-details[data-candidate-id='${candidateId}'] .program-select`
                    );
                    select.empty();

                    res.data.forEach(function (program) {
                        const selected = program.id == currentProgramId ? 'selected' : '';
                        select.append(
                            `<option value="${program.id}" ${selected}>${program.name}</option>`
                        );
                    });
                }
            },
        });
    }

    // Cargar descripciones de status para el selector
    function loadStatusDescriptions(candidateId, currentStatus) {
        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getPrograms" },
            success: function (response) {
                let res = typeof response === "string" ? JSON.parse(response) : response;
                
                if (res.success && Array.isArray(res.data)) {
                    const select = $(`.candidate-details[data-candidate-id='${candidateId}'] .program-select`);
                    select.empty();
                    
                    res.data.forEach(function (program) {
                        const selected = program.id == currentProgramId ? 'selected' : '';
                        select.append(`<option value="${program.id}" ${selected}>${program.name}</option>`);
                    });
                }
            }
        });
    }

    // Cargar descripciones de status para el selector
    function loadStatusDescriptions(candidateId, currentStatus) {
        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getCandidateDescriptions" },
            success: function (response) {
                let res = typeof response === "string" ? JSON.parse(response) : response;
                
                if (res.success && Array.isArray(res.data)) {
                    const select = $(`.candidate-details[data-candidate-id='${candidateId}'] .status-select`);
                    select.empty();
                    
                    res.data.forEach(function (status) {
                        const selected = status.id == currentStatus ? 'selected' : '';
                        select.append(`<option value="${status.id}" ${selected}>${status.description}</option>`);
                    });
                }
            }
        });
    }

    // Cargar evidencias del candidato
    function loadCandidateEvidence(candidateId) {
        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getCandidateEvidence", candidateID: candidateId },
            success: function (response) {
                let res = typeof response === "string" ? JSON.parse(response) : response;
                const evidenceList = $(`#evidence-list-${candidateId}`);
                
                if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                    evidenceList.empty();
                    res.data.forEach(function(evidence) {
                        evidenceList.append(`
                            <div class='evidence-item mb-2'>
                                <i class='fas fa-file-alt text-primary'></i>
                                <a href='${evidence.path}' download='${evidence.name}' class='ml-2'>
                                    ${evidence.name}
                                </a>
                            </div>
                        `);
                    });
                } else {
                    evidenceList.html('<p class="text-muted">No hay evidencias disponibles.</p>');
                }
            },
            error: function() {
                $(`#evidence-list-${candidateId}`).html('<p class="text-danger">Error al cargar evidencias.</p>');
            }
        });
    }

    // Manejar subida de evidencias
    $(document).on('click', '.upload-evidence', function() {
        const candidateId = $(this).data('candidateId');
        const fileInput = $('<input type="file" accept="*/*">');
        
        fileInput.on('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('candidateID', candidateId);
            formData.append('action', 'uploadEvidence');
            
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $(`#evidence-list-${candidateId}`).html('<p class="text-info">Subiendo archivo...</p>');
                },
                success: function(response) {
                    let res = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (res.success) {
                        displayMessage($(`.candidate-details[data-candidate-id='${candidateId}']`), 'Evidencia subida correctamente', 'success');
                        loadCandidateEvidence(candidateId);
                    } else {
                        alert('Error al subir la evidencia.');
                        loadCandidateEvidence(candidateId);
                    }
                },
                error: function() {
                    alert('Error al procesar la solicitud.');
                    loadCandidateEvidence(candidateId);
                }
            });
        });
        
        fileInput.trigger('click');
    });

    // Manejar edición de campos
    $(document).on('click', '.btn-edit-field', function () {
        const button = $(this);
        const fieldValue = button.closest('.field-value');
        const isEditing = fieldValue.hasClass('editing');

        if (isEditing) {
            // Guardar cambios
            const candidateId = button.closest('.candidate-details').data('candidateId');
            const field = fieldValue.data('field');
            let value;

            if (
                fieldValue.find(
                    'input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="datetime-local"]'
                ).length
            ) {
                value = fieldValue.find('input').val();
            } else if (fieldValue.find('select').length) {
                value = fieldValue.find('select').val();
            } else if (fieldValue.find('textarea').length) {
                value = fieldValue.find('textarea').val();
            }
            
            // Validación especial para status
            if (field === 'status' && value == 2) { // 2 = inscrito
                const candidateDetails = button.closest('.candidate-details');
                const ulsaIdField = candidateDetails.find('[data-field="ulsa_id"] .field-text');
                const ulsaIdValue = ulsaIdField.text().trim();
                
                if (!ulsaIdValue || ulsaIdValue === 'Sin asignar') {
                    alert('No se puede inscribir el candidato. Debe tener una Clave ULSA asignada primero.');
                    // Revertir el cambio en el select
                    const select = fieldValue.find('select');
                    const originalValue = select.data('original-value');
                    select.val(originalValue);
                    return;
                }
            }
            
            // Actualizar en la base de datos
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'updateCandidateField',
                    candidateID: candidateId,
                    field: field,
                    value: value,
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
                            fieldValue.find('.field-text').text(selectedText);
                        } else {
                            fieldValue.find('.field-text').text(value || 'No especificado');
                        }

                        fieldValue.removeClass('editing');
                        button.html('<i class="fas fa-edit"></i>');

                        // Mostrar mensajes específicos para ciertos campos
                        if (field === 'program_id') {
                            const candidateDetails = button.closest('.candidate-details');
                            const statusField = candidateDetails.find('[data-field="status"] .field-text');
                            const statusText = statusField.text().trim().toLowerCase();
                            
                            if (statusText === 'Inscrito') {
                                displayMessage(candidateDetails, 'Programa actualizado en candidato y estudiante', 'success');
                            } else {
                                displayMessage(
                                    candidateDetails,
                                    'Programa actualizado en candidato',
                                    'success'
                                );
                            }
                        } else if (field === 'ulsa_id') {
                            const candidateId = button.closest('.candidate-details').data('candidateId');
                            displayMessage(button.closest('.candidate-details'), 'Clave ULSA actualizada correctamente', 'success');
                            // Revalidar el estado del dropdown de status
                            updateStatusDropdownState(candidateId);
                        } else if (field === 'status') {
                            const message = value == 2 
                                ? 'Candidato inscrito y agregado como estudiante' 
                                : value == 3
                                ? 'Candidato dado de baja'
                                : 'Status actualizado a pendiente';
                            displayMessage(button.closest('.candidate-details'), message, 'success');
                        }

                        // Recargar tabla si es necesario
                        if (
                            field === 'status' ||
                            field === 'first_name' ||
                            field === 'last_name' ||
                            field === 'program_id'
                        ) {
                            loadCandidatesTable();
                        }
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
            
            // Guardar valor original para el select
            const select = fieldValue.find('select');
            if (select.length) {
                select.data('original-value', select.val());
            }
            
            // Enfocar el campo de entrada
            const input = fieldValue.find('input, select, textarea');
            if (input.length) {
                input.focus();
            }
        }
    });

    // Manejar cambios en checkboxes
    $(document).on('change', '.field-value input[type="checkbox"]', function () {
        const checkbox = $(this);
        const fieldValue = checkbox.closest('.field-value');
        const candidateId = checkbox.closest('.candidate-details').data('candidateId');
        const field = fieldValue.data('field');
        const value = checkbox.is(':checked');
        
        // Actualizar texto inmediatamente
        const fieldText = fieldValue.find('.field-text');
        fieldText.text(value ? 'Aprobado' : 'Pendiente');
        
        // Actualizar en la base de datos
        $.ajax({
            url: '',
            type: 'POST',
            data: {
                action: 'updateCandidateField',
                candidateID: candidateId,
                field: field,
                value: value ? 'true' : 'false',
            },
            beforeSend: function () {
                checkbox.prop('disabled', true);
            },
            success: function (response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;

                if (res.success) {
                    // Mostrar mensaje de confirmación si es necesario
                } else {
                    // Revertir cambio si falla
                    checkbox.prop('checked', !value);
                    fieldText.text(!value ? 'Aprobado' : 'Pendiente');
                    alert('Error al actualizar: ' + (res.message || 'Error desconocido'));
                }
            },
            error: function () {
                // Revertir cambio si falla
                checkbox.prop('checked', !value);
                fieldText.text(!value ? 'Aprobado' : 'Pendiente');
                alert('Error al procesar la solicitud');
            },
            complete: function () {
                checkbox.prop('disabled', false);
            },
        });
    });

    // Verificar y actualizar estado del dropdown de status basado en ULSA ID
    function updateStatusDropdownState(candidateId) {
        const candidateDetails = $(`.candidate-details[data-candidate-id='${candidateId}']`);
        const ulsaIdField = candidateDetails.find('[data-field="ulsa_id"] .field-text');
        const statusSelect = candidateDetails.find('[data-field="status"] select');
        const statusFieldValue = candidateDetails.find('[data-field="status"]');
        
        const ulsaIdValue = ulsaIdField.text().trim();
        const hasUlsaId = ulsaIdValue && ulsaIdValue !== 'Sin asignar';
        const currentStatus = parseInt(statusSelect.val());
        
        if (!hasUlsaId && currentStatus !== 2) {
            // Si no tiene ULSA ID y no está inscrito, deshabilitar opción de inscrito
            statusSelect.find('option[value="2"]').prop('disabled', true).text('Inscrito (requiere Clave ULSA)');
        } else {
            // Si tiene ULSA ID o ya está inscrito, habilitar todas las opciones
            statusSelect.find('option[value="2"]').prop('disabled', false).text('Inscrito');
        }
    }

    // Manejar checkbox de clave ULSA
    $("#tieneClaveUlsa").change(function() {
        if ($(this).is(":checked")) {
            $("#claveUlsaGroup").slideDown();
            $("#claveUlsa").prop("required", true);
        } else {
            $("#claveUlsaGroup").slideUp();
            $("#claveUlsa").prop("required", false).val("");
        }
    });

    // Inicializar navegación
    setupBtnsGC("btn-crear");
    setupBtnsGC("btn-consultar");
    setupBtnsGC("btn-eliminar");

    // Mostrar sección de registro por defecto
    $("#btn-crear").click();
}); 