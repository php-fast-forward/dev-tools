function openSvg(svg) {
    // convert to a valid XML source
    const as_text = new XMLSerializer().serializeToString(svg);
    // store in a Blob
    const blob = new Blob([as_text], { type: "image/svg+xml" });
    // create an URI pointing to that blob
    const url = URL.createObjectURL(blob);
    const win = open(url);
    // so the Garbage Collector can collect the blob
    win.onload = (evt) => URL.revokeObjectURL(url);
};


var svgs = document.querySelectorAll(".phpdocumentor-uml-diagram svg");
for( var i=0,il = svgs.length; i< il; i ++ ) {
    svgs[i].onclick = (evt) => openSvg(evt.target);
}
document.addEventListener('DOMContentLoaded', function () {
    if (window.hljs) window.hljs.highlightAll();
    document.querySelectorAll('table').forEach(function (table) {
        table.classList.add('table', 'table-striped-columns');
    });

    var root = document.documentElement;
    var header = document.querySelector('#header');
    function measureHeader() {
        if (header) root.style.setProperty('--ff-header-height', header.getBoundingClientRect().height + 'px');
    }
    measureHeader();
    if (window.ResizeObserver && header) new ResizeObserver(measureHeader).observe(header);

    var outline = document.querySelector('[data-guide-outline]');
    if (outline) {
        document.querySelectorAll('.ff-docs-article h2, .ff-docs-article h3').forEach(function (heading) {
            var section = heading.closest('section[id]');
            var id = heading.id || (section && section.id);
            if (!id) return;
            var link = document.createElement('a');
            var destination = new URL(window.location.href);
            destination.hash = id;
            link.href = destination.href;
            link.textContent = heading.textContent;
            outline.appendChild(link);
        });
    }
    var toggle = document.querySelector('[data-reading-theme-toggle]');
    var key = 'fast-forward-documentation-theme';
    function applyTheme(theme) {
        root.setAttribute('data-bs-theme', theme);
        if (toggle) {
            var label = theme === 'dark' ? 'Switch to light theme' : 'Switch to navy theme';
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
        }
    }
    applyTheme(root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light');
    if (toggle) {
        toggle.hidden = false;
        toggle.addEventListener('click', function () {
            var theme = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(theme);
            try { window.localStorage.setItem(key, theme); } catch (error) { /* Reading still works. */ }
        });
    }

    var navigation = document.querySelector('.ff-docs-navigation');
    var mobile = window.matchMedia('(max-width: 991.98px)');
    function adjustNavigation() { if (navigation) navigation.open = !mobile.matches; }
    adjustNavigation();
    if (mobile.addEventListener) mobile.addEventListener('change', adjustNavigation);

    function codeIcon(paths) {
        var namespace = 'http://www.w3.org/2000/svg';
        var icon = document.createElementNS(namespace, 'svg');
        icon.setAttribute('viewBox', '0 0 20 20');
        icon.setAttribute('width', '18');
        icon.setAttribute('height', '18');
        icon.setAttribute('fill', 'none');
        icon.setAttribute('stroke', 'currentColor');
        icon.setAttribute('stroke-width', '1.6');
        icon.setAttribute('stroke-linecap', 'round');
        icon.setAttribute('stroke-linejoin', 'round');
        icon.setAttribute('aria-hidden', 'true');
        icon.setAttribute('focusable', 'false');
        paths.forEach(function (value) {
            var path = document.createElementNS(namespace, 'path');
            path.setAttribute('d', value);
            icon.appendChild(path);
        });
        return icon;
    }
    var canCopy = window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText;
    /** Add one code window per block, including asynchronously loaded source listings. */
    function enhanceCodeWindow(pre) {
        var code = pre.querySelector('code');
        if (!code || pre.parentElement.classList.contains('ff-code-window')) return;
        var languageClass = Array.from(code.classList).find(function (name) { return /^(language|lang)-/.test(name); });
        var language = code.getAttribute('data-language') || pre.getAttribute('data-language') || (languageClass ? languageClass.replace(/^(language|lang)-/, '') : '');
        var languages = { bash: 'Bash', sh: 'Shell', shell: 'Shell', php: 'PHP', javascript: 'JavaScript', js: 'JavaScript', json: 'JSON', html: 'HTML', xml: 'XML', css: 'CSS', yaml: 'YAML', yml: 'YAML', sql: 'SQL', plaintext: 'Code', text: 'Code' };
        var title = code.getAttribute('data-title') || pre.getAttribute('data-title') || languages[language] || language || 'Code';
        var frame = document.createElement('div');
        frame.className = 'ff-code-window';
        var toolbar = document.createElement('div');
        toolbar.className = 'ff-code-toolbar';
        var dots = document.createElement('span');
        dots.className = 'ff-code-dots';
        dots.setAttribute('aria-hidden', 'true');
        for (var index = 0; index < 3; index++) dots.appendChild(document.createElement('span'));
        var heading = document.createElement('span');
        heading.className = 'ff-code-title';
        heading.appendChild(codeIcon(['m7 5-5 5 5 5', 'm13 5 5 5-5 5', 'm11 3-2 14']));
        var label = document.createElement('span');
        label.textContent = title;
        heading.appendChild(label);
        var actions = document.createElement('span');
        actions.className = 'ff-code-actions';
        toolbar.appendChild(dots);
        toolbar.appendChild(heading);
        toolbar.appendChild(actions);
        pre.parentNode.insertBefore(frame, pre);
        frame.appendChild(toolbar);
        frame.appendChild(pre);
        if (!canCopy) return;
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ff-copy-button';
        button.setAttribute('aria-label', 'Copy code example');
        button.setAttribute('title', 'Copy code');
        button.appendChild(codeIcon(['M7 7h10v11H7z', 'M4 13H2V2h10v2']));
        var status = document.createElement('span');
        status.className = 'ff-copy-status';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        status.setAttribute('aria-atomic', 'true');
        var statusTimer = null;
        button.addEventListener('click', async function () {
            window.clearTimeout(statusTimer);
            status.textContent = '';
            button.removeAttribute('data-copied');
            try {
                var currentCode = pre.querySelector('code');
                if (!currentCode) throw new Error('Code is unavailable');
                await navigator.clipboard.writeText(currentCode.textContent);
                status.textContent = 'Copied!';
                button.setAttribute('title', 'Copied!');
                button.setAttribute('data-copied', 'true');
                statusTimer = window.setTimeout(function () {
                    status.textContent = '';
                    button.setAttribute('title', 'Copy code');
                    button.removeAttribute('data-copied');
                }, 2500);
            } catch (error) {
                status.textContent = 'Copy unavailable. Select the code to copy it.';
                button.setAttribute('title', 'Copy unavailable');
            }
        });
        actions.appendChild(button);
        actions.appendChild(status);
    }
    document.querySelectorAll('pre').forEach(enhanceCodeWindow);
    document.addEventListener('ff-code-ready', function (event) {
        if (event.target.matches('pre')) enhanceCodeWindow(event.target);
    });
});
