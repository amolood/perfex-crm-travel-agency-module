<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading">
    <?= _l('travel_agency_my_passport'); ?>
</h4>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-base"><?= _l('travel_agency_client_passports_current'); ?></h4>

        <?php if ($current) { ?>
        <table class="table">
            <tr>
                <td class="bold"><?= _l('travel_agency_group_member_passport_number'); ?></td>
                <td><?= e($current['passport_number']); ?></td>
            </tr>
            <tr>
                <td class="bold"><?= _l('travel_agency_group_member_passport_expiry'); ?></td>
                <td><?= $current['passport_expiry'] ? e(_d($current['passport_expiry'])) : ''; ?></td>
            </tr>
            <tr>
                <td class="bold"><?= _l('travel_agency_group_member_nationality'); ?></td>
                <td><?= e($current['nationality']); ?></td>
            </tr>
            <?php if ($current['scan_file']) { ?>
            <tr>
                <td class="bold"><?= _l('travel_agency_group_member_passport_tab'); ?></td>
                <td><a href="<?= site_url('travel_agency/passport_file/' . $current['id']); ?>" target="_blank"><?= _l('travel_agency_group_member_view_passport_scan'); ?></a></td>
            </tr>
            <?php } ?>
        </table>
        <?php } else { ?>
        <p class="text-muted"><?= _l('travel_agency_client_passports_none_on_file'); ?></p>
        <?php } ?>
    </div>
</div>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-base"><?= _l('travel_agency_client_passports_upload_new'); ?></h4>
        <p class="text-muted"><?= _l('travel_agency_group_member_passport_scan_hint'); ?></p>

        <?= form_open_multipart(site_url('travel_agency/passport'), ['id' => 'my_passport_form']); ?>
        <div class="form-group">
            <label class="control-label"><?= _l('travel_agency_group_member_passport_tab'); ?></label>
            <input type="file" name="passport_scan" id="my_passport_scan_input" accept=".jpg,.jpeg,.png,.pdf">
        </div>
        <div id="my_passport_ocr_status" class="tw-mb-3" style="display:none;"></div>

        <div class="row">
            <div class="col-md-6">
                <?= render_input('passport_number', 'travel_agency_group_member_passport_number'); ?>
            </div>
            <div class="col-md-6">
                <?= render_date_input('passport_expiry', 'travel_agency_group_member_passport_expiry'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= render_input('surname', 'travel_agency_group_member_passport_surname'); ?>
            </div>
            <div class="col-md-6">
                <?= render_input('given_names', 'travel_agency_group_member_passport_given_names'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= render_input('nationality', 'travel_agency_group_member_nationality'); ?>
            </div>
            <div class="col-md-4">
                <?= render_date_input('date_of_birth', 'travel_agency_group_member_date_of_birth'); ?>
            </div>
            <div class="col-md-4">
                <?= render_input('gender', 'travel_agency_group_member_gender'); ?>
            </div>
        </div>
        <input type="hidden" name="mrz_raw" value="">

        <button type="submit" class="btn btn-primary"><?= _l('travel_agency_client_passports_save_new'); ?></button>
        <?= form_close(); ?>
    </div>
</div>

<?php if (count($history) > 1) { ?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-base"><?= _l('travel_agency_client_passports_history'); ?></h4>
        <table class="table">
            <thead>
                <tr>
                    <th><?= _l('travel_agency_group_member_passport_number'); ?></th>
                    <th><?= _l('travel_agency_group_member_passport_expiry'); ?></th>
                    <th><?= _l('travel_agency_client_passports_uploaded_on'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $passport) { ?>
                <tr>
                    <td><?= e($passport['passport_number']); ?></td>
                    <td><?= $passport['passport_expiry'] ? e(_d($passport['passport_expiry'])) : ''; ?></td>
                    <td><?= e(_dt($passport['datecreated'])); ?></td>
                    <td>
                        <a href="<?= site_url('travel_agency/delete_passport/' . $passport['id']); ?>" class="text-danger" onclick="return confirm('<?= addslashes(_l('travel_agency_client_passports_confirm_delete')); ?>');"><?= _l('delete'); ?></a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } ?>

<script>
    var travel_agency_assets_base = <?= json_encode(module_dir_url('travel_agency', 'assets/')); ?>;
</script>
<script src="<?= module_dir_url('travel_agency', 'assets/js/mrz_parser.js'); ?>"></script>
<script src="<?= module_dir_url('travel_agency', 'assets/js/vendor/tesseract/tesseract.min.js'); ?>"></script>
<script src="<?= module_dir_url('travel_agency', 'assets/js/passport_ocr.js'); ?>"></script>
<script>
$(function() {
    $('#my_passport_scan_input').on('change', function() {
        var file = this.files && this.files[0];

        if (!file || !/^image\/(jpeg|png)$/.test(file.type) || !window.TravelAgencyPassportOcr) {
            return;
        }

        var statusEl = $('#my_passport_ocr_status');
        statusEl.show().removeClass('alert-danger alert-warning alert-success').addClass('alert alert-info')
            .html('<i class="fa-solid fa-spinner fa-spin tw-mr-1"></i> ' + '<?= _l('travel_agency_group_member_passport_ocr_scanning'); ?>');

        window.TravelAgencyPassportOcr.scanPassportFile(file).then(function (mrz) {
            if (!mrz) {
                statusEl.removeClass('alert-info').addClass('alert-warning')
                    .html('<?= _l('travel_agency_group_member_passport_ocr_not_found'); ?>');

                return;
            }

            var form = $('#my_passport_form');
            if (mrz.surname) { form.find('input[name="surname"]').val(mrz.surname); }
            if (mrz.givenNames) { form.find('input[name="given_names"]').val(mrz.givenNames); }
            if (mrz.nationality) { form.find('input[name="nationality"]').val(mrz.nationality); }
            if (mrz.dateOfBirth) { form.find('input[name="date_of_birth"]').val(mrz.dateOfBirth); }
            if (mrz.sex) { form.find('input[name="gender"]').val(mrz.sex); }
            if (mrz.passportNumber) { form.find('input[name="passport_number"]').val(mrz.passportNumber); }
            if (mrz.passportExpiry) { form.find('input[name="passport_expiry"]').val(mrz.passportExpiry); }
            form.find('input[name="mrz_raw"]').val(mrz.rawLine1 + '\n' + mrz.rawLine2);

            if (mrz.confidence === 'high') {
                statusEl.removeClass('alert-info').addClass('alert-success')
                    .html('<i class="fa-solid fa-circle-check tw-mr-1"></i> ' + '<?= _l('travel_agency_group_member_passport_ocr_success'); ?>');
            } else {
                statusEl.removeClass('alert-info').addClass('alert-warning')
                    .html('<i class="fa-solid fa-triangle-exclamation tw-mr-1"></i> ' + '<?= _l('travel_agency_group_member_passport_ocr_low_confidence'); ?>');
            }
        }).catch(function () {
            statusEl.removeClass('alert-info').addClass('alert-danger')
                .html('<?= _l('travel_agency_group_member_passport_ocr_error'); ?>');
        });
    });
});
</script>

<a href="<?= site_url('travel_agency'); ?>" class="btn btn-default tw-mt-3">
    <i class="fa-solid fa-arrow-left tw-mr-1"></i>
    <?= _l('go_back'); ?>
</a>
