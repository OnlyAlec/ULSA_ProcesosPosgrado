window.setupBtns = setupBtns;
window.hideSections = hideSections;
window.displayMessage = displayMessage;
window.filterTableByCarrer = filterTableByCarrer;

function setupBtns(name) {
    if (name == "" || name == undefined) {
        throw new Error("Missing name - setupBtns");
    }

    $('#' + name).on("click", function () {
        $('.alert').remove();
        $('.sectionsAFI button').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        const div = name.split("-").slice(1).join("-");
        if (name.split("-").length <= 2)
            hideSections();
        else
            hideSections(true);
        $('#' + div).show();
    });
}

function hideSections(subsection = false) {
    const className = subsection ? ".subSectionAFI" : ".sectionAFI";
    $(className).each(function () {
        $(this).hide();
    });
}

function displayMessage(pos, message, type = 'success') {
    const newDiv = document.createElement('div');
    newDiv.className = type == 'success' ? 'alert alert-success my-3' : 'alert alert-danger my-3';
    newDiv.innerHTML = message;
    pos.before(newDiv);
}

function setupBtns(name) {
    if (name == "" || name == undefined) {
        throw new Error("Missing name - setupBtns");
    }

    $('#' + name).on("click", function () {
        $('.alert').remove();
        $('.sectionsAFI button').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        const div = name.split("-").slice(1).join("-");
        if (name.split("-").length <= 2)
            hideSections();
        else
            hideSections(true);
        $('#' + div).show();
    });
}

function filterTableByCarrer(filter, tableName) {
    const table = document.getElementById(tableName);
    const rows = table.getElementsByTagName("tr");

    if (filter === "ALL") {
        Array.from(rows).forEach(row => row.style.display = "");
        return;
    }

    Array.from(rows).forEach(row => {
        const cell = row.getElementsByTagName("td")[2];
        if (cell) {
            const txtValue = cell.textContent || cell.innerText;
            row.style.display = txtValue.toUpperCase().includes(filter) ? "" : "none";
        }
    });
}