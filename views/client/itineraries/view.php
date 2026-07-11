<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading">
    <?= e($booking->package_name); ?>
    <span class="label label-<?= travel_booking_status_label_class($booking->status); ?> tw-ml-2">
        <?= e(format_travel_booking_status($booking->status)); ?>
    </span>
</h4>

<div class="panel_s">
    <div class="panel-body">
        <table class="table">
            <tr>
                <td class="bold"><?= _l('travel_agency_package_destination'); ?></td>
                <td><?= e($booking->package_destination); ?></td>
            </tr>
            <tr>
                <td class="bold"><?= _l('travel_agency_booking_travelers'); ?></td>
                <td><?= e($booking->travelers); ?></td>
            </tr>
            <tr>
                <td class="bold"><?= _l('travel_agency_booking_travel_date'); ?></td>
                <td><?= $booking->travel_date ? e(_d($booking->travel_date)) : ''; ?></td>
            </tr>
            <tr>
                <td class="bold"><?= _l('travel_agency_booking_total'); ?></td>
                <td><?= e(app_format_money($booking->total, $booking_currency)); ?></td>
            </tr>
            <?php if ($booking->invoiceid && !empty($invoice_hash)) { ?>
            <tr>
                <td class="bold"><?= _l('invoice'); ?></td>
                <td><a href="<?= site_url('invoice/' . $booking->invoiceid . '/' . $invoice_hash); ?>"><?= _l('travel_agency_view_invoice'); ?></a></td>
            </tr>
            <?php if ($invoice_total_left !== null) { ?>
            <tr>
                <td class="bold"><?= _l('travel_agency_balance_due'); ?></td>
                <td>
                    <strong class="<?= $invoice_total_left > 0 ? 'text-danger' : 'text-success'; ?>">
                        <?= e(app_format_money($invoice_total_left, $booking_currency)); ?>
                    </strong>
                </td>
            </tr>
            <?php } ?>
            <?php } ?>
            <?php if ($booking->notes) { ?>
            <tr>
                <td class="bold"><?= _l('travel_agency_booking_notes'); ?></td>
                <td><?= nl2br(e($booking->notes)); ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php if ((int) $booking->status !== TRAVEL_BOOKING_STATUS_CANCELLED) { ?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-base"><?= _l('travel_agency_documents'); ?></h4>

        <?php echo form_open_multipart(site_url('travel_agency/upload_booking_document/' . $booking->id)); ?>
        <div class="row">
            <div class="col-md-3">
                <?php
                $document_type_options = [
                    ['id' => 'visa', 'name' => _l('travel_agency_document_type_visa')],
                    ['id' => 'ticket', 'name' => _l('travel_agency_document_type_ticket')],
                    ['id' => 'other', 'name' => _l('travel_agency_document_type_other')],
                ];
                echo render_select('document_type', $document_type_options, ['id', 'name'], 'travel_agency_document_type', 'other', [], [], '', '', false);
                ?>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?= _l('travel_agency_document_file'); ?></label>
                    <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
            </div>
            <div class="col-md-5">
                <?php echo render_input('notes', 'travel_agency_document_notes'); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= _l('travel_agency_document_upload'); ?></button>
        <?php echo form_close(); ?>

        <?php if (!empty($documents)) { ?>
        <hr>
        <ul class="list-unstyled">
            <?php foreach ($documents as $doc) { ?>
            <li class="tw-mb-1">
                <a href="<?= site_url('travel_agency/booking_document/' . $doc['id']); ?>" target="_blank">
                    <?= e(_l('travel_agency_document_type_' . $doc['document_type'])); ?> - <?= e($doc['original_name']); ?>
                </a>
            </li>
            <?php } ?>
        </ul>
        <?php } ?>
    </div>
</div>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-base"><?= _l('travel_agency_request_cancellation'); ?></h4>

        <?php if (!empty($booking->cancellation_requested_at)) { ?>
        <p class="text-muted"><?= _l('travel_agency_cancellation_already_requested'); ?></p>
        <?php } else { ?>
        <?php echo form_open(site_url('travel_agency/request_cancellation/' . $booking->id)); ?>
        <?php echo render_textarea('notes', 'travel_agency_cancellation_request_notes'); ?>
        <button type="submit" class="btn btn-danger"><?= _l('travel_agency_request_cancellation'); ?></button>
        <?php echo form_close(); ?>
        <?php } ?>
    </div>
</div>
<?php } ?>

<a href="<?= site_url('travel_agency'); ?>" class="btn btn-default">
    <i class="fa-solid fa-arrow-left tw-mr-1"></i>
    <?= _l('go_back'); ?>
</a>
