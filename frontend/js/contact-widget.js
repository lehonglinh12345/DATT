// Floating contact widget behavior without jQuery
// This script opens and closes the widget, closes on outside click, and supports Escape.

document.addEventListener('DOMContentLoaded', function () {
    var widget = document.getElementById('floatingContactWidget');
    var toggle = document.getElementById('floatingWidgetToggle');
    var actions = document.getElementById('floatingWidgetActions');
    var zaloAction = document.querySelector('.widget-action-zalo');

    if (!widget || !toggle || !actions) {
        return;
    }

    function setOpen(isOpen) {
        widget.classList.toggle('open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        actions.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    }

    function toggleOpen(event) {
        event.stopPropagation();
        setOpen(!widget.classList.contains('open'));
    }

    toggle.addEventListener('click', toggleOpen);

    document.addEventListener('click', function (event) {
        if (!widget.contains(event.target) && widget.classList.contains('open')) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && widget.classList.contains('open')) {
            setOpen(false);
            toggle.focus();
        }
    });

    if (zaloAction) {
        setInterval(function () {
            zaloAction.classList.add('zalo-rumble');
            window.setTimeout(function () {
                zaloAction.classList.remove('zalo-rumble');
            }, 900);
        }, 8000);
    }
});
