/* global airaloApi */
/**
 * Mejoras progresivas del panel de Airalo.
 *
 * El plugin funciona sin JavaScript: esto solo añade ayudas (copiar al
 * portapapeles, filtrar la tabla de estados y avisar antes de acciones
 * destructivas). El objeto `airaloApi` lo inyecta
 * Airalo_Admin_Pages::enqueue_admin_assets().
 */
(function () {
    'use strict';

    // Configuración inyectada por PHP (wp_localize_script). Se copia a una
    // variable local para no escribir en el objeto global.
    var config = (typeof airaloApi !== 'undefined' && airaloApi) ? airaloApi : {};

    /**
     * Copia un texto al portapapeles.
     *
     * @param {string} text Texto a copiar.
     */
    function copyToClipboard(text) {
        if (!text) {
            return;
        }

        var fallback = function () {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
            } catch (error) {
                // Sin soporte de portapapeles: no hacemos nada.
            }

            document.body.removeChild(textarea);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).catch(fallback);
        } else {
            fallback();
        }
    }

    /**
     * Muestra un aviso breve dentro de un elemento.
     *
     * @param {Element}   element  Contenedor del aviso.
     * @param {string}    message  Texto del aviso.
     * @param {boolean}   isError  true si es un error.
     */
    function flash(element, message, isError) {
        if (!element) {
            return;
        }

        var notice = document.createElement('div');
        notice.className = 'notice ' + (isError ? 'notice-error' : 'notice-success');
        notice.textContent = message;
        element.insertBefore(notice, element.firstChild);

        window.setTimeout(function () {
            notice.remove();
        }, 4000);
    }

    document.addEventListener('click', function (event) {
        var target = event.target;

        if (!target || !target.closest) {
            return;
        }

        // Copiar el ICCID o el contenido de un log.
        var copyButton = target.closest('[data-airalo-copy]');

        if (copyButton) {
            var source = document.querySelector(copyButton.getAttribute('data-airalo-copy'));
            var text = source ? (source.textContent || source.value || '') : '';
            var original = copyButton.textContent;

            copyToClipboard(text.trim());
            copyButton.textContent = 'Copiado';

            window.setTimeout(function () {
                copyButton.textContent = original;
            }, 1200);

            return;
        }

        // Confirmación antes de una acción destructiva.
        var dangerButton = target.closest('[data-airalo-confirm]');

        if (dangerButton) {
            if (!window.confirm(dangerButton.getAttribute('data-airalo-confirm'))) {
                event.preventDefault();
            }
        }
    });

    // Filtro rápido de la tabla de estados de orden.
    var filter = document.querySelector('[data-airalo-filter]');

    if (filter) {
        var table = document.querySelector(filter.getAttribute('data-airalo-filter'));

        if (table) {
            filter.addEventListener('input', function () {
                var needle = filter.value.toLowerCase().trim();
                var rows = table.querySelectorAll('tbody tr');

                Array.prototype.forEach.call(rows, function (row) {
                    var text = (row.textContent || '').toLowerCase();
                    row.style.display = (needle === '' || text.indexOf(needle) !== -1) ? '' : 'none';
                });
            });
        }
    }

    // Aviso si el navegador no puede preguntar nada al servidor.
    var ping = document.querySelector('[data-airalo-ping]');

    if (ping && !config.ajaxurl) {
        flash(ping, 'AJAX no está disponible en este servidor.', true);
    }
})();
