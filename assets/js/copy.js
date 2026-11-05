/* Copies the visitor code to the clipboard. */
(function () {
    'use strict';
    var btn = document.getElementById('copyCode');
    if (!btn) { return; }
    btn.addEventListener('click', function () {
        var el = document.getElementById(btn.getAttribute('data-target'));
        var text = el ? el.textContent.trim() : '';
        function done(ok) {
            btn.innerHTML = ok ? '<i class="bi bi-check2 me-1"></i>Copied!' : 'Press Ctrl+C to copy';
            setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy code'; }, 2000);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
        } else {
            var range = document.createRange();
            range.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            try { done(document.execCommand('copy')); } catch (e) { done(false); }
        }
    });
})();
