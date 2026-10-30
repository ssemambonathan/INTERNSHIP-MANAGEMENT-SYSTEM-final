import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import Chart from 'chart.js/auto';

window.Chart = Chart;

const chartDefaults = {
    color: '#475569',
    borderColor: '#e2e8f0',
    font: {
        family: 'Segoe UI, system-ui, sans-serif',
        size: 11,
    },
};

Chart.defaults.font.family = chartDefaults.font.family;
Chart.defaults.font.size = chartDefaults.font.size;
Chart.defaults.color = chartDefaults.color;
Chart.defaults.borderColor = chartDefaults.borderColor;

function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        if (canvas.dataset.chartRendered === '1') {
            return;
        }

        let config;

        try {
            config = JSON.parse(canvas.dataset.chart);
        } catch (error) {
            console.error('Invalid chart configuration', error);
            return;
        }

        config.options = {
            responsive: true,
            maintainAspectRatio: false,
            ...(config.options || {}),
        };

        if (config.type !== 'doughnut' && config.type !== 'pie') {
            config.options.scales = {
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 0, autoSkip: true },
                    ...(config.options.scales?.x || {}),
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#eef2f7' },
                    ticks: { precision: 0 },
                    ...(config.options.scales?.y || {}),
                },
            };
        }

        new Chart(canvas, config);
        canvas.dataset.chartRendered = '1';
    });
}

function setupSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (!sidebar) {
        return;
    }

    const toggle = document.getElementById('sidebarToggle');

    const close = () => {
        sidebar.classList.remove('show');
        backdrop?.classList.remove('show');
    };

    toggle?.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        backdrop?.classList.toggle('show');
    });

    backdrop?.addEventListener('click', close);
}

function setupConfirmations() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}

function setupGeoForms() {
    document.querySelectorAll('form.js-geo-form').forEach((form) => {
        const status = form.querySelector('.geo-status') || document.getElementById('geoStatus');
        const submit = form.querySelector('[type="submit"]');
        const latInput = form.querySelector('input[name="latitude"]');
        const lngInput = form.querySelector('input[name="longitude"]');
        const accuracyInput = form.querySelector('input[name="accuracy"]');

        const show = (message, tone) => {
            if (!status) {
                return;
            }

            status.className = 'geo-status show alert alert-' + tone + ' py-2 mb-3';
            status.innerHTML = message;
            status.style.display = 'block';
        };

        form.addEventListener('submit', (event) => {
            if (form.dataset.geoReady === '1') {
                return;
            }

            event.preventDefault();

            if (!('geolocation' in navigator)) {
                show('<i class="bi bi-exclamation-triangle me-1"></i>Your browser does not support location services. Use a modern browser or contact the administrator.', 'danger');
                return;
            }

            if (!window.isSecureContext) {
                show('<i class="bi bi-shield-exclamation me-1"></i>Location requires a secure connection. Open InternTrack over HTTPS or localhost.', 'danger');
                return;
            }

            show('<span class="spinner-border spinner-border-sm me-2"></span>Requesting your GPS location… Please allow location access when prompted.', 'info');
            submit?.setAttribute('disabled', 'disabled');
            submit?.classList.add('disabled');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude, accuracy } = position.coords;

                    if (latInput) latInput.value = latitude.toFixed(7);
                    if (lngInput) lngInput.value = longitude.toFixed(7);
                    if (accuracyInput) accuracyInput.value = Math.round(accuracy);

                    form.dataset.geoReady = '1';
                    show('<i class="bi bi-check-circle me-1"></i>Location captured with ±' + Math.round(accuracy) + ' m accuracy. Submitting…', 'success');
                    form.submit();
                },
                (error) => {
                    let message = 'We could not read your location. Please try again.';

                    if (error.code === error.PERMISSION_DENIED) {
                        message = 'Location permission was denied. Enable location access for this site in your browser settings, then try again.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        message = 'Your location is currently unavailable. Move to an open area, enable GPS, and try again.';
                    } else if (error.code === error.TIMEOUT) {
                        message = 'The location request timed out. Check your GPS signal and try again.';
                    }

                    show('<i class="bi bi-exclamation-triangle me-1"></i>' + message, 'danger');
                    submit?.removeAttribute('disabled');
                    submit?.classList.remove('disabled');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 0,
                }
            );
        });
    });
}

function setupAlerts() {
    document.querySelectorAll('.alert-auto-dismiss').forEach((alert) => {
        window.setTimeout(() => {
            if (window.bootstrap) {
                window.bootstrap.Alert.getOrCreateInstance(alert)?.close();
            }
        }, 6000);
    });
}

function setupTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new window.bootstrap.Tooltip(element);
    });
}

function setupFileInputs() {
    document.querySelectorAll('input[type="file"][data-max-size-kb]').forEach((input) => {
        input.addEventListener('change', () => {
            const hint = document.querySelector('[data-file-hint="' + input.id + '"]');
            const maxKb = Number(input.dataset.maxSizeKb);
            const file = input.files?.[0];

            if (!hint || !file) {
                return;
            }

            if (file.size / 1024 > maxKb) {
                hint.className = 'form-text text-danger';
                hint.textContent = 'This file is too large. Maximum allowed size is ' + Math.round(maxKb / 1024) + ' MB.';
                input.value = '';
                return;
            }

            hint.className = 'form-text text-success';
            hint.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB) selected.';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    renderCharts();
    setupSidebar();
    setupConfirmations();
    setupGeoForms();
    setupAlerts();
    setupTooltips();
    setupFileInputs();
});
