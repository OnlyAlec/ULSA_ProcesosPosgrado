window.setupBtns = setupBtns;
window.hideSections = hideSections;
window.displayMessage = displayMessage;

$(function () {
    // *Change label text on file input
    $('.custom-file-input').on('change', function (e) {
        const fileName = $(e.target).prop('files')[0]?.name
            ? $(e.target).prop('files')[0].name.length > 70
                ? $(e.target).prop('files')[0].name.substring(0, 68) + '...'
                : $(e.target).prop('files')[0].name
            : 'Seleccionar archivo...';
        $(e.target).next().text(fileName);
    });
});

function setupBtns(name) {
    if (name == '' || name == undefined) {
        throw new Error('Missing name - setupBtns');
    }

    $('#' + name).on('click', function () {
        $('.alert').remove();
        $('.forms-result').hide();
        // $('.subSectionAFI').hide();
        $('.custom-file-input').val('').next().text('Seleccionar archivo...');

        $('.sectionsAFI button').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        const div = name.split('-').slice(1).join('-');
        if (name.split('-').length <= 2) hideSections();
        else hideSections(true);
        $('#' + div).show();
    });
}

function hideSections(subsection = false) {
    const className = subsection ? '.subSectionAFI' : '.sectionAFI';
    $(className).each(function () {
        $(this).hide();
    });
}

function displayMessage(pos, message, type = 'success') {
    const newDiv = document.createElement('div');
    const icon = document.createElement('i');
    const text = document.createTextNode(message);
    type == 'success'
        ? icon.classList.add('fas', 'fa-check-circle', 'mr-2')
        : icon.classList.add('fas', 'fa-exclamation-triangle', 'mr-2');
    newDiv.className = type == 'success' ? 'alert alert-success my-3' : 'alert alert-danger my-3';
    newDiv.appendChild(icon);
    newDiv.appendChild(text);
    pos.after(newDiv);

    const scrollOffset = 200;
    const elementPosition = pos[0].getBoundingClientRect().top + window.scrollY;
    window.scrollTo({
        top: elementPosition - scrollOffset,
        behavior: 'smooth',
    });
}
