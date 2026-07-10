<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                    <?php echo e($client->company); ?>
                    <a href="<?php echo admin_url('clients/client/' . $client->userid); ?>" class="tw-text-sm tw-font-normal tw-ml-2"><?php echo _l('travel_agency_client_passports_view_client_profile'); ?></a>
                </h4>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-base"><?php echo _l('travel_agency_client_passports_current'); ?></h4>

                        <?php if ($current) { ?>
                        <div class="row">
                            <div class="col-md-4"><strong><?php echo _l('travel_agency_group_member_passport_number'); ?>:</strong> <?php echo e($current['passport_number']); ?></div>
                            <div class="col-md-4"><strong><?php echo _l('travel_agency_group_member_passport_expiry'); ?>:</strong> <?php echo $current['passport_expiry'] ? e(_d($current['passport_expiry'])) : ''; ?></div>
                            <div class="col-md-4"><strong><?php echo _l('travel_agency_group_member_nationality'); ?>:</strong> <?php echo e(travel_agency_format_nationality($current['nationality'])); ?></div>
                        </div>
                        <div class="row tw-mt-2">
                            <div class="col-md-4"><strong><?php echo _l('travel_agency_group_member_passport_surname'); ?>:</strong> <?php echo e($current['surname']); ?></div>
                            <div class="col-md-4"><strong><?php echo _l('travel_agency_group_member_passport_given_names'); ?>:</strong> <?php echo e($current['given_names']); ?></div>
                            <div class="col-md-4"><strong><?php echo _l('travel_agency_group_member_date_of_birth'); ?>:</strong> <?php echo $current['date_of_birth'] ? e(_d($current['date_of_birth'])) : ''; ?></div>
                        </div>
                        <div class="row tw-mt-2">
                            <div class="col-md-4"><strong><?php echo _l('travel_agency_group_member_gender'); ?>:</strong> <?php echo e(travel_agency_format_gender($current['gender'])); ?></div>
                        </div>
                        <?php if ($current['scan_file']) { ?>
                        <?php
                        $scan_url    = admin_url('travel_agency/view_client_passport_file/' . $current['id']);
                        $scan_is_img = in_array(strtolower(pathinfo($current['scan_file'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']);
                        ?>
                        <?php if ($scan_is_img) { ?>
                        <div class="tw-mt-3 tw-text-center">
                            <a href="<?php echo $scan_url; ?>" data-lightbox="client-passport-<?php echo $current['id']; ?>" data-title="<?php echo e($client->company); ?>">
                                <img src="<?php echo $scan_url; ?>" class="img-responsive" style="max-width:420px;max-height:420px;border:1px solid #e2e8f0;border-radius:6px;margin:0 auto;cursor:zoom-in;" alt="">
                            </a>
                        </div>
                        <?php } else { ?>
                        <div class="tw-mt-2">
                            <a href="<?php echo $scan_url; ?>" target="_blank"><?php echo _l('travel_agency_group_member_view_passport_scan'); ?></a>
                        </div>
                        <?php } ?>
                        <?php } ?>
                        <?php } else { ?>
                        <p class="text-muted"><?php echo _l('travel_agency_client_passports_none_on_file'); ?></p>
                        <?php } ?>
                    </div>
                </div>

                <?php if (staff_can('edit', 'customers')) { ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-base"><?php echo _l('travel_agency_client_passports_upload_new'); ?></h4>
                        <p class="text-muted"><?php echo _l('travel_agency_group_member_passport_scan_hint'); ?></p>

                        <?php echo form_open_multipart(admin_url('travel_agency/client_passport/' . $client->userid), ['id' => 'client_passport_form']); ?>
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('travel_agency_group_member_passport_tab'); ?></label>
                            <input type="file" name="passport_scan" id="client_passport_scan_input" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div id="client_passport_ocr_status" class="tw-mb-3" style="display:none;"></div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('passport_number', 'travel_agency_group_member_passport_number'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_date_input('passport_expiry', 'travel_agency_group_member_passport_expiry'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('surname', 'travel_agency_group_member_passport_surname'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('given_names', 'travel_agency_group_member_passport_given_names'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?php
                                $nationality_options = [];
                                foreach (travel_agency_nationality_names() as $code => $name) {
                                    $nationality_options[] = ['id' => $code, 'name' => $name];
                                }
                                echo render_select('nationality', $nationality_options, ['id', 'name'], 'travel_agency_group_member_nationality', '', ['data-none-selected-text' => _l('dropdown_non_selected_tex')]);
                                ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_date_input('date_of_birth', 'travel_agency_group_member_date_of_birth'); ?>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $gender_options = [
                                    ['id' => 'M', 'name' => _l('travel_agency_gender_male')],
                                    ['id' => 'F', 'name' => _l('travel_agency_gender_female')],
                                ];
                                echo render_select('gender', $gender_options, ['id', 'name'], 'travel_agency_group_member_gender', '', ['data-none-selected-text' => _l('dropdown_non_selected_tex')]);
                                ?>
                            </div>
                        </div>
                        <input type="hidden" name="mrz_raw" id="client_passport_mrz_raw">

                        <button type="submit" class="btn btn-primary"><?php echo _l('travel_agency_client_passports_save_new'); ?></button>
                        <?php echo form_close(); ?>
                    </div>
                </div>
                <?php } ?>

                <?php if (count($history) > 0) { ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-base"><?php echo _l('travel_agency_client_passports_history'); ?></h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo _l('travel_agency_group_member_passport_number'); ?></th>
                                    <th><?php echo _l('travel_agency_group_member_passport_expiry'); ?></th>
                                    <th><?php echo _l('travel_agency_client_passports_uploaded_on'); ?></th>
                                    <th><?php echo _l('travel_agency_client_passports_status'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $passport) { ?>
                                <tr>
                                    <td><?php echo e($passport['passport_number']); ?></td>
                                    <td><?php echo $passport['passport_expiry'] ? e(_d($passport['passport_expiry'])) : ''; ?></td>
                                    <td><?php echo e(_dt($passport['datecreated'])); ?></td>
                                    <td>
                                        <?php if ($passport['is_current'] == 1) { ?>
                                        <span class="label label-success"><?php echo _l('travel_agency_client_passports_current'); ?></span>
                                        <?php } else { ?>
                                        <span class="label label-default"><?php echo _l('travel_agency_client_passports_superseded'); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($passport['scan_file']) { ?>
                                        <a href="<?php echo admin_url('travel_agency/view_client_passport_file/' . $passport['id']); ?>" target="_blank"><?php echo _l('travel_agency_group_member_view_passport_scan'); ?></a>
                                        <?php } ?>
                                        <?php if (staff_can('edit', 'customers')) { ?>
                                        <a href="<?php echo admin_url('travel_agency/delete_client_passport/' . $passport['id']); ?>" class="text-danger _delete tw-ml-2"><?php echo _l('delete'); ?></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    var travel_agency_assets_base = <?php echo json_encode(module_dir_url('travel_agency', 'assets/')); ?>;
</script>
<script src="<?php echo module_dir_url('travel_agency', 'assets/js/mrz_parser.js'); ?>"></script>
<script src="<?php echo module_dir_url('travel_agency', 'assets/js/vendor/tesseract/tesseract.min.js'); ?>"></script>
<script src="<?php echo module_dir_url('travel_agency', 'assets/js/passport_ocr.js'); ?>"></script>
<script>
$(function() {
    appValidateForm($('#client_passport_form'), {
        passport_number: 'required',
    });

    $('#client_passport_scan_input').on('change', function() {
        var file = this.files && this.files[0];

        if (!file || !/^image\/(jpeg|png)$/.test(file.type) || !window.TravelAgencyPassportOcr) {
            return;
        }

        var statusEl = $('#client_passport_ocr_status');
        statusEl.show().removeClass('alert-danger alert-warning alert-success').addClass('alert alert-info')
            .html('<i class="fa-solid fa-spinner fa-spin tw-mr-1"></i> ' + '<?php echo _l('travel_agency_group_member_passport_ocr_scanning'); ?>');

        window.TravelAgencyPassportOcr.scanPassportFile(file).then(function (mrz) {
            if (!mrz) {
                statusEl.removeClass('alert-info').addClass('alert-warning')
                    .html('<?php echo _l('travel_agency_group_member_passport_ocr_not_found'); ?>');

                return;
            }

            var form = $('#client_passport_form');
            if (mrz.surname) { form.find('input[name="surname"]').val(mrz.surname); }
            if (mrz.givenNames) { form.find('input[name="given_names"]').val(mrz.givenNames); }
            if (mrz.nationality) { form.find('select[name="nationality"]').val(mrz.nationality).selectpicker('refresh'); }
            if (mrz.dateOfBirth) { form.find('input[name="date_of_birth"]').val(mrz.dateOfBirth); }
            if (mrz.sex) { form.find('select[name="gender"]').val(mrz.sex).selectpicker('refresh'); }
            if (mrz.passportNumber) { form.find('input[name="passport_number"]').val(mrz.passportNumber); }
            if (mrz.passportExpiry) { form.find('input[name="passport_expiry"]').val(mrz.passportExpiry); }
            form.find('input[name="mrz_raw"]').val(mrz.rawLine1 + '\n' + mrz.rawLine2);

            if (mrz.confidence === 'high') {
                statusEl.removeClass('alert-info').addClass('alert-success')
                    .html('<i class="fa-solid fa-circle-check tw-mr-1"></i> ' + '<?php echo _l('travel_agency_group_member_passport_ocr_success'); ?>');
            } else {
                statusEl.removeClass('alert-info').addClass('alert-warning')
                    .html('<i class="fa-solid fa-triangle-exclamation tw-mr-1"></i> ' + '<?php echo _l('travel_agency_group_member_passport_ocr_low_confidence'); ?>');
            }
        }).catch(function () {
            statusEl.removeClass('alert-info').addClass('alert-danger')
                .html('<?php echo _l('travel_agency_group_member_passport_ocr_error'); ?>');
        });
    });
});
</script>
</body>

</html>
