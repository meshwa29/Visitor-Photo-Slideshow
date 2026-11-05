/* Visitor Photo Slideshow
   - 5 seconds per photo, cross-fade, repeats forever
   - polls slideshow_data.php so newly approved photos appear automatically
   Written in plain ES5 so older smart-TV browsers can run it. */
(function () {
    'use strict';

    var DISPLAY_MS = 5000;     // time each photo is shown
    var REFRESH_MS = 15000;    // how often we look for new approved photos
    var IDLE_MS = 4000;        // hide controls after this much inactivity

    var show = document.getElementById('show');
    var feedUrl = show.getAttribute('data-feed');
    var layers = [document.getElementById('slideA'), document.getElementById('slideB')];
    var emptyState = document.getElementById('emptyState');
    var caption = document.getElementById('caption');
    var capName = document.getElementById('capName');
    var capDate = document.getElementById('capDate');
    var capIndex = document.getElementById('capIndex');
    var capTotal = document.getElementById('capTotal');
    var progress = document.getElementById('progressBar');
    var btnPlay = document.getElementById('btnPlay');
    var btnPrev = document.getElementById('btnPrev');
    var btnNext = document.getElementById('btnNext');
    var btnFull = document.getElementById('btnFull');
    var notice = document.getElementById('notice');

    function setNotice(text) {
        if (!notice) { return; }
        notice.textContent = text || '';
        notice.hidden = !text;
    }

    var photos = [];
    var index = -1;            // index of the photo currently on screen
    var activeLayer = 0;
    var playing = true;
    var timer = null;
    var failures = 0;
    var idleTimer = null;
    var loadToken = 0;         // ignore results of older, superseded loads

    function currentSignature(list) {
        var ids = [];
        for (var i = 0; i < list.length; i++) { ids.push(list[i].id); }
        return ids.join(',');
    }

    function setProgress() {
        progress.style.transition = 'none';
        progress.style.width = '0%';
        if (!playing || photos.length < 2) { return; }
        void progress.offsetWidth;                     // restart the CSS transition
        progress.style.transition = 'width ' + DISPLAY_MS + 'ms linear';
        progress.style.width = '100%';
    }

    function schedule() {
        clearTimeout(timer);
        setProgress();
        if (playing && photos.length > 1) {
            timer = setTimeout(function () { go(index + 1); }, DISPLAY_MS);
        }
    }

    function updateCaption() {
        var p = photos[index];
        if (!p) { caption.hidden = true; return; }
        capName.textContent = p.name;
        capDate.textContent = p.date;
        capIndex.textContent = String(index + 1);
        capTotal.textContent = String(photos.length);
        caption.hidden = false;
    }

    /** Show photo number n (wraps around at both ends). */
    function go(n) {
        if (photos.length === 0) { return; }
        n = ((n % photos.length) + photos.length) % photos.length;
        var next = 1 - activeLayer;
        var img = layers[next].getElementsByTagName('img')[0];
        var photo = photos[n];
        var loader = new Image();
        var myToken = ++loadToken;

        loader.onload = function () {
            if (myToken !== loadToken) { return; }
            failures = 0;
            setNotice('');
            img.src = photo.url;
            img.alt = 'Photo by ' + photo.name;
            layers[next].className = 'slide active';
            layers[activeLayer].className = 'slide';
            activeLayer = next;
            index = n;
            updateCaption();
            schedule();
        };
        loader.onerror = function () {
            if (myToken !== loadToken) { return; }
            failures++;
            if (failures >= photos.length) {        // every image failed: stop trying
                setNotice('Photos are approved but the image files cannot be loaded. Check the uploads folder and its .htaccess.');
                clearTimeout(timer);
                return;
            }
            go(n + 1);                               // skip the broken photo
        };
        clearTimeout(timer);
        loader.src = photo.url;
    }

    function showEmpty(isEmpty) {
        emptyState.hidden = !isEmpty;
        if (isEmpty) {
            caption.hidden = true;
            layers[0].className = 'slide';
            layers[1].className = 'slide';
            progress.style.width = '0%';
            loadToken++;
            clearTimeout(timer);
        }
    }

    function applyPhotos(list) {
        var oldSig = currentSignature(photos);
        var newSig = currentSignature(list);
        if (oldSig === newSig) { return; }

        var currentId = (index >= 0 && photos[index]) ? photos[index].id : null;
        photos = list;

        if (photos.length === 0) { index = -1; showEmpty(true); return; }
        showEmpty(false);

        // Keep showing the same photo if it is still approved.
        var pos = -1;
        for (var i = 0; i < photos.length; i++) {
            if (photos[i].id === currentId) { pos = i; break; }
        }
        if (pos >= 0) {
            index = pos;
            updateCaption();
            schedule();
        } else {
            go(Math.max(0, Math.min(index, photos.length - 1)));
        }
    }

    function refresh() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', feedUrl + '?t=' + new Date().getTime(), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) { return; }
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data && data.photos) { setNotice(data.ok === false ? 'Server error while loading photos (is MySQL running / database imported?).' : ''); applyPhotos(data.photos); }
                } catch (e) { setNotice('Photo list is unreadable (open slideshow_data.php in the browser to see the error).'); }
            } else {
                setNotice('Cannot load photo list (HTTP ' + xhr.status + '). Open slideshow_data.php in the browser to see the error.');
            }
        };
        xhr.send();
    }

    // ---- controls
    function setPlaying(value) {
        playing = value;
        btnPlay.innerHTML = playing ? '<i class="bi bi-pause-fill"></i>' : '<i class="bi bi-play-fill"></i>';
        btnPlay.setAttribute('aria-label', playing ? 'Pause' : 'Play');
        schedule();
    }

    function toggleFullscreen() {
        var doc = document;
        var fsElement = doc.fullscreenElement || doc.webkitFullscreenElement;
        if (!fsElement) {
            var el = document.documentElement;
            var req = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
            if (req) { req.call(el); }
        } else {
            var exit = doc.exitFullscreen || doc.webkitExitFullscreen || doc.mozCancelFullScreen || doc.msExitFullscreen;
            if (exit) { exit.call(doc); }
        }
    }

    btnNext.addEventListener('click', function () { go(index + 1); });
    btnPrev.addEventListener('click', function () { go(index - 1); });
    btnPlay.addEventListener('click', function () { setPlaying(!playing); });
    btnFull.addEventListener('click', toggleFullscreen);

    document.addEventListener('keydown', function (ev) {
        var k = ev.key || '';
        if (k === 'ArrowRight' || ev.keyCode === 39) { go(index + 1); }
        else if (k === 'ArrowLeft' || ev.keyCode === 37) { go(index - 1); }
        else if (k === ' ' || k === 'Spacebar' || ev.keyCode === 32) { ev.preventDefault(); setPlaying(!playing); }
        else if (k === 'f' || k === 'F' || ev.keyCode === 70) { toggleFullscreen(); }
    });

    // Hide the buttons and mouse cursor when nobody is using them (nicer on a TV).
    function wake() {
        show.className = 'show';
        clearTimeout(idleTimer);
        idleTimer = setTimeout(function () { show.className = 'show idle'; }, IDLE_MS);
    }
    ['mousemove', 'keydown', 'touchstart', 'click'].forEach(function (name) {
        document.addEventListener(name, wake);
    });
    wake();

    refresh();
    setInterval(refresh, REFRESH_MS);
})();
