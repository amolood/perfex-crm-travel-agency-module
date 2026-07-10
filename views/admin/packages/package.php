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
                        <?php $value = (isset($package) ? $package->name : ''); ?>
                        <?php echo render_input('name', 'travel_agency_package_name', $value); ?>

                        <?php $value = (isset($package) ? $package->destination : ''); ?>
                        <?php echo render_input('destination', 'travel_agency_package_destination', $value); ?>

                        <?php
                        $selected = (isset($package) ? $package->type_id : '');
                        echo render_select('type_id', $types, ['id', 'name'], 'travel_agency_package_type', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>

                        <?php
                        $selected = (isset($package) ? $package->supplier_id : '');
                        echo render_select('supplier_id', $suppliers, ['id', 'name'], 'travel_agency_package_supplier', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?php $value = (isset($package) ? $package->duration_days : 1); ?>
                                <?php echo render_input('duration_days', 'travel_agency_package_duration_days', $value, 'number'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php $value = (isset($package) ? $package->seats_available : ''); ?>
                                <?php echo render_input('seats_available', 'travel_agency_package_seats_available', $value, 'number'); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php $value = (isset($package) ? _d($package->start_date) : ''); ?>
                                <?php echo render_date_input('start_date', 'travel_agency_package_start_date', $value); ?>
                            </div>
                            <div class="col-md-6">
                                <?php $value = (isset($package) ? _d($package->end_date) : ''); ?>
                                <?php echo render_date_input('end_date', 'travel_agency_package_end_date', $value); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <?php $value = (isset($package) ? $package->price : ''); ?>
                                <?php echo render_input('price', 'travel_agency_package_price', $value, 'number', ['step' => '0.01']); ?>
                            </div>
                            <div class="col-md-4">
                                <?php $value = (isset($package) ? $package->cost : ''); ?>
                                <?php echo render_input('cost', 'travel_agency_package_cost', $value, 'number', ['step' => '0.01']); ?>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $selected = (isset($package) ? $package->currency : get_base_currency()->id);
                                echo render_select('currency', $currencies, ['id', 'name'], 'travel_agency_package_currency', $selected, [], [], '', '', false); ?>
                            </div>
                        </div>

                        <?php if (isset($package)) { ?>
                        <?php $profit = $this->travel_packages_model->calculate_profit($package); ?>
                        <p class="text-muted">
                            <?php echo _l('travel_agency_package_profit_per_seat'); ?>:
                            <strong><?php echo e(app_format_money($profit['profit_per_seat'], $this->currencies_model->get($package->currency))); ?></strong>
                            (<?php echo e($profit['margin_percent']); ?>%)
                        </p>
                        <?php } ?>

                        <?php $value = (isset($package) ? $package->description : ''); ?>
                        <?php echo render_textarea('description', 'travel_agency_package_description', $value); ?>

                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="active" id="active" <?php if (!isset($package) || $package->active == 1) {
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
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    appValidateForm($('form'), {
        name: 'required',
        destination: 'required',
        price: 'required',
    });
});
</script>
</body>

</html>
