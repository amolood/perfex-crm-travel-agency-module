<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (staff_can('create', 'travel_agency_suppliers')) { ?>
                <div class="tw-mb-2">
                    <a href="<?php echo admin_url('travel_agency/supplier'); ?>" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('add_new', _l('travel_agency_supplier_lowercase')); ?>
                    </a>
                </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <div class="form-group tw-mb-3" style="max-width: 250px;">
                            <label for="supplier-type-filter"><?php echo _l('travel_agency_supplier_type'); ?></label>
                            <select id="supplier-type-filter" class="form-control select-picker">
                                <option value=""><?php echo _l('travel_agency_all_types'); ?></option>
                                <?php foreach ($supplier_types as $type) { ?>
                                <option value="<?php echo e($type['type']); ?>"><?php echo e($type['type']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php render_datatable([
                        _l('travel_agency_supplier_name'),
                        _l('travel_agency_supplier_type'),
                        _l('travel_agency_supplier_email'),
                        _l('travel_agency_supplier_phonenumber'),
                        _l('status'),
                        ], 'travel_agency_suppliers'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    var travelSuppliersTable = initDataTable('.table-travel_agency_suppliers', window.location.href);

    $('#supplier-type-filter').on('change', function() {
        travelSuppliersTable.column(1).search(this.value).draw();
    });
});
</script>
</body>

</html>
