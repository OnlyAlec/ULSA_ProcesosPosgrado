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
                $('.alert').remove();
                form.find('button').prop('disabled', true);
            },
            success: function (response) {
                displayMessage(form, "Acción realizada correctamente");
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || 'Error al procesar la solicitud';
                displayMessage(form, errorMsg, 'error');
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

function showSection(section) {
    document.querySelectorAll('.section').forEach(div => div.classList.add('d-none'));
    document.getElementById('section-' + section).classList.remove('d-none');

    document.querySelectorAll('.btn-group button').forEach(btn => btn.classList.remove('btn-dark'));
    document.querySelectorAll('.btn-group button').forEach(btn => btn.classList.add('btn-primary'));
    document.getElementById('btn-' + section).classList.add('btn-dark');
}

document.addEventListener("DOMContentLoaded", function() {
    showSection('crear');
});

window.showSection = showSection;