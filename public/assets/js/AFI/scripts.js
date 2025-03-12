$(document).ready(function () {
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
                form.find('.alert').remove();
                form.find('button').prop('disabled', true);
            },
            success: function (response) {
                displayMessage(form, "Archivos procesados correctamente");
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
                displayMessage(form, errorMsg);
            },
            complete: function () {
                form.find('button').prop('disabled', false);
            }
        });
    });

    function displayMessage(pos, message, type = 'success') {
        const newDiv = document.createElement('div');
        newDiv.className = type == 'success' ? 'alert alert-success' : 'alert alert-danger';
        newDiv.innerHTML = message;
        pos.before(newDiv);
    }
});