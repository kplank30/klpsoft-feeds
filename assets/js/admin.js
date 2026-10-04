// Copyright (C) 2026 klp-soft. All rights reserved.

jQuery(document).ready(function ($) {

    $overlayDialog = $("#klpsfefo-ajax-wait-overlay").dialog({
        autoOpen: false,
        modal: true,
        resizable: false,
        draggable: false,
        width: 350,
        dialogClass: 'klpsfefo-no-close-dialog', 
        closeOnEscape: false,
        show: false,
        hide: false
    });
    
    $(document).on('change', '.mapping-source-select', function () {
        var $row = $(this).closest('tr');
        if ( $(this).val() === 'custom_field' || $(this).val() === 'static_val' ) {
            $row.find('.mapping-custom-input').fadeIn(150).focus();
        } else {
            $row.find('.mapping-custom-input').fadeOut(150);
        }
    });

    $(document).on('change', '.klpsfefo-feed-type-toggle', function () {
        if ($(this).val() === 'file') {
            $('.klpsfefo-file-options-row, .klpsfefo-file-path-wrapper').fadeIn(150);
        } else {
            $('.klpsfefo-file-options-row, .klpsfefo-file-path-wrapper').fadeOut(150);
        }
    });

    $('#klpsfefo-active-feed-select').on('change', function () {
        var feedId = $(this).val();
        var feedChannel = $('#klpsfefo_current_feed_channel').val();
        window.location.href = '?page=klpsoft-feeds&tab='+feedChannel+'&feed_id=' + feedId + '&feed_channel='+feedChannel;
    });

    $('#klpsfefo-feed-generate-now-btn').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $msg = $('#klpsfefo-manual-status-msg');
//        var feedId = $btn.data('feedid');
        var feedId = $('#klpsfefo-active-feed-select').val();

        $overlayDialog.dialog("open");
        $btn.prop('disabled', true);
        
        $msg.text(klpFeedSettings.saving).css('color', '#333');

        $.ajax({
            url: klpFeedSettings.ajax_url,
            type: 'POST',
            data: {
                action: 'klpsfefo_feed_generate_manually',
                nonce: klpFeedSettings.nonce,
                feed_id: feedId,
                feed_channel: $('#klpsfefo_current_feed_channel').val()
            },
            success: function (response) {
                $overlayDialog.dialog("close");
                $btn.prop('disabled', false);
                if (response.success) {
                    $msg.text(response.data.message).css('color', 'green');
                } else {
                    $msg.text(response.data.message).css('color', 'red');
                }
            },
            error: function () {
                $overlayDialog.dialog("close");
                $btn.prop('disabled', false);
                $msg.text('Server Error').css('color', 'red');
            }
        });
    });

   $('#klpsfefo-mapping-details').on('toggle', function() {
        var is_open = this.hasAttribute('open') ? '1' : '0';
        var feedId = $(this).data('feed-id');

        $.ajax({
            url: klpFeedSettings.ajax_url,
            type: 'POST',
            data: {
                action: 'klpsfefo_save_feed_table_state',
                nonce: klpFeedSettings.nonce,
                feed_id: feedId,
                feed_channel: $('#klpsfefo_current_feed_channel').val(),
                state: is_open
            },
            success: function(response) {
            }
        });
    });
    
    $('#klpsfefo-feed-save-btn').on('click', function (e) {
        e.preventDefault();

        var $btn = $('#klpsfefo-feed-save-btn');
        var $msg = $('#klpsfefo-feed-status-msg');

        if ( $('#klpsfefo_feedname').length > 0 && $('#klpsfefo_feedname').val().length < 3 ) {
            showKlpWarningAlert('Please fill feed name (min. 3 chars)');
            return;
        }

        $overlayDialog.dialog("open");
        $btn.prop('disabled', true);
        $msg.text(klpFeedSettings.saving).css('color', '#333');

        $.ajax({
            url: klpFeedSettings.ajax_url,
            type: 'POST',
            data: {
                action: 'klpsfefo_save_klp_feed_settings', 
                nonce: klpFeedSettings.nonce,
                form_data: $('#klpsfefo-feed-settings-form').serialize(),
                form_data_filters: $('#klpsfefo-feed-filter-settings-form').serialize()
            },
            success: function (response) {
                $overlayDialog.dialog("close");
                $btn.prop('disabled', false);
                if (response.success) {
                    $msg.text(klpFeedSettings.saved).css('color', 'green');
                    
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                } else {
                    $msg.text(response.data.message ? response.data.message : 'Error saving').css('color', 'red');
                }
            },
            error: function () {
                $overlayDialog.dialog("close");
                $btn.prop('disabled', false);
                $msg.text('Server Error').css('color', 'red');
            }
        });
    });

    if (!$('#klpsfefo-feed-dialog').length) {
        $('body').append(
            '<div id="klpsfefo-feed-dialog" title="' + klpFeedSettings.i18n.dialog_title + '" style="display:none;">' +
            '<p style="margin-top: 10px; font-weight: 500;">' + klpFeedSettings.i18n.dialog_text + '</p>' +
            '<input type="text" id="klpsfefo-new-feed-name" class="regular-text" value="" style="width:100%; margin-bottom:15px;">' +
            '</div>'
        );
    }

    $('#klpsfefo-active-feed-select').after(
        '<button type="button" id="klpsfefo-add-feed-btn" class="button button-secondary" style="margin-left: 10px; vertical-align: middle;">' +
        '➕ ' + klpFeedSettings.i18n.add_feed_btn +
        '</button>' +
        '<button type="button" id="klpsfefo-delete-feed-btn" class="button button-secondary" style="margin-left: 10px; vertical-align: middle; color: #b32d2e; ">' +
        '➖ ' + klpFeedSettings.i18n.delete_feed_btn +
        '</button>'
    );

    $('#klpsfefo-add-feed-btn').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);

        $('#klpsfefo-feed-dialog').dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            buttons: [
                {
                    text: klpFeedSettings.i18n.btn_create,
                    class: "button button-primary",
                    click: function() {
                        var dialogRef = $(this);
                        var feedName = $('#klpsfefo-new-feed-name').val();
                        var feedChannel = $('#klpsfefo_current_feed_channel').val();

                        if (!feedName || feedName.trim() === "") {
                            $('#klpsfefo-new-feed-name').css('border-color', '#dc3232').focus();
                            return;
                        }
                        
                        $overlayDialog.dialog("open");
                        $btn.prop('disabled', true);
                        if (dialogRef) {
                            dialogRef.dialog("close");
                        }

                        $.ajax({
                            url: klpFeedSettings.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'klpsfefo_create_new_feed',
                                nonce: klpFeedSettings.nonce,
                                feed_name: feedName,
                                feed_channel: feedChannel
                            },
                            success: function (response) {
                                $overlayDialog.dialog("close");
                                if (response.success) {
                                    window.location.href = '?page=klpsoft-feeds&tab='+response.data.feed_channel+'&feed_id=' + response.data.new_id;
                                } else {
                                    showKlpErrorAlert(response.data.message);
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function () {
                                $overlayDialog.dialog("close");
                                showKlpErrorAlert('Server Error');
                                $btn.prop('disabled', false);
                            }
                        });
                    }
                },
                {
                    text: klpFeedSettings.i18n.btn_cancel,
                    class: "button button-secondary",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
    });

    $('#klpsfefo-delete-feed-btn').on('click', function (e) {
        e.preventDefault();

        var $btn = $(this); 
        var currentFeedId = $('#klpsfefo-active-feed-select').val();
        var currentChannel = $('#klpsfefo_current_feed_channel').val();

        if ( !currentFeedId || currentFeedId === 'default' || currentFeedId.trim() === "" ) {
            showKlpWarningAlert(klpFeedSettings.i18n.invalid_feed_id_text || 'Please select a valid profile.');
            return;
        }

        var $confirmDialog = $('<div id="klpsfefo-feed-delete-confirm-dialog" title="' + klpFeedSettings.i18n.delete_feed_btn + '">' +
            '<p style="margin-top: 10px; font-weight: 500;">' + klpFeedSettings.i18n.confirm_delete_text + '</p>' +
            '</div>');

        $confirmDialog.dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            close: function() {
                $(this).dialog('destroy').remove();
            },
            buttons: [
                {
                    text: klpFeedSettings.i18n.btn_delete_confirm,
                    class: "button button-primary",
                    style: "background: #b32d2e; border-color: #b32d2e; text-shadow: none;", 
                    click: function() {
                        var dialogRef = $(this);

                        $btn.prop('disabled', true);

                        $.ajax({
                            url: klpFeedSettings.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'klpsfefo_delete_feed',
                                nonce: klpFeedSettings.nonce,
                                feed_id: currentFeedId,
                                feed_channel: currentChannel 
                            },
                            success: function (response) {
                                dialogRef.dialog("close"); 

                                if (response.success) {
                                    window.location.href = '?page=klpsoft-feeds&tab=' + currentChannel;
                                } else {
                                    showKlpErrorAlert(response.data.message || 'Error');
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function () {
                                dialogRef.dialog("close"); 
                                showKlpErrorAlert('Server Error');
                                $btn.prop('disabled', false);
                            }
                        });
                    }
                },
                {
                    text: klpFeedSettings.i18n.btn_cancel,
                    class: "button button-secondary",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
    });

    function showKlpErrorAlert(message) {
        var $errorDialog = $('<div id="klpsfefo-feed-error-dialog" title="Error">' +
            '<p style="margin-top: 10px; font-weight: 500; color: #b32d2e;">' + message + '</p>' +
            '</div>');

        $errorDialog.dialog({
            resizable: false,
            height: "auto",
            width: 350,
            modal: true,
            close: function() {
                $(this).dialog('destroy').remove();
            },
            buttons: [
                {
                    text: "OK",
                    class: "button button-primary",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
    }

    function showKlpWarningAlert(message) {
        var $warningDialog = $('<div id="klpsfefo-feed-warning-dialog" title="Warning">' +
            '<p style="margin-top: 10px; font-weight: 500; color: #d54e21;">' + message + '</p>' +
            '</div>');

        $warningDialog.dialog({
            resizable: false,
            height: "auto",
            width: 350,
            modal: true,
            close: function() {
                $(this).dialog('destroy').remove();
            },
            buttons: [
                {
                    text: "OK",
                    class: "button button-primary",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
    }

    function showKlpSuccessAlert(message) {
        var $warningDialog = $('<div id="klpsfefo-feed-success-dialog" title="Success">' +
            '<p style="margin-top: 10px; font-weight: 500; color: green;">' + message + '</p>' +
            '</div>');

        $warningDialog.dialog({
            resizable: false,
            height: "auto",
            width: 350,
            modal: true,
            close: function() {
                $(this).dialog('destroy').remove();
            },
            buttons: [
                {
                    text: "OK",
                    class: "button button-primary",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
    }

    let rowIndex = $('#klpsfefo-filter-table tbody tr.filter-row:not(.klpsfefo-no-filters-placeholder)').length;

    $('#klpsfefo-btn-add-filter').on('click', function() {
        $('.klpsfefo-no-filters-placeholder').remove();

        let template = $('#klpsfefo-filter-row-template').html();
        template = template.replaceAll('{{INDEX}}', rowIndex);

        $('#klpsfefo-filter-table tbody').append(template);
        rowIndex++;
    });

    $('#klpsfefo-btn-delete-filter').on('click', function() {
        let checkedBoxes = $('.klpsfefo-delete-filter-checkbox:checked');

        if ( checkedBoxes.length === 0 ) {
            showKlpWarningAlert('Please select a filter row checkbox.');
            return;
        }

        if ( confirm('Ausgewählten Filter wirklich löschen?') ) {
            checkedBoxes.closest('tr').remove();
            $('#klpsfefo-select-all-filters').prop('checked', false);

            if ($('#klpsfefo-filter-table tbody tr').length === 0) {
                $('#klpsfefo-filter-table tbody').append(
                    '<tr class="filter-row klpsfefo-no-filters-placeholder"><td colspan="5" style="text-align: center; color: #666; padding: 20px;">Noch keine Filter definiert. Klicke auf "Feld hinzufügen".</td></tr>'
                );
            }
        }
    });

    $('#klpsfefo-select-all-filters').on('change', function() {
        $('.klpsfefo-delete-filter-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#klpsfefo_save_filter_settings').on('click', function(e) {
        e.preventDefault();

        let $form = $('#klpsfefo-feed-filter-settings-form');
        let $submitButton = $('#klpsfefo_save_filter_settings');
        
        $submitButton.prop('disabled', true).text('Saving...');

        let formData = $form.serializeArray();
        
        formData.push({ name: 'action', value: 'klpsfefo_save_feed_filters' });
        formData.push({ name: 'nonce', value: klpFeedSettings.nonce });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showKlpSuccessAlert(response.data.message);
                } else {
                    showKlpErrorAlert('Error: ' + response.data.message);
                }
            },
            error: function() {
                showKlpErrorAlert('unknown error occured!');
            },
            complete: function() {
                $submitButton.prop('disabled', false).text('Save filters');
            }
        });
    });
});
