/* Admin helpers: confirm dialogs for dangerous forms and the big photo preview modal. */
(function () {
    'use strict';

    // Any form with data-confirm asks first.
    var forms = document.querySelectorAll('form[data-confirm]');
    Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    });

    // Click a thumbnail to see it large.
    var modalEl = document.getElementById('previewModal');
    if (!modalEl || typeof bootstrap === 'undefined') { return; }
    var modal = new bootstrap.Modal(modalEl);
    var image = document.getElementById('previewImage');
    var caption = document.getElementById('previewCaption');

    Array.prototype.forEach.call(document.querySelectorAll('[data-preview]'), function (btn) {
        btn.addEventListener('click', function () {
            image.src = btn.getAttribute('data-preview');
            caption.textContent = btn.getAttribute('data-caption') || '';
            modal.show();
        });
    });
    modalEl.addEventListener('hidden.bs.modal', function () { image.src = ''; });
})();
