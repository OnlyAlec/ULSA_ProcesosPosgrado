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
                                <td class="icono-acciones text-center">
                                    ###
                                    ~~~
                            </tr>`;
                        const afiStatusBtn = student.afi
                            ? `<i class="fas fa-minus-square fa-lg statusAFI" data-ulsaID=${student.ulsaID}></i>`
                            : `<i class="fas fa-check-square fa-lg statusAFI" data-ulsaID=${student.ulsaID}></i>`;
                        if (!student.afi)
                            row = row.replace(
                                "~~~",
                                `<i class="fas fa-paper-plane sendEmail" data-ulsaID=${student.ulsaID}></i>`
                            );
                        else row = row.replace("~~~", "");

                        row = row.replace("###", afiStatusBtn);
                        tableBody.append(row);
                    });
                    setupActions();
                } else {
                    tableBody.append(
                        '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
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

    $("#selectMasterConfirm, #selectSpecialtyConfirm").on("input", function () {
        const value = String($(this).val())?.toUpperCase();
        const icon = $(this).closest(".datalist").find("i");

        value
            ? icon.removeClass("fa-search").addClass("fa-times")
            : icon.removeClass("fa-times").addClass("fa-search");
        const otherSelect = $(this).is("#selectMaster") ? "#selectSpecialty" : "#selectMaster";
        $(otherSelect).val("");
        $(otherSelect).closest(".datalist").find("i").removeClass("fa-times").addClass("fa-search");

        filterTableByCarrer(value, "tableStudents");
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
            const parent = button.parent();
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
                        ? `<i class="fas fa-minus-square fa-lg statusAFI" data-ulsaID=${ulsaID}></i>`
                        : `<i class="fas fa-check-square fa-lg statusAFI" data-ulsaID=${ulsaID}></i>`;
                    parent.html(newIcon);

                    if (!newStatus)
                        parent.append(
                            `<i class="fas fa-paper-plane fa-lg sendEmail" data-ulsaID=${ulsaID}></i>`
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
