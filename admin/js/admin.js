jQuery(document).ready(function($) {
    $('.aipdb-test-api').on('click', function() {
        const button = $(this);
        const apiKey = $('#aipdb_api_key').val();

        // Crear o reutilizar contenedor de mensaje debajo
        let msgContainer = $('#aipdb-api-status-message');
        if (!msgContainer.length) {
            msgContainer = $('<div id="aipdb-api-status-message"></div>');
            button.closest('td').append(msgContainer); // Debe estar dentro de la celda del input
        }

        if (!apiKey) {
            showAIPDBStatus('✖️ API Key requerida.', 'error', msgContainer);
            return;
        }

        button.prop('disabled', true).text('Conectando...');

        $.ajax({
            url: aipdb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aipdb_test_api',
                api_key: apiKey,
                nonce: aipdb_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAIPDBStatus('✔️ ¡Conexión exitosa con AbuseIPDB!', 'success', msgContainer);
                } else {
                    showAIPDBStatus('✖️ ' + (response.data.message || 'Error en la conexión.'), 'error', msgContainer);
                }
            },
            error: function() {
                showAIPDBStatus('✖️ No se pudo conectar al servidor.', 'error', msgContainer);
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });

    /**
     * Muestra un mensaje debajo del campo de la API Key
     * @param {string} msg 
     * @param {'success'|'error'} type 
     * @param {jQuery} container 
     */
    function showAIPDBStatus(msg, type, container) {
        const color = type === 'success' ? '#d4edda' : '#f8d7da';
        const border = type === 'success' ? '#28a745' : '#dc3545';
        const text = type === 'success' ? '#155724' : '#721c24';

        container
            .html('<p><strong>' + msg + '</strong></p>')
            .css({
                background: color,
                border: '1px solid ' + border,
                color: text,
                padding: '10px',
                borderRadius: '4px',
                marginTop: '10px',
                width: 'fit-content'
            });
    }
});
