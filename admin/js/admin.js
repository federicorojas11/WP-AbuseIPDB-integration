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

    /* ------------------------------------------------------------------
     * Threshold slider — live readout
     * ------------------------------------------------------------------ */
    $(document).on('input change', '#aipdb_abuse_threshold', function () {
        const val = $(this).val();
        $(this).closest('.aipdb-slider-row, td, .aipdb-field')
            .find('.aipdb-threshold-output, .aipdb-threshold-value, .aipdb-slider-value')
            .text(val + '%');
    });

    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */
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
     * Dashboard — manual "Check an IP" tool
     * ------------------------------------------------------------------ */
    $('#aipdb-ip-check-form').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $result = $('#aipdb-ip-check-result');
        const $submit = $form.find('button[type="submit"]');
        const ip = $('#aipdb-check-ip-input').val().trim();

        if (!ip) {
            $result.html('<p class="aipdb-msg-error">' + (aipdb_admin.strings.ip_required || 'IP required.') + '</p>');
            return;
        }

        const originalLabel = $submit.text();
        $submit.prop('disabled', true).text(aipdb_admin.strings.connecting || 'Checking...');
        $result.html('<p><span class="spinner is-active" style="float:none;margin:0;"></span></p>');

        $.ajax({
            url: aipdb_admin.ajax_url,
            type: 'POST',
            data: { action: 'aipdb_check_ip', ip: ip, nonce: aipdb_admin.nonce },
            success: function (response) {
                if (response.success) {
                    $result.html(renderIpCheckResult(response.data));
                } else {
                    $result.html('<p class="aipdb-msg-error">' + ((response.data && response.data.message) || 'Error.') + '</p>');
                }
            },
            error: function () {
                $result.html('<p class="aipdb-msg-error">' + (aipdb_admin.strings.server_error || 'Server error.') + '</p>');
            },
            complete: function () {
                $submit.prop('disabled', false).text(originalLabel);
            }
        });
    });

    function renderIpCheckResult(d) {
        const s = aipdb_admin.strings;
        const score = (d.score === null || d.score === undefined)
            ? 'N/A'
            : d.score + '% (' + (d.threat_level || '') + ')';
        const verdict = d.would_block
            ? '<span class="aipdb-msg-error" style="display:inline-block;">' + (s.verdict_block || 'Would be blocked') + '</span>'
            : '<span class="aipdb-msg-success" style="display:inline-block;">' + (s.verdict_allow || 'Would be allowed') + '</span>';
        const country = d.country ? escapeHtml(d.country + (d.country_name ? ' — ' + d.country_name : '')) : '-';

        let flags = [];
        if (d.is_whitelisted) { flags.push(s.flag_whitelisted || 'Whitelisted'); }
        if (d.is_blocked) { flags.push(s.flag_blocked || 'Manually blocked'); }

        return '<table class="aipdb-details-table" style="margin-top:12px;">' +
            row(s.label_ip || 'IP Address:', escapeHtml(d.ip)) +
            row(s.label_score || 'AbuseIPDB Score:', score) +
            row(s.label_country || 'Country:', country) +
            row(s.label_reports || 'Total Reports:', d.total_reports === null ? '-' : d.total_reports) +
            row((s.verdict_label || 'Verdict:'), verdict) +
            (flags.length ? row(s.flags_label || 'Flags:', flags.join(', ')) : '') +
            '</table>';
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

    /* ------------------------------------------------------------------
     * Blocked IPs page — manual block form + unblock
     * ------------------------------------------------------------------ */
    $('#aipdb-block-ip-form').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $msg = $('#aipdb-block-ip-message');
        const ip = $form.find('[name="ip"]').val().trim();
        const reason = $form.find('[name="reason"]').val().trim();
        const $submit = $form.find('button[type="submit"]');

        if (!ip) {
            $msg.html('<p class="aipdb-msg-error">' + (aipdb_admin.strings.ip_required || 'IP required.') + '</p>');
            return;
        }

        const originalLabel = $submit.text();
        $submit.prop('disabled', true).text(aipdb_admin.strings.blocking || 'Blocking...');
        $msg.empty();

        $.ajax({
            url: aipdb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aipdb_block_ip',
                ip: ip,
                reason: reason,
                nonce: aipdb_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    $msg.html('<p class="aipdb-msg-success">' + response.data.message + '</p>');
                    appendBlocklistRow(response.data);
                    $form.find('[name="ip"], [name="reason"]').val('');
                } else {
                    $msg.html('<p class="aipdb-msg-error">' + (response.data.message || 'Error.') + '</p>');
                }
            },
            error: function () {
                $msg.html('<p class="aipdb-msg-error">' + (aipdb_admin.strings.server_error || 'Server error.') + '</p>');
            },
            complete: function () {
                $submit.prop('disabled', false).text(originalLabel);
            }
        });
    });

    $(document).on('click', '.aipdb-unblock-ip', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const ip = $btn.data('ip');
        const $row = $btn.closest('tr');

        if (!window.confirm((aipdb_admin.strings.confirm_unblock || 'Unblock this IP?') + '\n\n' + ip)) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            url: aipdb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aipdb_unblock_ip',
                ip: ip,
                nonce: aipdb_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    $row.fadeOut(300, function () {
                        $(this).remove();
                        const $tbody = $('#aipdb-blocklist-body');
                        if (!$tbody.find('tr[data-ip]').length) {
                            $tbody.html('<tr class="no-items"><td colspan="5" style="text-align:center;padding:20px;">' +
                                (aipdb_admin.strings.no_blocked_ips || 'No IPs are currently blocked.') + '</td></tr>');
                        }
                    });
                    showTopNotice('success', response.data.message);
                } else {
                    $btn.prop('disabled', false);
                    showTopNotice('error', response.data.message);
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                showTopNotice('error', aipdb_admin.strings.server_error || 'Server error.');
            }
        });
    });

    /* ------------------------------------------------------------------
     * Detections page — "Block IP" quick action
     * ------------------------------------------------------------------ */
    $(document).on('click', '.aipdb-block-detection-ip', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const ip = $btn.data('ip');

        const reason = window.prompt(
            (aipdb_admin.strings.prompt_block_reason || 'Reason (optional):'),
            (aipdb_admin.strings.default_block_reason || 'Blocked from detections')
        );
        if (reason === null) {
            return; // user cancelled
        }

        const originalHtml = $btn.html();
        $btn.prop('disabled', true)
            .html('<span class="spinner is-active" style="float:none;margin:0;"></span>');

        $.ajax({
            url: aipdb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aipdb_block_ip',
                ip: ip,
                reason: reason,
                nonce: aipdb_admin.nonce
            },
            success: function (response) {
                $btn.prop('disabled', false).html(originalHtml);
                if (response.success) {
                    showTopNotice('success', response.data.message);
                } else {
                    showTopNotice('error', response.data.message);
                }
            },
            error: function () {
                $btn.prop('disabled', false).html(originalHtml);
                showTopNotice('error', aipdb_admin.strings.server_error || 'Server error.');
            }
        });
    });

    /* ------------------------------------------------------------------
     * Detections page — filters, pagination, sorting, bulk actions
     * ------------------------------------------------------------------ */
    const $detectionsTable = $('#detections-table-body');
    if ($detectionsTable.length) {

        const state = {
            page: 1,
            orderby: 'created_at',
            order: 'DESC'
        };

        function collectFilters() {
            return {
                search: $('#detection-search').val() || '',
                event_type: $('#event-type-filter').val() || '',
                threat_level: $('#threat-level-filter').val() || '',
                country: $('#country-filter').val() || '',
                min_score: $('#min-score-filter').val() || '',
                date_from: $('#date-from').val() || '',
                date_to: $('#date-to').val() || ''
            };
        }

        function loadDetections() {
            const payload = $.extend({
                action: 'aipdb_get_detections',
                nonce: aipdb_admin.nonce,
                page: state.page,
                orderby: state.orderby,
                order: state.order,
                per_page: 20
            }, collectFilters());

            $detectionsTable.css('opacity', '0.5');

            $.ajax({
                url: aipdb_admin.ajax_url,
                type: 'POST',
                data: payload,
                success: function (response) {
                    if (response.success) {
                        renderDetections(response.data);
                    } else {
                        showTopNotice('error', (response.data && response.data.message) || 'Error.');
                    }
                },
                error: function () {
                    showTopNotice('error', aipdb_admin.strings.server_error || 'Server error.');
                },
                complete: function () {
                    $detectionsTable.css('opacity', '1');
                }
            });
        }

        function renderDetections(data) {
            const rows = data.data || [];
            $('#select-all-detections').prop('checked', false);

            if (!rows.length) {
                $detectionsTable.html(
                    '<tr class="no-items"><td class="colspanchange" colspan="9" style="text-align:center;padding:20px;">' +
                    (aipdb_admin.strings.no_detections || 'No detections found.') + '</td></tr>'
                );
            } else {
                $detectionsTable.html(rows.map(renderRow).join(''));
            }

            // Contador
            $('#detections-count').text(
                (aipdb_admin.strings.total_label || 'Total:') + ' ' + data.total + ' ' +
                (aipdb_admin.strings.detections_label || 'detections')
            );

            renderPagination(data);
        }

        function renderRow(d) {
            const score = renderScore(d.abuseipdb_score);
            const country = d.country_code
                ? '<span class="aipdb-country" title="' + escapeHtml(d.country_name || d.country_code) + '">' + escapeHtml(d.country_code) + '</span>'
                : '<span class="aipdb-country-unknown">-</span>';
            const action = d.action_taken
                ? '<span class="aipdb-action aipdb-action-' + escapeHtml(d.action_taken) + '">' + escapeHtml(humanize(d.action_taken)) + '</span>'
                : '<span class="aipdb-action-none">' + (aipdb_admin.strings.none_label || 'None') + '</span>';

            return '' +
                '<tr data-id="' + escapeHtml(d.id) + '">' +
                    '<th class="check-column"><input type="checkbox" class="detection-checkbox" value="' + escapeHtml(d.id) + '" /></th>' +
                    '<td>' + escapeHtml(d.created_at_formatted || d.created_at) + '</td>' +
                    '<td><strong>' + escapeHtml(d.ip_address) + '</strong>' +
                        '<div class="row-actions"><span><a href="https://www.abuseipdb.com/check/' + encodeURIComponent(d.ip_address) + '" target="_blank" rel="noopener noreferrer">' +
                        (aipdb_admin.strings.check_abuseipdb || 'Check AbuseIPDB') + '</a></span></div>' +
                    '</td>' +
                    '<td><span class="aipdb-event-type aipdb-event-' + escapeHtml(d.event_type) + '">' + escapeHtml(humanize(d.event_type)) + '</span></td>' +
                    '<td><span class="aipdb-threat-level aipdb-threat-' + escapeHtml(d.threat_level) + '">' + escapeHtml(ucfirst(d.threat_level)) + '</span></td>' +
                    '<td>' + score + '</td>' +
                    '<td>' + country + '</td>' +
                    '<td>' + action + '</td>' +
                    '<td class="aipdb-actions">' +
                        '<button type="button" class="button button-small aipdb-view-details" data-id="' + escapeHtml(d.id) + '"><span class="dashicons dashicons-visibility"></span> ' + (aipdb_admin.strings.details_label || 'Details') + '</button>' +
                        '<button type="button" class="button button-small aipdb-block-detection-ip" data-ip="' + escapeHtml(d.ip_address) + '"><span class="dashicons dashicons-shield-alt"></span> ' + (aipdb_admin.strings.block_ip_label || 'Block IP') + '</button>' +
                        '<button type="button" class="button button-small button-link-delete aipdb-delete-detection" data-id="' + escapeHtml(d.id) + '" data-ip="' + escapeHtml(d.ip_address) + '"><span class="dashicons dashicons-trash"></span> ' + (aipdb_admin.strings.delete_label || 'Delete') + '</button>' +
                    '</td>' +
                '</tr>';
        }

        function renderScore(value) {
            if (value === null || value === undefined || value === '') {
                return '<span class="aipdb-score-na">N/A</span>';
            }
            const n = parseInt(value, 10);
            const level = n >= 75 ? 'high' : (n >= 25 ? 'medium' : 'low');
            return '<span class="aipdb-score aipdb-score-' + level + '">' + n + '%</span>';
        }

        function renderPagination(data) {
            const $container = $('.aipdb-pagination');
            const $links = $container.find('.pagination-links');

            $container.find('.displaying-num').text(data.total + ' ' + (aipdb_admin.strings.items_label || 'items'));

            if (data.pages <= 1) {
                $links.empty();
                $container.hide();
                return;
            }

            let html = '';
            for (let i = 1; i <= data.pages; i++) {
                html += '<button type="button" class="button aipdb-page-btn ' + (i === data.current_page ? 'current' : '') + '" data-page="' + i + '">' + i + '</button>';
            }
            $links.html(html);
            $container.show();
        }

        function humanize(str) {
            return ucfirst(String(str).replace(/_/g, ' '));
        }
        function ucfirst(str) {
            str = String(str || '');
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Filtros
        $('.aipdb-filter-apply').on('click', function () {
            state.page = 1;
            loadDetections();
        });

        $('.aipdb-filter-reset').on('click', function () {
            $('#detection-search, #min-score-filter, #date-from, #date-to').val('');
            $('#event-type-filter, #threat-level-filter, #country-filter').val('');
            state.page = 1;
            state.orderby = 'created_at';
            state.order = 'DESC';
            loadDetections();
        });

        $('#detection-search').on('keypress', function (e) {
            if (e.which === 13) { state.page = 1; loadDetections(); }
        });

        // Paginación (delegada — los botones se re-renderizan)
        $(document).on('click', '.aipdb-page-btn', function () {
            state.page = parseInt($(this).data('page'), 10) || 1;
            loadDetections();
        });

        // Ordenamiento
        $('.aipdb-detections-table thead .sortable').on('click', function (e) {
            e.preventDefault();
            const col = $(this).data('sort');
            if (!col) { return; }
            if (state.orderby === col) {
                state.order = state.order === 'ASC' ? 'DESC' : 'ASC';
            } else {
                state.orderby = col;
                state.order = 'DESC';
            }
            state.page = 1;
            loadDetections();
        });

        // Select-all
        $('#select-all-detections').on('change', function () {
            $('.detection-checkbox').prop('checked', $(this).is(':checked'));
        });

        // Bulk actions
        $('.aipdb-bulk-apply').on('click', function () {
            const action = $('#bulk-action-selector').val();
            const ids = $('.detection-checkbox:checked').map(function () { return $(this).val(); }).get();

            if (!action) { return; }
            if (!ids.length) {
                showTopNotice('error', aipdb_admin.strings.no_selection || 'No items selected.');
                return;
            }
            if (action === 'delete' && !window.confirm(aipdb_admin.strings.confirm_bulk_delete || 'Delete the selected detections?')) {
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: aipdb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aipdb_bulk_action',
                    bulk_action: action,
                    ids: ids,
                    nonce: aipdb_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        showTopNotice('success', response.data.message);
                        $('#bulk-action-selector').val('');
                        loadDetections();
                    } else {
                        showTopNotice('error', response.data.message);
                    }
                },
                error: function () {
                    showTopNotice('error', aipdb_admin.strings.server_error || 'Server error.');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    function appendBlocklistRow(data) {
        const $tbody = $('#aipdb-blocklist-body');
        $tbody.find('tr.no-items').remove();

        const date = new Date((data.blocked_at || (Date.now() / 1000)) * 1000);
        const dateStr = date.toLocaleString();

        const row =
            '<tr data-ip="' + escapeHtml(data.ip) + '">' +
                '<td><strong>' + escapeHtml(data.ip) + '</strong></td>' +
                '<td>' + escapeHtml(data.reason || '') + '</td>' +
                '<td>' + escapeHtml(dateStr) + '</td>' +
                '<td><em>' + (aipdb_admin.strings.permanent || 'Permanent') + '</em></td>' +
                '<td>' +
                    '<button type="button" class="button button-small button-link-delete aipdb-unblock-ip" data-ip="' + escapeHtml(data.ip) + '">' +
                        '<span class="dashicons dashicons-no-alt"></span> ' +
                        (aipdb_admin.strings.unblock || 'Unblock') +
                    '</button>' +
                '</td>' +
            '</tr>';
        $tbody.prepend(row);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showTopNotice(type, message) {
        const cls = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + cls + ' is-dismissible" style="margin:15px 0;"><p>' + message + '</p></div>');
        $('.wrap h1').first().after($notice);
        setTimeout(function () {
            $notice.fadeOut(400, function () { $(this).remove(); });
        }, 5000);
    }
});
