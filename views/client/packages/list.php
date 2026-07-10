<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading">
    <?= _l('travel_agency_available_packages'); ?>
</h4>

<?php if (empty($packages)) { ?>
<div class="panel_s">
    <div class="panel-body">
        <p class="text-muted no-margin"><?= _l('travel_agency_no_packages_available'); ?></p>
    </div>
</div>
<?php } else { ?>
<div class="row">
    <?php foreach ($packages as $package) { ?>
    <div class="col-md-4">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="tw-mt-0 tw-mb-1 tw-font-semibold tw-text-base tw-text-neutral-700">
                    <?= e($package['name']); ?>
                </h4>
                <p class="text-muted tw-mb-2"><?= e($package['destination']); ?></p>

                <p class="tw-mb-1">
                    <strong><?= e(app_format_money($package['price'], $package['currency_row'])); ?></strong>
                    <span class="text-muted"><?= _l('travel_agency_package_price'); ?></span>
                </p>

                <p class="tw-mb-1 text-muted">
                    <?= _l('travel_agency_package_duration_days'); ?>: <?= e($package['duration_days']); ?>
                </p>

                <?php if ($package['seats_remaining'] !== null) { ?>
                <p class="tw-mb-3 text-muted">
                    <?= _l('travel_agency_seats_remaining'); ?>: <?= e($package['seats_remaining']); ?>
                </p>
                <?php } ?>

                <a href="<?= site_url('travel_agency/apply/' . $package['id']); ?>" class="btn btn-primary btn-block">
                    <?= _l('travel_agency_apply_for_package'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
<?php } ?>
