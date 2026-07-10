<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$upcoming_groups   = [];
$supplier_balances = [];

if (is_staff_member() && staff_can('view', 'travel_agency')) {
    $this->load->model('travel_agency/travel_groups_model');
    $upcoming_groups = $this->travel_groups_model->get_upcoming_departures(14);
}

if (is_staff_member() && staff_can('view', 'travel_agency_suppliers')) {
    $this->load->model('travel_agency/travel_supplier_payments_model');
    $this->load->model('travel_agency/travel_suppliers_model');

    $all_summaries = $this->travel_supplier_payments_model->get_all_account_summaries();
    $suppliers     = $this->travel_suppliers_model->get();
    $suppliers_map = [];
    foreach ($suppliers as $supplier) {
        $suppliers_map[$supplier['id']] = $supplier['name'];
    }

    foreach ($all_summaries as $supplier_id => $currencies) {
        foreach ($currencies as $row) {
            if ($row['balance'] > 0) {
                $supplier_balances[] = [
                    'name'     => $suppliers_map[$supplier_id] ?? '',
                    'balance'  => $row['balance'],
                    'currency' => $row['currency'],
                ];
            }
        }
    }
}

$has_content = count($upcoming_groups) > 0 || count($supplier_balances) > 0;
?>
<div class="widget<?php if (!$has_content) {
    echo ' hide';
} ?>" id="widget-<?php echo create_widget_id('travel_agency'); ?>">
    <?php if ($has_content) { ?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body padding-10">
                    <div class="widget-dragger"></div>

                    <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-space-x-1.5 rtl:tw-space-x-reverse tw-p-1.5">
                        <i class="fa-solid fa-plane tw-text-neutral-500"></i>
                        <span class="tw-text-neutral-700"><?php echo _l('travel_agency'); ?></span>
                    </p>

                    <hr class="-tw-mx-3 tw-mt-3 tw-mb-3">

                    <?php if (count($upcoming_groups) > 0) { ?>
                    <h5 class="tw-font-semibold tw-text-neutral-600 tw-px-1"><?php echo _l('travel_agency_widget_upcoming_departures'); ?></h5>
                    <?php foreach ($upcoming_groups as $group) { ?>
                    <div class="tw-px-1 tw-py-1.5 tw-flex tw-justify-between tw-items-center">
                        <div>
                            <a href="<?php echo admin_url('travel_agency/group/' . $group['id']); ?>" class="tw-font-medium"><?php echo e($group['name']); ?></a>
                            <br>
                            <small class="text-muted"><?php echo e($group['package_destination']); ?> &middot; <?php echo e($group['members_count']); ?> <?php echo _l('travel_agency_group_members_count'); ?></small>
                        </div>
                        <span class="label label-info"><?php echo e(_d($group['departure_date'])); ?></span>
                    </div>
                    <?php } ?>
                    <?php } ?>

                    <?php if (count($supplier_balances) > 0) { ?>
                    <hr class="-tw-mx-3 tw-my-3">
                    <h5 class="tw-font-semibold tw-text-neutral-600 tw-px-1"><?php echo _l('travel_agency_widget_supplier_balances'); ?></h5>
                    <?php foreach ($supplier_balances as $row) { ?>
                    <div class="tw-px-1 tw-py-1.5 tw-flex tw-justify-between tw-items-center">
                        <span><?php echo e($row['name']); ?></span>
                        <strong class="text-danger"><?php echo e(app_format_money($row['balance'], $row['currency'])); ?></strong>
                    </div>
                    <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
