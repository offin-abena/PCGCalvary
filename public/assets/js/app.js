// Church Management System - App JS

document.addEventListener('DOMContentLoaded', function() {
    initSidebarToggle();
    initNavSubmenus();
    initSessionTimeout();

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // QR Check-in functionality
    var qrInput = document.getElementById('qrCodeInput');
    if (qrInput) {
        qrInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                processQrCheckin(this.value);
            }
        });
    }
});

function initSidebarToggle() {
    var body = document.body;
    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('sidebarToggle');
    var icon = document.getElementById('sidebarToggleIcon');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !toggle) return;

    var saved = localStorage.getItem('sidebar_state');
    if (saved === 'collapsed') {
        body.classList.remove('sidebar-expanded');
        body.classList.add('sidebar-collapsed');
    }

    function isMobile() {
        return window.matchMedia('(max-width: 768px)').matches;
    }

    function updateIcon() {
        if (!icon) return;
        if (isMobile()) {
            icon.className = sidebar.classList.contains('show')
                ? 'bi bi-x-lg fs-5'
                : 'bi bi-list fs-5';
        } else {
            icon.className = body.classList.contains('sidebar-collapsed')
                ? 'bi bi-list fs-5'
                : 'bi bi-layout-sidebar-inset fs-5';
        }
    }

    function setBackdrop(visible) {
        if (!backdrop) return;
        backdrop.style.display = visible ? 'block' : 'none';
        backdrop.style.opacity = visible ? '1' : '0';
    }

    toggle.addEventListener('click', function() {
        if (isMobile()) {
            sidebar.classList.toggle('show');
            setBackdrop(sidebar.classList.contains('show'));
            updateIcon();
            return;
        }

        var collapsed = !body.classList.contains('sidebar-collapsed');
        body.classList.toggle('sidebar-collapsed', collapsed);
        body.classList.toggle('sidebar-expanded', !collapsed);
        localStorage.setItem('sidebar_state', collapsed ? 'collapsed' : 'expanded');
        updateIcon();
    });

    if (backdrop) {
        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('show');
            setBackdrop(false);
            updateIcon();
        });
    }

    window.addEventListener('resize', function() {
        if (!isMobile()) {
            sidebar.classList.remove('show');
            setBackdrop(false);
        }
        updateIcon();
    });

    updateIcon();
}

function initNavSubmenus() {
    document.querySelectorAll('[data-nav-toggle]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var submenu = this.nextElementSibling;
            if (!submenu || !submenu.classList.contains('nav-submenu')) {
                return;
            }

            var isOpen = submenu.classList.toggle('open');
            this.classList.toggle('expanded', isOpen);
            this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            if (isOpen) {
                this.classList.add('active');
            } else if (!submenu.querySelector('.nav-submenu-link.active')) {
                this.classList.remove('active');
            }
        });
    });
}

function initSessionTimeout() {
    if (!document.body.dataset.authenticated) {
        return;
    }

    var timeoutMs = window.SESSION_TIMEOUT_MS || 300000;
    var logoutUrl = (window.APP_URL || '') + '/logout?reason=inactivity';
    var timer = null;

    function logoutForInactivity() {
        window.location.href = logoutUrl;
    }

    function resetTimer() {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(logoutForInactivity, timeoutMs);
    }

    ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function(eventName) {
        document.addEventListener(eventName, resetTimer, { passive: true });
    });

    resetTimer();
}

function processQrCheckin(qrCode) {
    var sessionId = document.getElementById('sessionId')?.value;
    if (!sessionId || !qrCode) return;

    fetch(window.APP_URL + '/attendance/qr-checkin', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
        },
        body: 'qr_code=' + encodeURIComponent(qrCode) + '&session_id=' + sessionId + '&_csrf=' + encodeURIComponent(window.CSRF_TOKEN || '')
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var result = document.getElementById('qrResult');
        if (result) {
            result.className = 'alert alert-' + (data.success ? 'success' : 'danger');
            result.textContent = data.message;
            result.classList.remove('d-none');
        }
        document.getElementById('qrCodeInput').value = '';
        document.getElementById('qrCodeInput').focus();
    });
}
