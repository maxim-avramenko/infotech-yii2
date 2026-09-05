function initAuthorSubscribeModal() {
    var modal = document.getElementById('author-subscribe-modal');
    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) {
            return;
        }
        var idInput = modal.querySelector('#subscription-author-id');
        var title = modal.querySelector('.js-author-name');
        if (idInput) {
            idInput.value = button.getAttribute('data-author-id') || '';
        }
        if (title) {
            title.textContent = button.getAttribute('data-author-name') || '';
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuthorSubscribeModal);
} else {
    initAuthorSubscribeModal();
}
