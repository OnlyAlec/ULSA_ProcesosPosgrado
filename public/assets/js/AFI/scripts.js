window.setupBtns = setupBtns;
window.hideSections = hideSections;
window.displayMessage = displayMessage;
window.filterTableByCarrer = filterTableByCarrer;

$(function () {
    $(document).on("click", function (e) {
        if (!$(e.target).closest(".datalist").length) {
            $(".datalist ul").css("display", "none");
        }
    });

    $(".custom-file-input").on("change", function (e) {
        const fileName = $(e.target).prop("files")[0]?.name
            ? $(e.target).prop("files")[0].name.length > 70
                ? $(e.target).prop("files")[0].name.substring(0, 68) + "..."
                : $(e.target).prop("files")[0].name
            : "Seleccionar archivo...";
        $(e.target).next().text(fileName);
    });

    $(".datalist-input").on("click", function (e) {
        e.stopPropagation();
        const list = $(this).closest(".datalist").find("ul");
        list.css("display", list.css("display") === "none" ? "block" : "none");
    });

    $(".datalist li:not(.not-selectable)").on("click", function () {
        const input = $(this).closest(".datalist").find(".datalist-input");
        input.val($(this).text()).trigger("input");
        $(this).closest("ul").css("display", "none");
    });

    $(".datalist i").on("click", function () {
        const input = $(this).closest(".datalist").find(".datalist-input");
        input.val("").trigger("input");
        $(this).removeClass("fa-times").addClass("fa-search");
    });
});

/**
 * @param {string} name
 */
function setupBtns(name) {
    if (name == "" || name == undefined) {
        throw new Error("Missing name - setupBtns");
    }

    $("#" + name).on("click", function () {
        $(".alert").remove();
        $(".forms-result").hide();
        $(".sectionsAFI button").removeClass("btn-primary").addClass("btn-outline-primary");
        $(this).removeClass("btn-outline-primary").addClass("btn-primary");
        const div = name.split("-").slice(1).join("-");
        if (name.split("-").length <= 2) hideSections();
        else hideSections(true);
        $("#" + div).show();
    });
}

function hideSections(subsection = false) {
    const className = subsection ? ".subSectionAFI" : ".sectionAFI";
    $(className).each(function () {
        $(this).hide();
    });
}

/**
 * @param {JQuery<HTMLElement>} pos
 * @param {string} message
 */
function displayMessage(pos, message, type = "success") {
    const newDiv = document.createElement("div");
    const icon = document.createElement("i");
    const text = document.createTextNode(message);
    type == "success"
        ? icon.classList.add("fas", "fa-check-circle", "mr-2")
        : icon.classList.add("fas", "fa-exclamation-triangle", "mr-2");
    newDiv.className = type == "success" ? "alert alert-success my-3" : "alert alert-danger my-3";
    newDiv.appendChild(icon);
    newDiv.appendChild(text);
    pos.after(newDiv);

    const scrollOffset = 200;
    const elementPosition = pos[0].getBoundingClientRect().top + window.scrollY;
    window.scrollTo({
        top: elementPosition - scrollOffset,
        behavior: "smooth",
    });
}

/**
 * @param {string | undefined} name
 */
function setupBtns(name) {
    if (name == "" || name == undefined) {
        throw new Error("Missing name - setupBtns");
    }

    $("#" + name).on("click", function () {
        $(".alert").remove();
        $(".sectionsAFI button").removeClass("btn-primary").addClass("btn-outline-primary");
        $(this).removeClass("btn-outline-primary").addClass("btn-primary");
        const div = name.split("-").slice(1).join("-");
        if (name.split("-").length <= 2) hideSections();
        else hideSections(true);
        $("#" + div).show();
    });
}

/**
 * @param {string} filter
 * @param {string} tableName
 */
function filterTableByCarrer(filter, tableName) {
    const table = document.getElementById(tableName);
    const tableBody = $("#" + tableName).find("tbody");
    const rows = table?.getElementsByTagName("tr");

    if (filter === "") {
        if (rows) Array.from(rows).forEach((row) => (row.style.display = ""));
        return;
    }

    if (rows)
        Array.from(rows).forEach((row) => {
            const cell = row.getElementsByTagName("td")[2];
            if (cell) {
                const txtValue = cell.textContent || cell.innerText;
                row.style.display = txtValue.toUpperCase().includes(filter) ? "" : "none";
            }
        });

    if (tableBody.find("tr:visible").length == 0)
        tableBody.append(
            '<tr><td colspan="5" class="text-center">No se encontraron alumnos</td></tr>'
        );
}
