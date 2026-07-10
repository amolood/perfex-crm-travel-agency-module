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

<a href="<?= site_url('travel_agency'); ?>" class="btn btn-default">
    <i class="fa-solid fa-arrow-left tw-mr-1"></i>
    <?= _l('go_back'); ?>
</a>
