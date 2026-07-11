<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700"><?php echo e($title); ?></h4>
                <?php echo form_open($this->uri->uri_string()); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (!isset($booking)) { ?>
                        <div class="form-group">
                            <div class="radio radio-primary radio-inline">
                                <input type="radio" name="client_source" id="client_source_existing" value="existing" checked>
                                <label for="client_source_existing"><?php echo _l('travel_agency_booking_existing_client'); ?></label>
                            </div>
                            <div class="radio radio-primary radio-inline">
                                <input type="radio" name="client_source" id="client_source_new" value="new">
                                <label for="client_source_new"><?php echo _l('travel_agency_booking_new_client'); ?></label>
                            </div>
                        </div>
                        <?php } ?>

                        <div id="client_source_existing_wrapper">
                        <?php
                        $selected = (isset($booking) ? $booking->clientid : '');
                        echo render_select('clientid', $clients, ['userid', 'company'], 'travel_agency_booking_client', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                        </div>

                        <?php if (!isset($booking)) { ?>
                        <div id="client_source_new_wrapper" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <?php echo render_input('new_client_company', 'travel_agency_booking_new_client_company'); ?>
                                </div>
                                <div class="col-md-6">
                                    <?php echo render_input('new_client_email', 'travel_agency_booking_new_client_email', '', 'email'); ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php echo render_input('new_client_firstname', 'travel_agency_booking_new_client_firstname'); ?>
                                </div>
                                <div class="col-md-6">
                                    <?php echo render_input('new_client_lastname', 'travel_agency_booking_new_client_lastname'); ?>
                                </div>
                            </div>
                            <?php echo render_input('new_client_phonenumber', 'travel_agency_booking_new_client_phonenumber'); ?>
                        </div>
                        <?php } ?>

                        <?php
                        $selected = (isset($booking) ? $booking->package_id : '');
                        echo render_select('package_id', $packages, ['id', 'name'], 'travel_agency_booking_package', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?php $value = (isset($booking) ? $booking->travelers : 1); ?>
                                <?php echo render_input('travelers', 'travel_agency_booking_travelers', $value, 'number'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php $value = (isset($booking) ? _d($booking->travel_date) : ''); ?>
                                <?php echo render_date_input('travel_date', 'travel_agency_booking_travel_date', $value); ?>
                            </div>
                        </div>

                        <?php if (isset($booking)) { ?>
                        <?php
                        $selected = $booking->status;
                        echo render_select('status', $statuses, ['id', 'name'], 'status', $selected, [], [], '', '', false); ?>
                        <?php } ?>

                        <?php $value = (isset($booking) ? $booking->notes : ''); ?>
                        <?php echo render_textarea('notes', 'travel_agency_booking_notes', $value); ?>

                        <?php if (!isset($booking)) { ?>
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="create_invoice" id="create_invoice" value="true" checked>
                            <label for="create_invoice"><?php echo _l('travel_agency_booking_create_invoice'); ?></label>
                        </div>
                        <?php } elseif ($booking->invoiceid) { ?>
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('invoice'); ?></label><br>
                            <a href="<?php echo admin_url('invoices/list_invoices/' . $booking->invoiceid); ?>" target="_blank">#<?php echo e($booking->invoiceid); ?></a>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                    </div>
                </div>
                <?php echo form_close(); ?>

                <?php if (isset($booking) && (int) $booking->status === TRAVEL_BOOKING_STATUS_CANCELLED) { ?>
                <div class="panel_s" id="booking-cancellation-details">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-base tw-text-neutral-700"><?php echo _l('travel_agency_booking_cancellation_details'); ?></h4>
                        <p><strong><?php echo _l('travel_agency_booking_cancellation_reason'); ?>:</strong> <?php echo e($booking->cancellation_reason); ?></p>
                        <?php if ($booking->refund_amount > 0) { ?>
                        <p><strong><?php echo _l('travel_agency_booking_refund_amount'); ?>:</strong> <?php echo e(app_format_money($booking->refund_amount, get_base_currency())); ?></p>
                        <?php } ?>
                        <p class="text-muted"><?php echo _d($booking->cancelled_at); ?></p>
                        <?php if ($booking->invoiceid) { ?>
                        <div class="alert alert-warning tw-mt-2">
                            <?php echo _l('travel_agency_booking_cancelled_invoice_reminder'); ?>
                            <a href="<?php echo admin_url('invoices/list_invoices/' . $booking->invoiceid); ?>" target="_blank">#<?php echo e($booking->invoiceid); ?></a>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } elseif (isset($booking) && staff_can('edit', 'travel_agency')) { ?>
                <div class="panel_s" id="booking-cancel">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-base tw-text-neutral-700"><?php echo _l('travel_agency_booking_cancel'); ?></h4>
                        <?php echo form_open(admin_url('travel_agency/cancel_booking/' . $booking->id), ['id' => 'booking-cancel-form']); ?>
                        <?php echo render_textarea('cancellation_reason', 'travel_agency_booking_cancellation_reason'); ?>
                        <?php echo render_input('refund_amount', 'travel_agency_booking_refund_amount', '0.00', 'number', ['step' => '0.01', 'min' => '0']); ?>
                        <button type="submit" class="btn btn-danger"><?php echo _l('travel_agency_booking_cancel'); ?></button>
                        <?php echo form_close(); ?>
                    </div>
                </div>
                <?php } ?>

                <?php if (isset($booking)) { ?>
                <?php
                $document_rel_type = 'booking';
                $document_rel_id   = $booking->id;
                $this->load->view('admin/documents_panel', ['documents' => $documents, 'document_rel_type' => $document_rel_type, 'document_rel_id' => $document_rel_id]);
                ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    var validationRules = {
        package_id: 'required',
        travelers: 'required',
    };

    <?php if (!isset($booking)) { ?>
    validationRules.clientid = {
        required: function() {
            return $('input[name="client_source"]:checked').val() === 'existing';
        },
    };
    validationRules.new_client_company = {
        required: function() {
            return $('input[name="client_source"]:checked').val() === 'new';
        },
    };
    validationRules.new_client_email = {
        required: function() {
            return $('input[name="client_source"]:checked').val() === 'new';
        },
        email: true,
    };

    $('input[name="client_source"]').on('change', function() {
        if ($(this).val() === 'new') {
            $('#client_source_existing_wrapper').hide();
            $('#client_source_new_wrapper').show();
        } else {
            $('#client_source_new_wrapper').hide();
            $('#client_source_existing_wrapper').show();
        }
    });
    <?php } else { ?>
    validationRules.clientid = 'required';
    <?php } ?>

    appValidateForm($('form'), validationRules);

    appValidateForm($('#booking-cancel-form'), {
        cancellation_reason: 'required',
    });

    $('#booking-cancel-form').on('submit', function(e) {
        if (!confirm('<?php echo _l('travel_agency_booking_cancel_confirm'); ?>')) {
            e.preventDefault();
        }
    });
});
</script>
</body>

</html>
