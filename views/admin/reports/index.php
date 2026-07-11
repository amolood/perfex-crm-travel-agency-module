<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-base tw-text-neutral-700"><?php echo _l('travel_agency_reports_monthly_bookings'); ?></h4>
                        <canvas id="travel-monthly-bookings-chart" height="90"></canvas>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-base tw-text-neutral-700"><?php echo _l('travel_agency_reports_package_revenue'); ?></h4>
                        <?php if (empty($package_revenue)) { ?>
                        <p class="text-muted"><?php echo _l('travel_agency_reports_no_data'); ?></p>
                        <?php } else { ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo _l('travel_agency_package_name'); ?></th>
                                    <th><?php echo _l('travel_agency_package_destination'); ?></th>
                                    <th><?php echo _l('travel_agency_reports_seats_booked'); ?></th>
                                    <th><?php echo _l('travel_agency_reports_occupancy'); ?></th>
                                    <th><?php echo _l('travel_agency_reports_revenue'); ?></th>
                                    <th><?php echo _l('travel_agency_reports_margin'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($package_revenue as $row) { ?>
                                <?php $pkg_currency = $row['currency'] ? $this->currencies_model->get($row['currency']) : get_base_currency(); ?>
                                <tr>
                                    <td><a href="<?php echo admin_url('travel_agency/package/' . $row['id']); ?>"><?php echo e($row['name']); ?></a></td>
                                    <td><?php echo e($row['destination']); ?></td>
                                    <td><?php echo e($row['seats_booked']); ?></td>
                                    <td>
                                        <?php if ($row['occupancy_percent'] === null) { ?>
                                        <span class="text-muted">&mdash;</span>
                                        <?php } else { ?>
                                        <?php echo e($row['occupancy_percent']); ?>%
                                        <?php } ?>
                                    </td>
                                    <td><?php echo e(app_format_money($row['revenue'], $pkg_currency)); ?></td>
                                    <td>
                                        <strong class="<?php echo $row['margin_total'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo e(app_format_money($row['margin_total'], $pkg_currency)); ?>
                                        </strong>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </div>
                </div>

                <?php if (staff_can('view', 'travel_agency_suppliers')) { ?>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-base tw-text-neutral-700"><?php echo _l('travel_agency_reports_supplier_summary'); ?></h4>
                        <?php if (empty($supplier_summary)) { ?>
                        <p class="text-muted"><?php echo _l('travel_agency_reports_no_data'); ?></p>
                        <?php } else { ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo _l('travel_agency_supplier_name'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_type'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_linked_packages'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_total_due'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_total_paid'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_balance'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supplier_summary as $row) { ?>
                                <tr>
                                    <td><a href="<?php echo admin_url('travel_agency/supplier/' . $row['id']); ?>"><?php echo e($row['name']); ?></a></td>
                                    <td><?php echo e($row['type']); ?></td>
                                    <td><?php echo e($row['package_count']); ?></td>
                                    <?php if ($row['currency']) { ?>
                                    <td><?php echo e(app_format_money($row['due'], $row['currency'])); ?></td>
                                    <td><?php echo e(app_format_money($row['paid'], $row['currency'])); ?></td>
                                    <td>
                                        <strong class="<?php echo $row['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo e(app_format_money($row['balance'], $row['currency'])); ?>
                                        </strong>
                                    </td>
                                    <?php } else { ?>
                                    <td colspan="3" class="text-muted"><?php echo _l('travel_agency_supplier_no_dues'); ?></td>
                                    <?php } ?>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>

            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    var ctx = document.getElementById('travel-monthly-bookings-chart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($monthly_bookings, 'month')); ?>,
            datasets: [{
                label: '<?php echo _l('travel_agency_reports_bookings_count'); ?>',
                data: <?php echo json_encode(array_map('intval', array_column($monthly_bookings, 'bookings_count'))); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
            }]
        },
        options: {
            scales: {
                yAxes: [{ ticks: { beginAtZero: true } }]
            }
        }
    });
});
</script>
</body>

</html>
