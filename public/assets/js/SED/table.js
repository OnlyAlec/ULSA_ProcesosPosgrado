$('#programType').on("change", function () {
    const selectedOption = $(this).val();
    const table = $('#studentsTable');
    const tbody = table.find('tbody');
    const rows = tbody.find('tr');
    rows.show();

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

            if (selectedOption != '') {
                $("#filterArea").show();
            } else {
                $("#filterArea").hide();
            }
        },
        error: function (xhr) {
            const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
            displayMessage($('.sectionsAFI'), errorMsg, 'error');
        }
    });
});

$('#programArea').on("change", function () {
    const selectedOption = $(this).val();
    const table = $('#studentsTable');
    const tbody = table.find('tbody');
    const rows = tbody.find('tr');
    rows.each(function () {
        const row = $(this);
        const area = row.data("carrer").toUpperCase();
        if (selectedOption === 'Seleccione un área primero' || area === selectedOption.toUpperCase()) {
            row.show();
        } else {
            row.hide();
        }
    });
});

$(".studentCheckbox").on("change", function () {
    const checked = $(this).is(":checked");
    const row = $(this).closest("tr");
    if (checked) {
        row.addClass("selected");
        $("#confirmChanges").prop("disabled", false);
        $("#cancelChanges").prop("disabled", false);
    } else {
        row.removeClass("selected");
        if ($(".studentCheckbox:checked").length === 0) {
            $("#confirmChanges").prop("disabled", true);
            $("#cancelChanges").prop("disabled", true);
        }
    }
});

$("#selectAll").on("change", function () {
    const checked = $(this).is(":checked");
    $(".studentCheckbox").prop("checked", checked);
    $(".studentCheckbox").trigger("change");
});
