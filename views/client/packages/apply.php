<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading">
    <?= e($package['name']); ?>
</h4>

<div class="row">
    <div class="col-md-8">
        <div class="panel_s">
            <div class="panel-body">
                <p class="text-muted"><?= e($package['destination']); ?></p>
                <?php if (!empty($package['description'])) { ?>
                <p><?= nl2br(e($package['description'])); ?></p>
                <?php } ?>

                <table class="table">
                    <tr>
                        <td class="bold"><?= _l('travel_agency_package_price'); ?></td>
                        <td><?= e(app_format_money($package['price'], $currency_row)); ?></td>
                    </tr>
                    <tr>
                        <td class="bold"><?= _l('travel_agency_package_duration_days'); ?></td>
                        <td><?= e($package['duration_days']); ?></td>
                    </tr>
                    <?php if ($package['start_date']) { ?>
                    <tr>
                        <td class="bold"><?= _l('travel_agency_package_start_date'); ?></td>
                        <td><?= e(_d($package['start_date'])); ?></td>
                    </tr>
                    <?php } ?>
                    <?php if ($package['end_date']) { ?>
                    <tr>
                        <td class="bold"><?= _l('travel_agency_package_end_date'); ?></td>
                        <td><?= e(_d($package['end_date'])); ?></td>
                    </tr>
                    <?php } ?>
                </table>

                <?php echo form_open($this->uri->uri_string()); ?>
                <?php if ($package['start_date']) { ?>
                <?php echo render_input('travelers', 'travel_agency_booking_travelers', 1, 'number', ['min' => 1]); ?>
                <?php } else { ?>
                <div class="row">
                    <div class="col-md-6">
                        <?php echo render_input('travelers', 'travel_agency_booking_travelers', 1, 'number', ['min' => 1]); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo render_date_input('travel_date', 'travel_agency_booking_travel_date'); ?>
                    </div>
                </div>
                <?php } ?>
                <?php echo render_textarea('notes', 'travel_agency_booking_notes'); ?>

                <button type="submit" class="btn btn-primary">
                    <?= _l('travel_agency_submit_application'); ?>
                </button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
