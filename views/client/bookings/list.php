<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading tw-flex tw-items-center tw-justify-between">
    <?= _l('travel_agency_my_bookings'); ?>
    <a href="<?= site_url('travel_agency/packages'); ?>" class="btn btn-primary btn-sm">
        <i class="fa-regular fa-plus tw-mr-1"></i>
        <?= _l('travel_agency_apply_for_package'); ?>
    </a>
</h4>

<div class="panel_s">
    <div class="panel-body">
        <table class="table dt-table">
            <thead>
                <tr>
                    <th><?= _l('travel_agency_booking_package'); ?></th>
                    <th><?= _l('travel_agency_package_destination'); ?></th>
                    <th><?= _l('travel_agency_booking_travelers'); ?></th>
                    <th><?= _l('travel_agency_booking_travel_date'); ?></th>
                    <th><?= _l('status'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)) { ?>
                <tr>
                    <td colspan="6" class="text-center"><?= _l('travel_agency_no_bookings_found'); ?></td>
                </tr>
                <?php } ?>
                <?php foreach ($bookings as $booking) { ?>
                <tr>
                    <td><?= e($booking['package_name']); ?></td>
                    <td><?= e($booking['package_destination']); ?></td>
                    <td><?= e($booking['travelers']); ?></td>
                    <td><?= $booking['travel_date'] ? e(_d($booking['travel_date'])) : ''; ?></td>
                    <td>
                        <span class="label label-<?= travel_booking_status_label_class($booking['status']); ?>">
                            <?= e(format_travel_booking_status($booking['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= site_url('travel_agency/itinerary/' . $booking['id']); ?>"><?= _l('travel_agency_view_itinerary'); ?></a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
