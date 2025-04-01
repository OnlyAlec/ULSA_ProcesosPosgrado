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
                console.log(response);
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

    $("#btn-consultar").on("click", function () {
        const button = $(this);
        let tableBody = $("#tableStudents tbody");    
    
        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getTableStudents" },
            beforeSend: function () {
                button.prop("disabled", true);
                tableBody.empty();
            },
            success: function (response) {
                if (response.data) {
                    response.data.forEach((student) => {
                        let row = `<tr>
                                <td>${student.ulsaID}</td>
                                <td>${student.firstName} ${student.lastName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                            </tr>`;
                        tableBody.append(row);
                    });
                } else {
                    tableBody.append(
                        '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
                    );
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseText || "Error al procesar la solicitud";
                displayMessage(divError, errorMsg, "error");
            },
            complete: function () {
                button.prop("disabled", false);
            },
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