$('#programType').on("change", function () {
    const selectedOption = $(this).val();
    $.ajax({
        url: '',
        type: 'POST',
        data: { action: selectedOption },
        success: function (response) {
            // if (!response.success) {
            //     displayMessage($('.sectionsAFI'), response.message, 'error');
            //     return;
            // }
            if (!Array.isArray(response))
                response = Object.values(response);

            if (response.length == 0) {
                displayMessage($('.sectionsAFI'), "No hay áreas disponibles para el tipo de programa seleccionado", 'error');
                return;
            }
            $('#programArea').empty();
            const option = document.createElement("option");
            option.text = "Seleccione un tipo de programa primero";
            $('#programArea').append(option);

            response.forEach(element => {
                const option = document.createElement("option");
                option.value = element;
                option.text = element;
                $('#programArea').append(option);
            });

            $("#filterArea").show();
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
            displayMessage($('.sectionsAFI'), errorMsg, 'error');
        }
    });
});