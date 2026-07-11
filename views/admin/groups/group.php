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
                        <?php $value = (isset($group) ? $group->name : ''); ?>
                        <?php echo render_input('name', 'travel_agency_group_name', $value); ?>

                        <?php
                        $selected = (isset($group) ? $group->package_id : '');
                        echo render_select('package_id', $packages, ['id', 'name'], 'travel_agency_group_package', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?php $value = (isset($group) ? _d($group->departure_date) : ''); ?>
                                <?php echo render_date_input('departure_date', 'travel_agency_group_departure_date', $value); ?>
                            </div>
                            <div class="col-md-6">
                                <?php $value = (isset($group) ? _d($group->return_date) : ''); ?>
                                <?php echo render_date_input('return_date', 'travel_agency_group_return_date', $value); ?>
                            </div>
                        </div>

                        <?php $value = (isset($group) ? $group->seats_total : ''); ?>
                        <?php echo render_input('seats_total', 'travel_agency_group_seats_total', $value, 'number'); ?>

                        <?php if (isset($group)) { ?>
                        <?php
                        $selected = $group->status;
                        echo render_select('status', $statuses, ['id', 'name'], 'status', $selected, [], [], '', '', false); ?>
                        <?php } ?>

                        <hr>
                        <h4 class="tw-font-semibold tw-text-base"><?php echo _l('travel_agency_group_hotel_section'); ?></h4>
                        <p class="text-muted"><?php echo _l('travel_agency_group_itinerary_hint'); ?></p>

                        <div id="itinerary_stops_wrapper">
                            <?php foreach ($stops as $i => $stop) { ?>
                            <div class="row itinerary-stop-row tw-mb-2">
                                <div class="col-md-5">
                                    <input type="text" name="stops[<?php echo $i; ?>][city]" class="form-control" placeholder="<?php echo _l('travel_agency_group_stop_city'); ?>" value="<?php echo e($stop['city']); ?>">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" min="1" name="stops[<?php echo $i; ?>][days]" class="form-control" placeholder="<?php echo _l('travel_agency_group_stop_days'); ?>" value="<?php echo e($stop['days']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="stops[<?php echo $i; ?>][hotel_name]" class="form-control" placeholder="<?php echo _l('travel_agency_group_stop_hotel_name'); ?>" value="<?php echo e($stop['hotel_name']); ?>">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-default remove-itinerary-stop"><i class="fa-regular fa-trash-can"></i></button>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <button type="button" class="btn btn-default tw-mb-3" id="add_itinerary_stop">
                            <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('travel_agency_group_add_stop'); ?>
                        </button>

                        <hr>
                        <h4 class="tw-font-semibold tw-text-base"><?php echo _l('travel_agency_group_carrier_section'); ?></h4>

                        <div id="transport_wrapper">
                            <?php foreach ($transport as $i => $entry) { ?>
                            <div class="row transport-row tw-mb-2">
                                <div class="col-md-5">
                                    <input type="text" name="transport[<?php echo $i; ?>][carrier_name]" class="form-control" placeholder="<?php echo _l('travel_agency_group_carrier_name'); ?>" value="<?php echo e($entry['carrier_name']); ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="transport[<?php echo $i; ?>][carrier_type]" class="form-control" placeholder="<?php echo _l('travel_agency_group_carrier_type'); ?>" value="<?php echo e($entry['carrier_type']); ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="transport[<?php echo $i; ?>][carrier_reference]" class="form-control" placeholder="<?php echo _l('travel_agency_group_carrier_reference'); ?>" value="<?php echo e($entry['carrier_reference']); ?>">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-default remove-transport-row"><i class="fa-regular fa-trash-can"></i></button>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <button type="button" class="btn btn-default tw-mb-3" id="add_transport_row">
                            <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('travel_agency_group_add_transport'); ?>
                        </button>

                        <hr>
                        <?php $value = (isset($group) ? $group->notes : ''); ?>
                        <?php echo render_textarea('notes', 'travel_agency_group_notes', $value); ?>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                    </div>
                </div>
                <?php echo form_close(); ?>

                <?php if (isset($group)) { ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg tw-text-neutral-700">
                            <?php echo _l('travel_agency_group_members'); ?>
                            <span class="label label-default tw-ml-1"><?php echo e($group->members_count); ?></span>
                        </h4>

                        <?php
                        $group_is_active = !in_array((int) $group->status, [TRAVEL_GROUP_STATUS_DEPARTED, TRAVEL_GROUP_STATUS_COMPLETED, TRAVEL_GROUP_STATUS_CANCELLED], true);
                        ?>
                        <?php if (staff_can('edit', 'travel_agency') && $group_is_active) { ?>
                        <button type="button" class="btn btn-default tw-mb-3" data-toggle="modal" data-target="#add_group_member_modal">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('travel_agency_group_add_member'); ?>
                        </button>
                        <?php } ?>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><?php echo _l('travel_agency_group_member_traveler_name'); ?></th>
                                    <th><?php echo _l('travel_agency_group_member_client'); ?></th>
                                    <th><?php echo _l('travel_agency_group_member_passport_number'); ?></th>
                                    <th><?php echo _l('travel_agency_group_member_passport_expiry'); ?></th>
                                    <th><?php echo _l('travel_agency_group_member_visa_status'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($members)) { ?>
                                <tr>
                                    <td colspan="7" class="text-center"><?php echo _l('travel_agency_group_no_members'); ?></td>
                                </tr>
                                <?php } ?>
                                <?php foreach ($members as $member) { ?>
                                <?php
                                $member_js = $member;
                                $member_js['passport_expiry'] = $member['passport_expiry'] ? _d($member['passport_expiry']) : '';
                                $member_js['date_of_birth']   = $member['date_of_birth'] ? _d($member['date_of_birth']) : '';
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($member['photo'] != '') { ?>
                                        <img src="<?php echo admin_url('travel_agency/view_group_member_file/' . $member['id'] . '/' . $member['group_id'] . '/photo'); ?>" class="tw-h-8 tw-w-8 tw-rounded-full tw-object-cover" alt="">
                                        <?php } else { ?>
                                        <i class="fa-regular fa-user-circle fa-2x tw-text-neutral-400"></i>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo e($member['traveler_name']); ?></td>
                                    <td><?php echo e($member['client_company']); ?></td>
                                    <td><?php echo e($member['passport_number']); ?></td>
                                    <td>
                                        <?php if ($member['passport_expiry']) { ?>
                                        <?php echo e(_d($member['passport_expiry'])); ?>
                                        <?php
                                        $passport_warning_class = travel_agency_passport_expiry_warning_class($member['passport_expiry'], isset($group) ? $group->departure_date : null);
                                        ?>
                                        <?php if ($passport_warning_class == 'danger') { ?>
                                        <i class="fa-solid fa-triangle-exclamation text-danger tw-ml-1" title="<?php echo _l('travel_agency_group_member_passport_expired_before_departure'); ?>"></i>
                                        <?php } elseif ($passport_warning_class == 'warning') { ?>
                                        <i class="fa-solid fa-triangle-exclamation text-warning tw-ml-1" title="<?php echo _l('travel_agency_group_member_passport_expiring_soon'); ?>"></i>
                                        <?php } ?>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <span class="label label-<?php echo travel_visa_status_label_class($member['visa_status']); ?>">
                                            <?php echo e(format_travel_visa_status($member['visa_status'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <?php if (staff_can('edit', 'travel_agency')) { ?>
                                        <div class="dropdown">
                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa-solid fa-ellipsis-v"></i></a>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li>
                                                    <a href="#" onclick="edit_group_member_details(<?php echo e(json_encode($member_js)); ?>); return false;">
                                                        <?php echo _l('travel_agency_group_member_edit_details'); ?>
                                                    </a>
                                                </li>
                                                <li role="separator" class="divider"></li>
                                                <?php foreach (get_travel_visa_statuses() as $status_id => $status_name) { ?>
                                                <li>
                                                    <a href="<?php echo admin_url('travel_agency/update_group_member_visa_status/' . $member['id'] . '/' . $group->id . '/' . $status_id); ?>">
                                                        <?php echo e($status_name); ?>
                                                    </a>
                                                </li>
                                                <?php } ?>
                                                <li role="separator" class="divider"></li>
                                                <li>
                                                    <a href="<?php echo admin_url('travel_agency/remove_group_member/' . $member['id'] . '/' . $group->id); ?>" class="text-danger _delete">
                                                        <?php echo _l('travel_agency_group_remove_member'); ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal fade" id="add_group_member_modal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <?php echo form_open(admin_url('travel_agency/add_group_member/' . $group->id)); ?>
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                <h4 class="modal-title"><?php echo _l('travel_agency_group_add_member'); ?></h4>
                            </div>
                            <div class="modal-body">
                                <div class="form-group select-placeholder">
                                    <label for="booking_id" class="control-label"><?php echo _l('travel_agency_group_member_booking'); ?></label>
                                    <select name="booking_id" id="booking_id" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <?php echo render_input('traveler_name', 'travel_agency_group_member_traveler_name'); ?>
                                <?php echo render_input('passport_number', 'travel_agency_group_member_passport_number'); ?>
                                <?php echo render_date_input('passport_expiry', 'travel_agency_group_member_passport_expiry'); ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                            </div>
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="edit_group_member_modal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                <h4 class="modal-title"><?php echo _l('travel_agency_group_member_edit_details'); ?></h4>
                            </div>
                            <div class="modal-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li role="presentation" class="active"><a href="#member_details_tab" aria-controls="member_details_tab" role="tab" data-toggle="tab"><?php echo _l('travel_agency_group_member_details_tab'); ?></a></li>
                                    <li role="presentation"><a href="#member_photo_tab" aria-controls="member_photo_tab" role="tab" data-toggle="tab"><?php echo _l('travel_agency_group_member_photo_tab'); ?></a></li>
                                    <li role="presentation"><a href="#member_passport_tab" aria-controls="member_passport_tab" role="tab" data-toggle="tab"><?php echo _l('travel_agency_group_member_passport_tab'); ?></a></li>
                                </ul>
                                <div class="tab-content tw-pt-4">
                                    <div role="tabpanel" class="tab-pane active" id="member_details_tab">
                                        <form id="edit_group_member_details_form" action="" method="post">
                                            <?php echo render_input('traveler_name', 'travel_agency_group_member_traveler_name'); ?>
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
                                                    <?php echo render_input('passport_surname', 'travel_agency_group_member_passport_surname'); ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <?php echo render_input('passport_given_names', 'travel_agency_group_member_passport_given_names'); ?>
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
                                            <input type="hidden" name="passport_mrz_raw" id="edit_member_mrz_raw">
                                            <?php echo render_textarea('notes', 'travel_agency_group_member_notes'); ?>
                                        </form>
                                    </div>
                                    <div role="tabpanel" class="tab-pane" id="member_photo_tab">
                                        <div class="tw-mb-3 tw-text-center" id="member_photo_preview"></div>
                                        <form id="member_photo_upload_form" action="" method="post" enctype="multipart/form-data">
                                            <input type="file" name="photo" accept=".jpg,.jpeg,.png">
                                        </form>
                                    </div>
                                    <div role="tabpanel" class="tab-pane" id="member_passport_tab">
                                        <p class="text-muted"><?php echo _l('travel_agency_group_member_passport_scan_hint'); ?></p>
                                        <div class="tw-mb-3" id="member_passport_scan_preview"></div>
                                        <form id="member_passport_scan_upload_form" action="" method="post" enctype="multipart/form-data">
                                            <input type="file" name="passport_scan" accept=".jpg,.jpeg,.png,.pdf">
                                        </form>
                                        <div id="passport_ocr_status" class="tw-mt-2" style="display:none;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                                <button type="button" class="btn btn-primary" onclick="submit_group_member_edit_modal(); return false;"><?php echo _l('submit'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<?php if (isset($group)) { ?>
<script>
    var travel_agency_assets_base = <?php echo json_encode(module_dir_url('travel_agency', 'assets/')); ?>;
</script>
<script src="<?php echo module_dir_url('travel_agency', 'assets/js/mrz_parser.js'); ?>"></script>
<script src="<?php echo module_dir_url('travel_agency', 'assets/js/vendor/tesseract/tesseract.min.js'); ?>"></script>
<script src="<?php echo module_dir_url('travel_agency', 'assets/js/passport_ocr.js'); ?>"></script>
<?php } ?>
<script>
$(function() {
    appValidateForm($('form').eq(0), {
        name: 'required',
        package_id: 'required',
    });

    var itineraryStopIndex = <?php echo count($stops); ?>;
    var transportRowIndex  = <?php echo count($transport); ?>;

    $('#add_itinerary_stop').on('click', function() {
        var row = $('<div class="row itinerary-stop-row tw-mb-2">' +
            '<div class="col-md-5"><input type="text" name="stops[' + itineraryStopIndex + '][city]" class="form-control" placeholder="<?php echo _l('travel_agency_group_stop_city'); ?>"></div>' +
            '<div class="col-md-2"><input type="number" min="1" name="stops[' + itineraryStopIndex + '][days]" class="form-control" placeholder="<?php echo _l('travel_agency_group_stop_days'); ?>"></div>' +
            '<div class="col-md-4"><input type="text" name="stops[' + itineraryStopIndex + '][hotel_name]" class="form-control" placeholder="<?php echo _l('travel_agency_group_stop_hotel_name'); ?>"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-default remove-itinerary-stop"><i class="fa-regular fa-trash-can"></i></button></div>' +
            '</div>');
        $('#itinerary_stops_wrapper').append(row);
        itineraryStopIndex++;
    });

    $('#itinerary_stops_wrapper').on('click', '.remove-itinerary-stop', function() {
        $(this).closest('.itinerary-stop-row').remove();
    });

    $('#add_transport_row').on('click', function() {
        var row = $('<div class="row transport-row tw-mb-2">' +
            '<div class="col-md-5"><input type="text" name="transport[' + transportRowIndex + '][carrier_name]" class="form-control" placeholder="<?php echo _l('travel_agency_group_carrier_name'); ?>"></div>' +
            '<div class="col-md-3"><input type="text" name="transport[' + transportRowIndex + '][carrier_type]" class="form-control" placeholder="<?php echo _l('travel_agency_group_carrier_type'); ?>"></div>' +
            '<div class="col-md-3"><input type="text" name="transport[' + transportRowIndex + '][carrier_reference]" class="form-control" placeholder="<?php echo _l('travel_agency_group_carrier_reference'); ?>"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-default remove-transport-row"><i class="fa-regular fa-trash-can"></i></button></div>' +
            '</div>');
        $('#transport_wrapper').append(row);
        transportRowIndex++;
    });

    $('#transport_wrapper').on('click', '.remove-transport-row', function() {
        $(this).closest('.transport-row').remove();
    });

    <?php if (isset($group)) { ?>
    $('#add_group_member_modal').on('show.bs.modal', function() {
        var modal  = $(this);
        var select = modal.find('select[name="booking_id"]');
        select.html('<option value=""></option>').data('bookings', {});
        modal.find('input[name="traveler_name"]').val('');
        modal.find('input[name="passport_number"]').val('');
        modal.find('input[name="passport_expiry"]').val('');

        $.get('<?php echo admin_url('travel_agency/get_eligible_group_bookings/' . $group->id); ?>', function(bookings) {
            var bookingsById = {};

            $.each(bookings, function(i, booking) {
                bookingsById[booking.id] = booking;
                select.append('<option value="' + booking.id + '">' + booking.client_company + ' (#' + booking.id + ')</option>');
            });

            select.data('bookings', bookingsById).selectpicker('refresh');
        }, 'json');
    });

    // Auto-fill the traveler's name, passport number, and passport expiry from the selected
    // booking's client the moment a booking is picked - the client's own passport record
    // (surname + given names, as printed on the passport) takes priority over the CRM contact
    // name, since that's what actually needs to match the travel document; falls back to the
    // contact's CRM name only when the client has no passport on file yet. Staff can still edit
    // any of these fields by hand afterwards before submitting.
    $('#add_group_member_modal').on('change', 'select[name="booking_id"]', function() {
        var modal    = $('#add_group_member_modal');
        var bookings = $(this).data('bookings') || {};
        var booking  = bookings[$(this).val()];

        if (!booking) {
            return;
        }

        var fullName = '';
        if (booking.passport_surname || booking.passport_given_names) {
            fullName = [booking.passport_given_names, booking.passport_surname].filter(Boolean).join(' ');
        } else if (booking.contact_firstname || booking.contact_lastname) {
            fullName = [booking.contact_firstname, booking.contact_lastname].filter(Boolean).join(' ');
        }

        if (fullName) {
            modal.find('input[name="traveler_name"]').val(fullName);
        }
        if (booking.passport_number) {
            modal.find('input[name="passport_number"]').val(booking.passport_number);
        }
        if (booking.passport_expiry) {
            modal.find('input[name="passport_expiry"]').val(booking.passport_expiry);
        }
    });

    $('#member_photo_upload_form input[name="photo"]').on('change', function() {
        $('#member_photo_upload_form').submit();
    });

    $('#member_passport_scan_upload_form input[name="passport_scan"]').on('change', function() {
        var fileInput = this;
        var file      = fileInput.files && fileInput.files[0];

        $('#member_passport_scan_upload_form').submit();

        if (file && /^image\/(jpeg|png)$/.test(file.type) && window.TravelAgencyPassportOcr) {
            travel_agency_run_passport_ocr(file);
        }
    });
    <?php } ?>
});

function edit_group_member_details(member) {
    var detailsForm = $('#edit_group_member_details_form');
    detailsForm.attr('action', '<?php echo admin_url('travel_agency/update_group_member'); ?>/' + member.id + '/<?php echo isset($group) ? $group->id : ''; ?>');
    detailsForm.find('input[name="traveler_name"]').val(member.traveler_name);
    detailsForm.find('input[name="passport_number"]').val(member.passport_number);
    detailsForm.find('input[name="passport_expiry"]').val(member.passport_expiry);
    detailsForm.find('input[name="passport_surname"]').val(member.passport_surname);
    detailsForm.find('input[name="passport_given_names"]').val(member.passport_given_names);
    detailsForm.find('select[name="nationality"]').val(member.nationality).selectpicker('refresh');
    detailsForm.find('input[name="date_of_birth"]').val(member.date_of_birth);
    detailsForm.find('select[name="gender"]').val(member.gender).selectpicker('refresh');
    detailsForm.find('input[name="passport_mrz_raw"]').val(member.passport_mrz_raw);
    detailsForm.find('textarea[name="notes"]').val(member.notes);

    $('#member_photo_upload_form').attr('action', '<?php echo admin_url('travel_agency/upload_group_member_file'); ?>/' + member.id + '/<?php echo isset($group) ? $group->id : ''; ?>/photo');
    $('#member_passport_scan_upload_form').attr('action', '<?php echo admin_url('travel_agency/upload_group_member_file'); ?>/' + member.id + '/<?php echo isset($group) ? $group->id : ''; ?>/passport_scan');

    if (member.photo) {
        $('#member_photo_preview').html('<img src="<?php echo admin_url('travel_agency/view_group_member_file'); ?>/' + member.id + '/<?php echo isset($group) ? $group->id : ''; ?>/photo" class="tw-h-24 tw-w-24 tw-rounded-full tw-object-cover">');
    } else {
        $('#member_photo_preview').html('');
    }

    if (member.passport_scan) {
        var scanUrl = '<?php echo admin_url('travel_agency/view_group_member_file'); ?>/' + member.id + '/<?php echo isset($group) ? $group->id : ''; ?>/passport_scan';

        if (/\.(jpe?g|png)$/i.test(member.passport_scan)) {
            $('#member_passport_scan_preview').html(
                '<div class="tw-text-center"><a href="' + scanUrl + '" data-lightbox="member-passport-scan-' + member.id + '">' +
                '<img src="' + scanUrl + '" class="img-responsive" style="max-width:320px;max-height:320px;border:1px solid #e2e8f0;border-radius:6px;margin:0 auto;cursor:zoom-in;" alt=""></a></div>'
            );
        } else {
            // PDFs can't be shown as an <img> / lightbox - keep the plain link for those.
            $('#member_passport_scan_preview').html('<a href="' + scanUrl + '" target="_blank"><?php echo _l('travel_agency_group_member_view_passport_scan'); ?></a>');
        }
    } else {
        $('#member_passport_scan_preview').html('');
    }

    $('#edit_group_member_modal').modal('show');
}

/**
 * Run client-side MRZ OCR on a selected passport scan image and pre-fill the details tab's
 * fields with the result. Nothing here saves automatically - staff still review the Details
 * tab and click Submit themselves, same as if they'd typed the fields in by hand. This only
 * exists to save typing on the common case; it deliberately never overwrites the raw MRZ upload
 * itself, only the structured form fields.
 * @param {File} file
 */
function travel_agency_run_passport_ocr(file) {
    var statusEl = $('#passport_ocr_status');

    statusEl.show().removeClass('alert-danger alert-warning alert-success').addClass('alert alert-info')
        .html('<i class="fa-solid fa-spinner fa-spin tw-mr-1"></i> ' + '<?php echo _l('travel_agency_group_member_passport_ocr_scanning'); ?>');

    window.TravelAgencyPassportOcr.scanPassportFile(file).then(function (mrz) {
        if (!mrz) {
            statusEl.removeClass('alert-info').addClass('alert-warning')
                .html('<?php echo _l('travel_agency_group_member_passport_ocr_not_found'); ?>');

            return;
        }

        var detailsForm = $('#edit_group_member_details_form');

        if (mrz.surname) {
            detailsForm.find('input[name="passport_surname"]').val(mrz.surname);
        }
        if (mrz.givenNames) {
            detailsForm.find('input[name="passport_given_names"]').val(mrz.givenNames);
        }
        if (mrz.nationality) {
            detailsForm.find('select[name="nationality"]').val(mrz.nationality).selectpicker('refresh');
        }
        if (mrz.dateOfBirth) {
            detailsForm.find('input[name="date_of_birth"]').val(mrz.dateOfBirth);
        }
        if (mrz.sex) {
            detailsForm.find('select[name="gender"]').val(mrz.sex).selectpicker('refresh');
        }
        if (mrz.passportNumber) {
            detailsForm.find('input[name="passport_number"]').val(mrz.passportNumber);
        }
        if (mrz.passportExpiry) {
            detailsForm.find('input[name="passport_expiry"]').val(mrz.passportExpiry);
        }
        detailsForm.find('input[name="passport_mrz_raw"]').val(mrz.rawLine1 + '\n' + mrz.rawLine2);

        if (mrz.confidence === 'high') {
            statusEl.removeClass('alert-info').addClass('alert-success')
                .html('<i class="fa-solid fa-circle-check tw-mr-1"></i> ' + '<?php echo _l('travel_agency_group_member_passport_ocr_success'); ?>');
        } else {
            statusEl.removeClass('alert-info').addClass('alert-warning')
                .html('<i class="fa-solid fa-triangle-exclamation tw-mr-1"></i> ' + '<?php echo _l('travel_agency_group_member_passport_ocr_low_confidence'); ?>');
        }

        // Surface the auto-filled data on the tab staff actually need to check next.
        $('a[href="#member_details_tab"]').tab('show');
    }).catch(function () {
        statusEl.removeClass('alert-info').addClass('alert-danger')
            .html('<?php echo _l('travel_agency_group_member_passport_ocr_error'); ?>');
    });
}

function submit_group_member_edit_modal() {
    $('#edit_group_member_details_form').submit();
}
</script>
</body>

</html>
