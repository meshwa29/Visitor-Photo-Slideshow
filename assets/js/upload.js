/* Upload page: drag & drop, previews and friendly client-side checks.
   The server repeats every check - this is only for convenience. */
(function () {
    'use strict';

    var dropzone = document.getElementById('dropzone');
    var input = document.getElementById('photos');
    var grid = document.getElementById('previewGrid');
    var errorBox = document.getElementById('clientErrors');
    var form = document.getElementById('uploadForm');
    var submitBtn = document.getElementById('submitBtn');
    var nameInput = document.getElementById('visitor_name');
    var codeInput = document.getElementById('visitor_code');
    var nameStar = document.getElementById('nameStar');
    if (!dropzone || !input || !form) { return; }

    var MAX_SIZE = parseInt(dropzone.getAttribute('data-max-size'), 10);
    var MAX_FILES = parseInt(dropzone.getAttribute('data-max-files'), 10);
    var ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
    var ALLOWED_EXT = /\.(jpe?g|png|webp)$/i;
    var files = [];            // the files currently selected
    var canEditList = (typeof DataTransfer !== 'undefined');

    function formatSize(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function problemWith(file) {
        if (!ALLOWED_EXT.test(file.name) || (file.type && ALLOWED.indexOf(file.type) === -1)) {
            return 'Only JPG, JPEG, PNG and WEBP images are allowed.';
        }
        if (file.size > MAX_SIZE) { return 'This file is larger than 5 MB.'; }
        if (file.size === 0) { return 'This file is empty.'; }
        return '';
    }

    function syncInput() {
        if (!canEditList) { return; }
        try {
            var dt = new DataTransfer();
            files.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
        } catch (e) { canEditList = false; }
    }

    function render() {
        grid.innerHTML = '';
        var messages = [];
        if (files.length > MAX_FILES) { messages.push('You can upload at most ' + MAX_FILES + ' photos at a time.'); }

        files.forEach(function (file, index) {
            var problem = problemWith(file);
            var card = document.createElement('div');
            card.className = 'preview-item' + (problem ? ' has-error' : '');

            if (!problem) {
                var img = document.createElement('img');
                img.alt = 'Preview of ' + file.name;
                img.src = URL.createObjectURL(file);
                img.onload = function () { URL.revokeObjectURL(img.src); };
                card.appendChild(img);
            } else {
                var bad = document.createElement('div');
                bad.className = 'preview-bad';
                bad.innerHTML = '<i class="bi bi-x-octagon"></i>';
                card.appendChild(bad);
                messages.push('"' + file.name + '": ' + problem);
            }

            var meta = document.createElement('div');
            meta.className = 'preview-meta';
            meta.textContent = file.name + ' (' + formatSize(file.size) + ')';
            card.appendChild(meta);

            if (canEditList) {
                var rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'preview-remove';
                rm.setAttribute('aria-label', 'Remove ' + file.name);
                rm.innerHTML = '&times;';
                rm.addEventListener('click', function (ev) {
                    ev.stopPropagation();
                    files.splice(index, 1);
                    syncInput();
                    render();
                });
                card.appendChild(rm);
            }
            grid.appendChild(card);
        });

        errorBox.innerHTML = '';
        messages.forEach(function (m) {
            var line = document.createElement('div');
            line.textContent = m;
            errorBox.appendChild(line);
        });
    }

    function addFiles(list) {
        for (var i = 0; i < list.length; i++) { files.push(list[i]); }
        syncInput();
        render();
    }

    dropzone.addEventListener('click', function () { input.click(); });
    dropzone.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); input.click(); }
    });
    input.addEventListener('click', function (ev) { ev.stopPropagation(); });
    input.addEventListener('change', function () {
        if (canEditList) {
            var picked = Array.prototype.slice.call(input.files);
            files = files.concat(picked);
            syncInput();
            render();
        } else {
            files = Array.prototype.slice.call(input.files);
            render();
        }
    });

    ['dragenter', 'dragover'].forEach(function (name) {
        dropzone.addEventListener(name, function (ev) { ev.preventDefault(); dropzone.classList.add('dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (name) {
        dropzone.addEventListener(name, function (ev) { ev.preventDefault(); dropzone.classList.remove('dragover'); });
    });
    dropzone.addEventListener('drop', function (ev) {
        if (ev.dataTransfer && ev.dataTransfer.files && ev.dataTransfer.files.length) {
            if (canEditList) { addFiles(ev.dataTransfer.files); }
            else { files = Array.prototype.slice.call(ev.dataTransfer.files); try { input.files = ev.dataTransfer.files; } catch (e) {} render(); }
        }
    });

    // Name is optional when a visitor code is typed.
    function updateNameHint() {
        if (nameStar) { nameStar.style.display = codeInput.value.trim() ? 'none' : 'inline'; }
    }
    codeInput.addEventListener('input', function () {
        codeInput.value = codeInput.value.toUpperCase();
        updateNameHint();
    });
    updateNameHint();

    form.addEventListener('submit', function (ev) {
        var msgs = [];
        if (!codeInput.value.trim() && nameInput.value.trim().length < 2) { msgs.push('Please enter your full name.'); }
        if (files.length === 0) { msgs.push('Please choose at least one photo.'); }
        if (files.length > MAX_FILES) { msgs.push('Too many photos selected.'); }
        if (files.some(function (f) { return problemWith(f) !== ''; })) { msgs.push('Remove the files marked in red before uploading.'); }
        if (!document.getElementById('consent').checked) { msgs.push('Please tick the consent box.'); }
        if (msgs.length) {
            ev.preventDefault();
            errorBox.innerHTML = '';
            msgs.forEach(function (m) { var d = document.createElement('div'); d.textContent = m; errorBox.appendChild(d); });
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
    });
})();
