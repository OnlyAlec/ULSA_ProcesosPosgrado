// $(function () {
//     // Cargar datos de la tabla
//     loadProgramSubjects();

//     function loadProgramSubjects() {
//         $.ajax({
//             url: "", // Actualiza con la ruta de tu script PHP
//             type: "POST",
//             data: { action: "getProgramSubjects" },
//             dataType: "json",
//             success: function (response) {
//                 if (!response.success || !Array.isArray(response.data)) {
//                     displayMessage($(".sectionsFA"), "Ocurrió un problema", "error");
//                     return;
//                 }

//                 const tbody = $("#programsTableBody");
//                 tbody.empty();

//                 response.data.forEach((program) => {
//                     const row = `<tr data-id="${program.id}">
//                                     <td>${program.program_name}</td>
//                                     <td>${program.subject_name}</td>
//                                     <td>${program.professor_name}</td>
//                                     <td>
//                                         <button class="btn btn-sm toggle-signed ${program.has_signed ? 'btn-success' : 'btn-danger'}">
//                                             <i class="fas ${program.has_signed ? 'fa-check' : 'fa-times'}"></i>
//                                         </button>
//                                         <button class="btn btn-sm toggle-absent ${program.will_be_absent ? 'btn-warning' : 'btn-secondary'}">
//                                             <i class="fas ${program.will_be_absent ? 'fa-user-times' : 'fa-user-check'}"></i>
//                                         </button>
//                                         <button class="btn btn-sm btn-info add-comment">
//                                             <i class="fas fa-comment"></i>
//                                         </button>
//                                         <button class="btn btn-sm btn-primary upload-evidence">
//                                             <i class="fas fa-upload"></i>
//                                         </button>
//                                     </td>
//                                 </tr>`;
//                     tbody.append(row);
//                 });
//             },
//             error: function () {
//                 displayMessage($(".sectionsFA"), "Error al procesar la solicitud", "error");
//             },
//         });
//     }

//     // Funcionalidad de los botones
//     $(document).on("click", ".toggle-signed", function () {
//         const button = $(this);
//         const row = button.closest("tr");
//         const id = row.data("id");
//         const newState = !button.hasClass("btn-success");

//         $.ajax({
//             url: "", // Actualiza con la ruta de tu script PHP
//             type: "POST",
//             data: { action: "toggleSigned", id: id, state: newState },
//             success: function (response) {
//                 if (response.success) {
//                     button.toggleClass("btn-success btn-danger");
//                     button.find("i").toggleClass("fa-check fa-times");
//                 } else {
//                     alert("Error al actualizar el estado de firma.");
//                 }
//             },
//             error: function () {
//                 alert("Error al procesar la solicitud.");
//             },
//         });
//     });

//     $(document).on("click", ".toggle-absent", function () {
//         const button = $(this);
//         const row = button.closest("tr");
//         const id = row.data("id");
//         const newState = !button.hasClass("btn-warning");

//         $.ajax({
//             url: "", // Actualiza con la ruta de tu script PHP
//             type: "POST",
//             data: { action: "toggleAbsent", id: id, state: newState },
//             success: function (response) {
//                 if (response.success) {
//                     button.toggleClass("btn-warning btn-secondary");
//                     button.find("i").toggleClass("fa-user-times fa-user-check");
//                 } else {
//                     alert("Error al actualizar el estado de ausencia.");
//                 }
//             },
//             error: function () {
//                 alert("Error al procesar la solicitud.");
//             },
//         });
//     });

//     $(document).on("click", ".add-comment", function () {
//         const row = $(this).closest("tr");
//         const id = row.data("id");
//         const comment = prompt("Escribe tu comentario:");

//         if (comment) {
//             $.ajax({
//                 url: "", // Actualiza con la ruta de tu script PHP
//                 type: "POST",
//                 data: { action: "addComment", id: id, comment: comment },
//                 success: function (response) {
//                     if (response.success) {
//                         alert("Comentario agregado correctamente.");
//                     } else {
//                         alert("Error al agregar el comentario.");
//                     }
//                 },
//                 error: function () {
//                     alert("Error al procesar la solicitud.");
//                 },
//             });
//         }
//     });

//     $(document).on("click", ".upload-evidence", function () {
//         const row = $(this).closest("tr");
//         const id = row.data("id");
//         const fileInput = $('<input type="file" accept="*/*">');

//         fileInput.on("change", function () {
//             const file = this.files[0];
//             const formData = new FormData();
//             formData.append("file", file);
//             formData.append("id", id);
//             formData.append("action", "uploadEvidence");

//             $.ajax({
//                 url: "", // Actualiza con la ruta de tu script PHP
//                 type: "POST",
//                 data: formData,
//                 processData: false,
//                 contentType: false,
//                 success: function (response) {
//                     if (response.success) {
//                         alert("Evidencia subida correctamente.");
//                     } else {
//                         alert("Error al subir la evidencia.");
//                     }
//                 },
//                 error: function () {
//                     alert("Error al procesar la solicitud.");
//                 },
//             });
//         });

//         fileInput.trigger("click");
//     });
// });
