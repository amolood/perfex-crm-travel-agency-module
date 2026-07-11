<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700"><?php echo e($title); ?></h4>
                <?php echo form_open($this->uri->uri_string(), ['id' => 'supplier-form']); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <?php $value = (isset($supplier) ? $supplier->name : ''); ?>
                        <?php echo render_input('name', 'travel_agency_supplier_name', $value); ?>

                        <?php $value = (isset($supplier) ? $supplier->type : ''); ?>
                        <?php echo render_input('type', 'travel_agency_supplier_type', $value); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?php $value = (isset($supplier) ? $supplier->email : ''); ?>
                                <?php echo render_input('email', 'travel_agency_supplier_email', $value, 'email'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php $value = (isset($supplier) ? $supplier->phonenumber : ''); ?>
                                <?php echo render_input('phonenumber', 'travel_agency_supplier_phonenumber', $value); ?>
                            </div>
                        </div>

                        <?php $value = (isset($supplier) ? $supplier->city : ''); ?>
                        <?php echo render_input('city', 'travel_agency_supplier_city', $value); ?>

                        <?php $value = (isset($supplier) ? $supplier->address : ''); ?>
                        <?php echo render_textarea('address', 'travel_agency_supplier_address', $value); ?>

                        <?php $value = (isset($supplier) ? $supplier->notes : ''); ?>
                        <?php echo render_textarea('notes', 'travel_agency_supplier_notes', $value); ?>

                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="active" id="active" <?php if (!isset($supplier) || $supplier->active == 1) {
                                echo 'checked';
                            } ?>>
                            <label for="active"><?php echo _l('travel_agency_active'); ?></label>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                    </div>
                </div>
                <?php echo form_close(); ?>

                <?php if (isset($supplier)) { ?>
                <div class="panel_s" id="supplier-account-summary">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-base tw-text-neutral-700"><?php echo _l('travel_agency_supplier_account_summary'); ?></h4>
                        <?php if (empty($account_summary)) { ?>
                        <p class="text-muted"><?php echo _l('travel_agency_supplier_no_dues'); ?></p>
                        <?php } else { ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo _l('travel_agency_supplier_total_due'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_total_paid'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_balance'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($account_summary as $row) { ?>
                                <tr>
                                    <td><?php echo e(app_format_money($row['due'], $row['currency'])); ?></td>
                                    <td><?php echo e(app_format_money($row['paid'], $row['currency'])); ?></td>
                                    <td>
                                        <strong class="<?php echo $row['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo e(app_format_money($row['balance'], $row['currency'])); ?>
                                        </strong>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s" id="supplier-payments">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-bold tw-text-base tw-text-neutral-700"><?php echo _l('travel_agency_supplier_payments'); ?></h4>

                        <?php if (staff_can('edit', 'travel_agency_suppliers')) { ?>
                        <?php echo form_open(admin_url('travel_agency/supplier_payment/' . $supplier->id)); ?>
                        <div class="row">
                            <div class="col-md-3">
                                <?php echo render_input('amount', 'travel_agency_supplier_payment_amount', '', 'number', ['step' => '0.01', 'min' => '0.01']); ?>
                            </div>
                            <div class="col-md-3">
                                <?php
                                $selected = get_base_currency()->id;
                                echo render_select('currency', $currencies, ['id', 'name'], 'travel_agency_supplier_payment_currency', $selected, [], [], '', '', false); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_date_input('date', 'travel_agency_supplier_payment_date', _d(date('Y-m-d'))); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_input('notes', 'travel_agency_supplier_payment_notes'); ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo _l('travel_agency_supplier_add_payment'); ?></button>
                        <?php echo form_close(); ?>
                        <hr>
                        <?php } ?>

                        <?php if (empty($payments)) { ?>
                        <p class="text-muted"><?php echo _l('travel_agency_supplier_no_payments'); ?></p>
                        <?php } else { ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo _l('travel_agency_supplier_payment_date'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_payment_amount'); ?></th>
                                    <th><?php echo _l('travel_agency_supplier_payment_notes'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment) { ?>
                                <tr>
                                    <td><?php echo _d($payment['date']); ?></td>
                                    <td><?php echo e(app_format_money($payment['amount'], $this->currencies_model->get($payment['currency']))); ?></td>
                                    <td><?php echo e($payment['notes']); ?></td>
                                    <td class="text-right">
                                        <?php if (staff_can('edit', 'travel_agency_suppliers')) { ?>
                                        <a href="<?php echo admin_url('travel_agency/delete_supplier_payment/' . $payment['id']); ?>" class="text-danger _delete"><?php echo _l('delete'); ?></a>
                                        <?php } ?>
                                    </td>
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
    appValidateForm($('#supplier-form'), {
        name: 'required',
        phonenumber: 'required',
    });
});
</script>
</body>

</html>
