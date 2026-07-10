<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (staff_can('create', 'travel_agency')) { ?>
                <div class="tw-mb-2">
                    <a href="<?php echo admin_url('travel_agency/package'); ?>" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('add_new', _l('travel_agency_package_lowercase')); ?>
                    </a>
                </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                        _l('travel_agency_package_name'),
                        _l('travel_agency_package_destination'),
                        _l('travel_agency_package_supplier'),
                        _l('travel_agency_package_price'),
                        _l('travel_agency_package_seats_available'),
                        _l('status'),
                        _l('travel_agency_package_profit_per_seat'),
                        ], 'travel_agency_packages'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    initDataTable('.table-travel_agency_packages', window.location.href);
});
</script>
</body>

</html>
