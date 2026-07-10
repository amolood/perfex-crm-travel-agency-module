<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php if (empty($suppliers)) { ?>
                        <p class="text-muted"><?php echo _l('travel_agency_suppliers_not_found'); ?></p>
                        <?php } else { ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo _l('travel_agency_supplier_name'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_total_due'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_total_paid'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_balance'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suppliers as $supplier) { ?>
                                <?php $rows = $account_summaries[$supplier['id']] ?? []; ?>
                                <?php if (empty($rows)) { ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo admin_url('travel_agency/supplier/' . $supplier['id']); ?>" class="tw-font-medium"><?php echo e($supplier['name']); ?></a>
                                    </td>
                                    <td colspan="3" class="text-muted"><?php echo _l('travel_agency_supplier_no_dues'); ?></td>
                                    <td></td>
                                </tr>
                                <?php } else { ?>
                                <?php foreach ($rows as $i => $row) { ?>
                                <tr>
                                    <td>
                                        <?php if ($i === 0) { ?>
                                        <a href="<?php echo admin_url('travel_agency/supplier/' . $supplier['id']); ?>" class="tw-font-medium"><?php echo e($supplier['name']); ?></a>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo e(app_format_money($row['due'], $row['currency'])); ?></td>
                                    <td><?php echo e(app_format_money($row['paid'], $row['currency'])); ?></td>
                                    <td>
                                        <strong class="<?php echo $row['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo e(app_format_money($row['balance'], $row['currency'])); ?>
                                        </strong>
                                    </td>
                                    <td class="text-right">
                                        <?php if ($i === 0) { ?>
                                        <a href="<?php echo admin_url('travel_agency/supplier/' . $supplier['id'] . '#supplier-payments'); ?>"><?php echo _l('travel_agency_supplier_add_payment'); ?></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                                <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>

</html>
