$(function () {
    setupBtns("btn-gestor");

    $("#btn-gestor").on("click", function () {
        const button = $(this);
        const divError = $(".sectionsAFI");
        const tableContainer = $("#tableStudentsConfirm");
        const tableBody = tableContainer.find("tbody");

        $.ajax({
            url: "",
            type: "POST",
            data: { action: "getTableStudents" },
            beforeSend: function () {
                button.prop("disabled", true);
                tableContainer.hide();
                tableBody.empty();
            },
            success: function (response) {
                if (!response.success) {
                    displayMessage(divError, response.message, "error");
                    return;
                }

                if (response.data) {
                    // @ts-ignore
                    response.data.forEach((student) => {
                        let row = `<tr>
                                <th scope='row'>${student.ulsaID}</th>
                                <td>${student.firstName} ${student.lastName}</td>
                                <td>${student.carrer}</td>
                                <td>${student.email}</td>
                                <td>
                                    <div class="d-flex" style="gap: 8px;">
                                        <button class="btn ??? btn-sm text-white border-0 flex-fill statusAFI" data-ulsaID="${student.ulsaID}">
                                            ###
                                        </button>
                                        ~~~
                                    </div>
                                </td>
                            </tr>`;
                        const afiStatusStyle = student.afi ? 'btn-danger' : 'btn-success';
                        const afiStatusIcon = student.afi
                            ? `<i class="fas fa-minus-square fa-lg"></i>`
                            : `<i class="fas fa-check-square fa-lg"></i>`;
                        if (!student.afi)
                            row = row.replace(
                                "~~~",
                                `<button class="btn btn-info btn-sm text-white border-0 flex-fill sendEmail" data-email="${student.email}"><i class="fas fa-paper-plane"></i>`
                            );
                        else row = row.replace("~~~", "");

                        row = row.replace("???", afiStatusStyle);
                        row = row.replace("###", afiStatusIcon);
                        tableBody.append(row);
                    });
                    setupActions();
                } else {
                    tableBody.append(
                        '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
                    );
                }
                tableContainer.show();
                setupDatasets();
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

    $("#onlyMissing").on("click", function () {
        const tableContainer = $("#tableStudentsConfirm");
        const tableBody = tableContainer.find("tbody");

        tableBody
            .find("tr")
            .show()
            .filter(function () {
                return $(this).find("i").hasClass("fa-minus-square");
            })
            .hide();

        if (tableBody.find("tr:visible").length == 0)
            tableBody.append(
                '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
            );
    });
    $("#onlyConfirm").on("click", function () {
        const tableContainer = $("#tableStudentsConfirm");
        const tableBody = tableContainer.find("tbody");

        tableBody
            .find("tr")
            .show()
            .filter(function () {
                return $(this).find("i").hasClass("fa-check-square");
            })
            .hide();

        if (tableBody.find("tr:visible").length == 0)
            tableBody.append(
                '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
            );
    });
    $("#removeFilter").on("click", function () {
        const tableContainer = $("#tableStudentsConfirm");
        const tableBody = tableContainer.find("tbody");

        tableBody.find("tr").show();

        if (tableBody.find("tr:visible").length == 0)
            tableBody.append(
                '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
            );
    });
});

function setupActions() {
    $(".statusAFI")
        .off("click")
        .on("click", function () {
            const button = $(this);
            const ulsaID = button.data("ulsaid");
            const divError = $(".sectionsAFI");

            $.ajax({
                url: "",
                type: "POST",
                data: { action: "setStatus", ulsaID: ulsaID },
                beforeSend: function () {
                    button.prop("disabled", true);
                },
                success: function (response) {
                    if (!response.success) {
                        displayMessage(divError, response.message, "error");
                        return;
                    }
                    const newStatus = response.data.newStatus;
                    const newIcon = newStatus
                        ? `<i class="fas fa-minus-square fa-lg"></i>`
                        : `<i class="fas fa-check-square fa-lg"></i>`;
                    const newColor = newStatus ? "btn-danger" : "btn-success";

                    button.html(newIcon);
                    button.removeClass("btn-success btn-danger");
                    button.addClass(newColor);

                    if (newStatus) button.parent().parent().find(".sendEmail").remove();
                    else
                        button
                            .parent()
                            .append(
                                `<button class="btn btn-info btn-sm text-white border-0 flex-fill sendEmail" data-email=${response.data.email}><i class= "fas fa-paper-plane"></i></button>`
                            );
                    setupActions();
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

    $(".sendEmail")
        .off("click")
        .on("click", function () {
            const button = $(this);
            const buttonConfirm = button.parent().find(".statusAFI");
            const ulsaID = button.data("ulsaid");
            const divError = $(".sectionsAFI");

            $.ajax({
                url: "",
                type: "POST",
                data: { action: "sendEmail", ulsaID: ulsaID },
                beforeSend: function () {
                    $(".alert").remove();
                    button.prop("disabled", true);
                    buttonConfirm.prop("disabled", true);
                },
                success: function (response) {
                    if (!response.success || !response.data.delivered) {
                        displayMessage(
                            divError,
                            response.message ?? "No se pudo mandar el correo",
                            "error"
                        );
                        return;
                    }
                    displayMessage(
                        divError,
                        "Correo enviado correctamente: " + response.data.receipt
                    );
                    divError[0].scrollIntoView({
                        behavior: "smooth",
                        block: "start",
                        inline: "nearest",
                    });
                },
                error: function (xhr) {
                    const errorMsg = "Error al procesar la solicitud";
                    displayMessage(divError, errorMsg, "error");
                },
                complete: function () {
                    button.prop("disabled", false);
                    buttonConfirm.prop("disabled", false);
                },
            });
        });
}
