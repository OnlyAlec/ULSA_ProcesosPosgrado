$(document).ready(function () {
    // Manejo general de envío de formularios
    $("form").submit(function (e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);

        $.ajax({
            url: "", // La URL se procesa en el mismo archivo PHP (index.php)
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $(".alert").remove(); // Limpia mensajes anteriores
                form.find("button[type='submit']").prop("disabled", true);
            },
            success: function (response) {
                let res;
                try {
                    res = typeof response === "string" ? JSON.parse(response) : response;
                } catch (e) {
                    console.error("Error al parsear la respuesta:", response);
                    displayMessage(form, "Respuesta inválida del servidor.", "error");
                    return;
                }

                if (res.success) {
                     displayMessage(form, "Acción realizada correctamente");
                     // Si el registro fue exitoso y es el formulario de registro único, limpiar campos
                     if (form.find('input[name="action"]').val() === 'registerOneCandidate') {
                         form[0].reset();
                         form.find(".datalist-input").val(""); // Limpiar campo datalist
                     } else if (form.find('input[name="action"]').val() === 'deleteOneStudent') { // Asumiendo que el action para eliminar único es deleteOneStudent basado en GA
                         form[0].reset();
                     }

                } else {
                     const errorMsg = res.message || "Error al procesar la solicitud";
                     displayMessage(form, errorMsg, "error");
                }
                 console.log(response); // Log completo de la respuesta
            },
            error: function (xhr) {
                const errorMsg = "Error al procesar la solicitud. Código: " + xhr.status;
                displayMessage(form, errorMsg, "error");
                 console.error("Error AJAX:", xhr.responseText); // Log del error
            },
            complete: function () {
                form.find("button[type='submit']").prop("disabled", false);
            },
        });
    });

    // Función para mostrar mensajes de alerta
    function displayMessage(pos, message, type = "success") {
        // Asegúrate de que 'pos' es un elemento jQuery válido antes de usar .before()
        if (pos && pos.length > 0) {
            const newDiv = document.createElement("div");
            newDiv.className = type == "success" ? "alert alert-success mt-3" : "alert alert-danger mt-3";
            newDiv.innerHTML = message;
            pos.before(newDiv);
        } else {
            console.error("Elemento de posición inválido para mostrar mensaje:", pos);
        }
    }

    // Configuración de botones de navegación para mostrar/ocultar secciones
    function setupBtnsGC(name) {
        if (!name) {
            console.error("Missing name - setupBtnsGC");
            return;
        }

        $("#" + name).on("click", function () {
            $(".alert").remove(); // Limpia mensajes al cambiar de sección
            $(".sectionGC").hide(); // Oculta todas las secciones
            $(".sectionsGC button").removeClass("btn-primary").addClass("btn-outline-primary"); // Desactiva todos los botones

            $(this).removeClass("btn-outline-primary").addClass("btn-primary"); // Activa el botón actual

            const targetSectionId = name.split("-").slice(1).join("-"); // Obtiene el id de la sección objetivo (crear, consultar, eliminar)
            $("#" + targetSectionId).show(); // Muestra la sección objetivo

            // Lógica específica para cada sección al mostrarla
            if (targetSectionId === 'crear') {
                loadProgramOptions(); // Carga opciones de programas al mostrar la sección de registro
            } else if (targetSectionId === 'consultar') {
                 loadCandidatesTable(); // Carga la tabla de candidatos al mostrar la sección de consulta
            }
            // No se necesita lógica especial al mostrar la sección 'eliminar' por ahora
        });
    }

    // Función para ocultar todas las secciones de GC
    function hideSectionsGC() {
        $(".sectionGC").each(function () {
            $(this).hide();
        });
    }

    // Lógica para cargar opciones de programas académicos en el datalist
    function loadProgramOptions() {
         // Verificar si las opciones ya fueron cargadas para evitar llamadas duplicadas
         if ($('#programaAcademicoOptions li').length > 0) {
             return; // Opciones ya cargadas
         }
        $.ajax({
            url: "", // Asume que la acción getPrograms se maneja en el mismo index.php
            type: "POST",
            data: { action: "getPrograms" },
            success: function (response) {
                let res;
                try {
                     res = typeof response === "string" ? JSON.parse(response) : response;
                } catch (e) {
                     console.error("Error al parsear la respuesta de programas:", response);
                     return;
                }


                if (res.success && Array.isArray(res.data)) {
                    const optionsList = $('#programaAcademicoOptions');
                    optionsList.empty(); // Limpiar opciones actuales
                    res.data.forEach(function (program) {
                        // Asume que cada programa en res.data tiene propiedades 'id' y 'name'
                        optionsList.append(`<li data-value="${program.id}">${program.name}</li>`);
                    });
                     setupDatalist('#programaAcademico', '#programaAcademicoOptions'); // Re-configurar datalist si es necesario

                } else {
                    console.error("Error al obtener programas:", res.message || "Respuesta inesperada");
                    const optionsList = $('#programaAcademicoOptions');
                    optionsList.empty();
                    optionsList.append(`<li>Error al cargar programas</li>`);
                }
            },
            error: function (xhr) {
                console.error("Error AJAX al cargar programas:", xhr.responseText);
                 const optionsList = $('#programaAcademicoOptions');
                 optionsList.empty();
                 optionsList.append(`<li>Error de conexión</li>`);
            }
        });
    }

     // Lógica para cargar la tabla de candidatos
     function loadCandidatesTable() {
         const tableBody = $("#tableCandidates tbody"); // Asume que hay una tabla con id="tableCandidates"
         if (tableBody.length === 0) {
             console.error("No se encontró el tbody de la tabla de candidatos.");
             return;
         }

         $.ajax({
             url: "", // Asume que la acción getTableCandidates se maneja en el mismo index.php
             type: "POST",
             data: { action: "getTableCandidates" }, // TODO: Implement getTableCandidates action in PHP
             beforeSend: function() {
                 tableBody.empty().html('<tr><td colspan="5" class="text-center">Cargando...</td></tr>'); // Mostrar mensaje de carga
             },
             success: function (response) {
                 let res;
                 try {
                     res = typeof response === "string" ? JSON.parse(response) : response;
                 } catch (e) {
                     console.error("Error al parsear la respuesta de candidatos:", response);
                     tableBody.empty().html('<tr><td colspan="5" class="text-center">Error al cargar datos.</td></tr>');
                     return;
                 }

                 tableBody.empty(); // Limpiar filas actuales

                 if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                     res.data.forEach(function (candidate) {
                         // Asume que cada candidato en res.data tiene propiedades como claveUlsa, nombreCompleto, programa, correo
                         // TODO: Ajustar las propiedades según la estructura real de los datos del candidato
                         let row = `<tr>
                                 <td>${candidate.folioAdmision || ''}</td>
                                 <td>${candidate.nombre || ''} ${candidate.apellidos || ''}</td>
                                 <td>${candidate.programaAcademico || ''}</td>
                                 <td>${candidate.correo1 || ''}</td>
                                 <td>${candidate.celular || ''}</td>
                                 <!-- Añadir más columnas si es necesario -->
                             </tr>`;
                         tableBody.append(row);
                     });
                 } else {
                     tableBody.html('<tr><td colspan="5" class="text-center">No se encontraron candidatos.</td></tr>'); // Mostrar mensaje si no hay datos
                 }
             },
             error: function (xhr) {
                 console.error("Error AJAX al cargar tabla de candidatos:", xhr.responseText);
                 tableBody.empty().html('<tr><td colspan="5" class="text-center">Error al cargar datos.</td></tr>');
             }
         });
     }

    // TODO: Implementar setupDatalist function or adapt the existing one from util.js if available
    // This function is needed to make the datalist work like a searchable dropdown
     function setupDatalist(inputSelector, optionsSelector) {
         const input = $(inputSelector);
         const optionsList = $(optionsSelector);

         input.on('focus', function() {
             optionsList.show();
         }).on('blur', function() {
             // Pequeño retraso para permitir clic en opciones
             setTimeout(function() {
                 optionsList.hide();
             }, 100);
         }).on('input', function() {
             const searchTerm = input.val().toLowerCase();
             optionsList.find('li').each(function() {
                 const text = $(this).text().toLowerCase();
                 if (text.includes(searchTerm)) {
                     $(this).show();
                 } else {
                     $(this).hide();
                 }
             });
             optionsList.show(); // Asegura que la lista permanezca visible mientras se escribe
         });

         optionsList.on('mousedown', 'li', function(event) {
             // Usar mousedown en lugar de click para que funcione con el blur del input
             const selectedValue = $(this).text();
             const dataValue = $(this).data('value');
             input.val(selectedValue);
             // Opcional: almacenar el valor subyacente (id) si es necesario
             // input.data('selected-value', dataValue);
              input.trigger('change'); // Trigger change event after selecting a value
             optionsList.hide();
             event.preventDefault(); // Previene el blur del input al hacer clic en la opción
         });

          // Cerrar lista si se hace clic fuera
          $(document).on('click', function(event) {
              if (!$(event.target).closest(inputSelector).length && !$(event.target).closest(optionsSelector).length) {
                  optionsList.hide();
              }
          });
     }


    // Inicializar la configuración de los botones de navegación
    setupBtnsGC("btn-crear");
    setupBtnsGC("btn-consultar");
    setupBtnsGC("btn-eliminar");

    // Mostrar la sección de registro por defecto al cargar la página
    hideSectionsGC(); // Asegura que todas estén ocultas primero
    $("#crear").show();
     $("#btn-crear").removeClass("btn-outline-primary").addClass("btn-primary");

    // Cargar opciones de programas al cargar la página si la sección 'crear' es la visible por defecto
    loadProgramOptions();

});

// Código para mostrar el nombre del archivo seleccionado (adaptado de GD/scripts.js si aplica)
$(function () {
    // Esta función podría no ser necesaria en GC/index.php a menos que añadas carga por Excel
    // Si decides añadir carga por Excel más adelante, descomenta y adapta esto:
    /*
    $(".custom-file-input").on("change", function (e) {
        const fileName = $(e.target).prop("files")[0]?.name
            ? $(e.target).prop("files")[0].name.length > 70
                ? $(e.target).prop("files")[0].name.substring(0, 68) + "..."
                : $(e.target).prop("files")[0].name
            : "Seleccionar archivo...";
        $(e.target).next().text(fileName);
    });
    */
});

// TODOs adicionales:
// - Asegurar que la función setupDatalist() en util.js (si existe y es global) sea utilizada en lugar de la aquí definida si es idéntica. Si no existe, esta implementación básica debería funcionar, pero una versión más robusta en util.js sería ideal.
// - Implementar las acciones PHP 'getTableCandidates' y verificar la estructura de datos devuelta para la función loadCandidatesTable.
// - Implementar las acciones PHP para eliminar candidato único y todos los candidatos.
// - Considerar añadir validación de formulario en el lado del cliente antes de enviar la solicitud AJAX.