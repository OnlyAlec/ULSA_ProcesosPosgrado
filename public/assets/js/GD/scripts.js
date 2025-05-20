$(document).ready(function () {
    $("form").submit(function (e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);

        $.ajax({
            url: "",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $(".alert").remove();
                form.find("button").prop("disabled", true);
            },
            success: function (response) {
                displayMessage(form, "Acción realizada correctamente");
                console.log(response);
            },
            error: function (xhr) {
                const errorMsg = "Error al procesar la solicitud";
                displayMessage(form, errorMsg, "error");
            },
            complete: function () {
                form.find("button").prop("disabled", false);
            },
        });
    });

    $("#btn-consultar").on("click", function () {
        const button = $(this);
        const tableContainer = $("#tableProfessors");
        const tableBody = tableContainer.find("tbody");

        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getTableProfessor" },
            beforeSend: function () {
                button.prop("disabled", true);
                tableContainer.hide();
                tableBody.empty();
            },
            success: function (response) {
                console.log(response);
                if (response.data) {
                    response.data.forEach((professor) => {
                        let row = `<tr>
                                <td>${professor.ulsaID}</td>
                                <td>${professor.firstName} ${professor.lastName}</td>
                                <td>${professor.email}</td>
                                <td class="text-center">
                                <button class='btn-view btn btn-sm btn-outline-primary' data-id='${professor.ulsaID}'>
                                    <i class='fas fa-eye'></i>
                                </button>
                                </td>
                            </tr>`;
                        tableBody.append(row);
                    });
                } else {
                    tableBody.append(
                        '<tr><td colspan="5" class="text-center">No se encontraron profesores</td></tr>'
                    );
                }
                tableContainer.show();
            },
            error: function (xhr) {
                const errorMsg = "Error al procesar la solicitud";
                displayMessage(divError, errorMsg, "error");
            },
            complete: function () {
                button.prop("disabled", false);
            },
        });
    });

    function displayMessage(pos, message, type = "success") {
        const newDiv = document.createElement("div");
        newDiv.className = type == "success" ? "alert alert-success" : "alert alert-danger";
        newDiv.innerHTML = message;
        pos.before(newDiv);
    }

    $(document).on("click", ".btn-view", function () {
        const button = $(this);
        const icon = button.find("i");
        const professorUlsaId = $(this).data("id");
        let professorId = "";
        const row = $(this).closest("tr");
        const existingCard = row.next(".professor-card");

        if (existingCard.length) {
            existingCard.remove();
            icon.removeClass("fa-eye-slash").addClass("fa-eye");
        } else {
            $.ajax({
                url: "",
                type: "POST",
                data: { action: "getProfessorDetails", ulsaID: professorUlsaId },
                success: function (response) {
                    let res = typeof response === "string" ? JSON.parse(response) : response;
                    let content = "Sin materias asignadas";
                    if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                        content = '<ul style="list-style: none; padding-left: 0;">';
                        res.data.forEach((item) => {
                            professorId = item.professor_id;
                            content += `<li style="padding: 6px 0; border-bottom: 1px solid #ddd;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                <strong>Programa:</strong> ${item.program_name}<br>
                                <strong>Materia:</strong> ${item.subject_name}
                                </div>
                                <button class='btn-delete btn btn-sm btn-outline-danger' 
                                        data-professor-id='${item.professor_id}' 
                                        data-subject-id='${item.subject_id}' 
                                        data-program-id='${item.program_id}'>
                                <i class='fas fa-trash'></i>
                                </button>
                            </div>
                            </li>`;
                        });
                        content += "</ul>";
                    }

                    const card = `
                    <tr class='professor-card'>
                    <td colspan='4'>
                        <div class='card shadow-sm border-0' style='padding: 1.5rem; background-color: #f9f9f9; border-radius: 0.75rem;'>
                            <h5 class='mb-3'>Materias asignadas</h5>
                            ${content}
                            <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 10px;">
                                <button class='btn-add btn btn-sm btn-outline-success' data-professor-id='${professorId}'>
                                    <i class='fas fa-plus'></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    </tr>`;
                    row.after(card);
                    icon.removeClass("fa-eye").addClass("fa-eye-slash");
                },
                error: function () {
                    const card = `<tr class='professor-card'><td colspan='4'><div class='card'>Error al obtener los detalles del profesor.</div></td></tr>`;
                    row.after(card);
                    icon.removeClass("fa-eye").addClass("fa-eye-slash");
                },
            });
        }
    });

    $(document).on("click", ".btn-delete", function () {
        const button = $(this);
        const professorId = button.data("professor-id");
        const subjectId = button.data("subject-id");
        const programId = button.data("program-id");
        const listItem = button.closest("li");

        $.ajax({
            url: "",
            type: "POST",
            data: {
                action: "deleteProgramSubject",
                professorId: professorId,
                subjectId: subjectId,
                programId: programId,
            },
            success: function (response) {
                let res = typeof response === "string" ? JSON.parse(response) : response;
                if (res.success === true) {
                    listItem.remove();
                } else {
                    alert("Error al eliminar el registro.");
                }
            },
            error: function () {
                alert("Error al procesar la solicitud de eliminación.");
            },
        });
    });

    $(document).on("click", ".btn-add", function () {
        const button = $(this);
        const card = button.closest(".card");
        const newRow = `
        <li class='new-assignment'>
            <div class="select-group">
                <label class="form-label fw-bold text-primary">Programa</label>
                <select class='form-select program-select shadow-sm'>
                    <option value=''>Seleccionar programa</option>
                </select>
            </div>
            
            <div class="select-group">
                <label class="form-label fw-bold text-primary">Materia</label>
                <select class='form-select subject-select shadow-sm'>
                    <option value=''>Seleccionar materia</option>
                </select>
            </div>
        
            <div class="button-container">
                <button class='btn-ok btn btn-primary btn-sm' disabled>
                    <i class='fas fa-check me-1'></i>Confirmar
                </button>
            </div>
        </li>`;

        card.find("ul").append(newRow);

        // Llenar las opciones de programas y materias
        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getPrograms" },
            success: function (response) {
                let res = typeof response === "string" ? JSON.parse(response) : response;
                if (res.success) {
                    const programSelect = card.find(".program-select");
                    res.data.forEach((program) => {
                        programSelect.append(
                            `<option value='${program.id}'>${program.name}</option>`
                        );
                    });
                }
            },
        });

        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getSubjects" },
            success: function (response) {
                let res = typeof response === "string" ? JSON.parse(response) : response;
                if (res.success) {
                    const subjectSelect = card.find(".subject-select");
                    res.data.forEach((subject) => {
                        subjectSelect.append(
                            `<option value='${subject.id}'>${subject.name}</option>`
                        );
                    });
                }
            },
        });

        // Habilitar o deshabilitar el botón OK
        card.on("change", ".program-select, .subject-select", function () {
            const programSelected = card.find(".program-select").val();
            const subjectSelected = card.find(".subject-select").val();
            const okButton = card.find(".btn-ok");
            if (programSelected && subjectSelected) {
                okButton.prop("disabled", false);
            } else {
                okButton.prop("disabled", true);
            }
        });

        // Manejar el clic en el botón OK
        card.on("click", ".btn-ok", function () {
            const programId = card.find(".program-select").val();
            const subjectId = card.find(".subject-select").val();
            const professorId = button.data("professor-id");
            const listItem = $(this).closest("li");

            $.ajax({
                url: "",
                type: "POST",
                data: {
                    action: "addProgramSubject",
                    professorId: professorId,
                    subjectId: subjectId,
                    programId: programId,
                },
                success: function (response) {
                    let res = typeof response === "string" ? JSON.parse(response) : response;

                    if (res.success) {
                        listItem.remove();
                        const newRow = `<li style='padding: 6px 0; border-bottom: 1px solid #ddd;'>
                            <div style='display: flex; justify-content: space-between; align-items: center;'>
                                <div>
                                <strong>Programa:</strong> ${res.data.program.name}<br>
                                <strong>Materia:</strong> ${res.data.subject.name}
                                </div>
                                <button class='btn-delete btn btn-sm btn-outline-danger' 
                                        data-professor-id='${professorId}' 
                                        data-subject-id='${subjectId}' 
                                        data-program-id='${programId}'>
                                <i class='fas fa-trash'></i>
                                </button>
                            </div>
                        </li>`;
                        card.find("ul").append(newRow);
                    } else {
                        alert("Error al asignar la materia y programa.");
                    }
                },
                error: function () {
                    alert("Error al procesar la solicitud de asignación.");
                },
            });
        });
    });
});

$(function () {
    $(".custom-file-input").on("change", function (e) {
        const fileName = $(e.target).prop("files")[0]?.name
            ? $(e.target).prop("files")[0].name.length > 70
                ? $(e.target).prop("files")[0].name.substring(0, 68) + "..."
                : $(e.target).prop("files")[0].name
            : "Seleccionar archivo...";
        $(e.target).next().text(fileName);
    });
});

function setupBtnsGD(name) {
    if (!name) {
        throw new Error("Missing name - setupBtnsGD");
    }

    $("#" + name).on("click", function () {
        $(".alert").remove();
        $(".forms-result").hide();
        $(".sectionsGD button").removeClass("btn-primary").addClass("btn-outline-primary");
        $(this).removeClass("btn-outline-primary").addClass("btn-primary");

        const div = name.split("-").slice(1).join("-");
        hideSectionsGD();
        $("#" + div).show();
    });
}

function hideSectionsGD() {
    $(".sectionGD").each(function () {
        $(this).hide();
    });
}

// Llamadas para inicializar
setupBtnsGD("btn-crear");
setupBtnsGD("btn-consultar");
setupBtnsGD("btn-eliminar");
