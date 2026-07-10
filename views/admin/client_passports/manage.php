<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                        _l('travel_agency_client_passports_client'),
                        _l('travel_agency_client_passports_passport_number'),
                        _l('travel_agency_client_passports_expiry'),
                        _l('travel_agency_client_passports_status'),
                        ], 'travel_agency_client_passports'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    initDataTable('.table-travel_agency_client_passports', window.location.href);
});
</script>
</body>

</html>
