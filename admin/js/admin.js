jQuery(document).ready(function ($) {

    /* ------------------------------------------------------------------
     * Test API connection
     * ------------------------------------------------------------------ */
    $('.aipdb-test-api').on('click', function () {
        const button = $(this);
        const apiKey = $('#aipdb_api_key').val();

        let msgContainer = $('#aipdb-api-status-message');
        if (!msgContainer.length) {
            msgContainer = $('<div id="aipdb-api-status-message"></div>');
            button.closest('td').append(msgContainer);
        }

        if (!apiKey) {
            showAIPDBStatus('✖ ' + (aipdb_admin.strings.api_key_required || 'API Key required.'), 'error', msgContainer);
            return;
        }

        button.prop('disabled', true).text(aipdb_admin.strings.connecting || 'Connecting...');

        $.ajax({
            url: aipdb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aipdb_test_api',
                api_key: apiKey,
                nonce: aipdb_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    showAIPDBStatus('✔ ' + (response.data.message || 'Connection successful.'), 'success', msgContainer);
                } else {
                    showAIPDBStatus('✖ ' + (response.data.message || 'Connection error.'), 'error', msgContainer);
                }
            },
            error: function () {
                showAIPDBStatus('✖ ' + (aipdb_admin.strings.server_error || 'Could not connect to server.'), 'error', msgContainer);
            },
            complete: function () {
                button.prop('disabled', false).text(aipdb_admin.strings.test_connection || 'Test Connection');
            }
        });
    });

<<<<<<< HEAD
    /* ------------------------------------------------------------------
     * Threshold slider — live readout
     * ------------------------------------------------------------------ */
    $(document).on('input change', '#aipdb_abuse_threshold', function () {
        const val = $(this).val();
        $(this).siblings('.aipdb-threshold-output, .aipdb-threshold-value').text(val + '%');
    });

    /* ------------------------------------------------------------------
     * Quick Setup form (dashboard)
     * ------------------------------------------------------------------ */
    $('#aipdb-quick-form').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $submit = $form.find('button[type="submit"]');
        const originalLabel = $submit.text();

        $submit.prop('disabled', true).text(aipdb_admin.strings.saving || 'Saving...');

        const payload = {
            action: 'aipdb_save_quick_settings',
            nonce: aipdb_admin.nonce,
            aipdb_api_key: $form.find('[name="aipdb_api_key"]').val(),
            aipdb_enabled: $form.find('[name="aipdb_enabled"]').is(':checked') ? 1 : 0,
            aipdb_abuse_threshold: $form.find('[name="aipdb_abuse_threshold"]').val(),
            aipdb_auto_report: $form.find('[name="aipdb_auto_report"]').is(':checked') ? 1 : 0
        };
=======
    // Test IP Tool
    $('.aipdb-check-ip').on('click', function() {
        const button = $(this);
        const ip = $('#aipdb_test_ip').val();
        const resultContainer = $('#aipdb-test-ip-result');

        if (!ip) {
            alert('Please enter an IP address');
            return;
        }

        button.prop('disabled', true).text('Checking...');
        resultContainer.html('<p>Checking IP...</p>');
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c

        $.ajax({
            url: aipdb_admin.ajax_url,
            type: 'POST',
<<<<<<< HEAD
            data: payload,
            success: function (response) {
                if (response.success) {
                    showInlineNotice($form, 'success', response.data.message || (aipdb_admin.strings.saved || 'Saved.'));
                } else {
                    showInlineNotice($form, 'error', response.data.message || (aipdb_admin.strings.error || 'Could not save.'));
                }
            },
            error: function () {
                showInlineNotice($form, 'error', aipdb_admin.strings.server_error || 'Server error.');
            },
            complete: function () {
                $submit.prop('disabled', false).text(originalLabel);
=======
            data: {
                action: 'aipdb_check_ip',
                ip: ip,
                nonce: aipdb_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = '<div class="notice notice-success inline"><p>';
                    html += '<strong>Score: ' + data.score + '%</strong><br>';
                    if (data.country) {
                        html += 'Country: ' + data.country + '<br>';
                    }
                    html += 'Cached: ' + (data.cached ? 'Yes' : 'No');
                    html += '</p></div>';
                    resultContainer.html(html);
                } else {
                    resultContainer.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                resultContainer.html('<div class="notice notice-error inline"><p>Connection error</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).text('Check IP');
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
            }
        });
    });

<<<<<<< HEAD
    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */
=======
    /**
     * Muestra un mensaje debajo del campo de la API Key
     * @param {string} msg 
     * @param {'success'|'error'} type 
     * @param {jQuery} container 
     */
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
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

    /* ------------------------------------------------------------------
     * Security Rules page — toggle event rows and status badge
     * ------------------------------------------------------------------ */
    $('.aipdb-event-toggle').on('change', function () {
        const $row = $(this).closest('tr');
        const $configRow = $row.next('.aipdb-config-row');
        const isEnabled = $(this).is(':checked');

        if ($configRow.length) {
            isEnabled ? $configRow.show() : $configRow.hide();
        }

        const $badge = $row.find('.aipdb-status-badge');
        if (isEnabled) {
            $badge.removeClass('aipdb-status-disabled').addClass('aipdb-status-enabled').text('ENABLED');
        } else {
            $badge.removeClass('aipdb-status-enabled').addClass('aipdb-status-disabled').text('DISABLED');
        }
    });

    $('.aipdb-event-toggle').each(function () {
        const $configRow = $(this).closest('tr').next('.aipdb-config-row');
        if ($configRow.length && !$(this).is(':checked')) {
            $configRow.hide();
        }
    });

    /* ------------------------------------------------------------------
     * Detections page — delete row + view details modal
     * ------------------------------------------------------------------ */
    $(document).on('click', '.aipdb-delete-detection', function (e) {
        e.preventDefault();

        const $button = $(this);
        const detectionId = $button.data('id');
        const detectionIP = $button.data('ip');
        const $row = $button.closest('tr');
        const confirmMsg = (aipdb_admin.strings.confirm_delete_detection || 'Delete this detection?') + '\n\nIP: ' + detectionIP;

        if (!window.confirm(confirmMsg)) {
            return;
        }

        const originalHtml = $button.html();
        $button.prop('disabled', true)
            .html('<span class="spinner is-active" style="float:none;margin:0;"></span> ' + (aipdb_admin.strings.deleting || 'Deleting...'));

        $.ajax({
            url: aipdb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aipdb_delete_detection',
                id: detectionId,
                nonce: aipdb_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    $row.fadeOut(400, function () {
                        $(this).remove();
                        const $tbody = $('#detections-table-body');
                        if (!$tbody.find('tr').length) {
                            $tbody.html('<tr class="no-items"><td colspan="9" style="text-align:center;padding:20px;">' +
                                (aipdb_admin.strings.no_detections || 'No detections found.') + '</td></tr>');
                        }
                    });
                    showTopNotice('success', response.data.message);
                } else {
                    $button.prop('disabled', false).html(originalHtml);
                    showTopNotice('error', response.data.message);
                }
            },
            error: function () {
                $button.prop('disabled', false).html(originalHtml);
                showTopNotice('error', aipdb_admin.strings.server_error || 'Connection error.');
            }
        });
    });

    $(document).on('click', '.aipdb-view-details', function (e) {
        e.preventDefault();
        const $row = $(this).closest('tr');
        const data = {
            id: $(this).data('id'),
            ip: $row.find('td:nth-child(3)').text().trim(),
            date: $row.find('td:nth-child(2)').text().trim(),
            eventType: $row.find('td:nth-child(4)').text().trim(),
            threatLevel: $row.find('td:nth-child(5)').text().trim(),
            score: $row.find('td:nth-child(6)').text().trim(),
            country: $row.find('td:nth-child(7)').text().trim(),
            action: $row.find('td:nth-child(8)').text().trim()
        };
        showDetailsModal(data);
    });

    $(document).on('click', '.aipdb-details-modal-overlay, .aipdb-modal-close', function (e) {
        if (e.target === this || $(e.target).hasClass('aipdb-modal-close')) {
            $('.aipdb-details-modal-overlay').remove();
        }
    });

    function showDetailsModal(d) {
        const s = aipdb_admin.strings;
        const html =
            '<div class="aipdb-details-modal-overlay">' +
                '<div class="aipdb-details-modal">' +
                    '<div class="aipdb-modal-header">' +
                        '<h3>' + (s.details_title || 'Detection details') + ' #' + d.id + '</h3>' +
                        '<span class="aipdb-modal-close" role="button" aria-label="Close">&times;</span>' +
                    '</div>' +
                    '<div class="aipdb-modal-body">' +
                        '<table class="aipdb-details-table">' +
                            row(s.label_ip || 'IP Address:', d.ip) +
                            row(s.label_date || 'Date:', d.date) +
                            row(s.label_event_type || 'Event Type:', d.eventType) +
                            row(s.label_threat_level || 'Threat Level:', d.threatLevel) +
                            row(s.label_score || 'AbuseIPDB Score:', d.score) +
                            row(s.label_country || 'Country:', d.country) +
                            row(s.label_action || 'Action Taken:', d.action) +
                        '</table>' +
                    '</div>' +
                '</div>' +
            '</div>';
        $('body').append(html);
    }

    function row(label, value) {
        return '<tr><td><strong>' + label + '</strong></td><td>' + value + '</td></tr>';
    }

    function showTopNotice(type, message) {
        const cls = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + cls + ' is-dismissible" style="margin:15px 0;"><p>' + message + '</p></div>');
        $('.wrap h1').first().after($notice);
        setTimeout(function () {
            $notice.fadeOut(400, function () { $(this).remove(); });
        }, 5000);
    }

    function showInlineNotice($form, type, message) {
        $form.find('.aipdb-inline-notice').remove();
        const cls = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $(
            '<div class="notice ' + cls + ' aipdb-inline-notice" style="margin:10px 0;">' +
            '<p>' + message + '</p>' +
            '</div>'
        );
        $form.prepend($notice);
        setTimeout(function () {
            $notice.fadeOut(400, function () { $(this).remove(); });
        }, 4000);
    }
});
