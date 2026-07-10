<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (staff_can('create', 'travel_agency')) { ?>
                <div class="tw-mb-2">
                    <a href="#" onclick="new_travel_package_type(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('add_new', _l('travel_agency_package_type_lowercase')); ?>
                    </a>
                </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php if (count($types) > 0) { ?>
                        <table class="table dt-table" data-order-col="1" data-order-type="asc">
                            <thead>
                                <tr>
                                    <th><?php echo _l('travel_agency_package_type_name'); ?></th>
                                    <th><?php echo _l('travel_agency_package_type_display_order'); ?></th>
                                    <th><?php echo _l('status'); ?></th>
                                    <th class="options"><?php echo _l('options'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($types as $type) { ?>
                                <tr>
                                    <td>
                                        <a href="#" class="tw-font-medium"
                                            onclick="edit_travel_package_type(this, <?php echo e($type['id']); ?>); return false"
                                            data-name="<?php echo e($type['name']); ?>"
                                            data-display_order="<?php echo e($type['display_order']); ?>"
                                            data-active="<?php echo e($type['active']); ?>">
                                            <?php echo e($type['name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo e($type['display_order']); ?></td>
                                    <td>
                                        <?php echo $type['active'] == 1
                                        ? '<span class="label label-success">' . _l('travel_agency_active') . '</span>'
                                        : '<span class="label label-default">' . _l('travel_agency_inactive') . '</span>'; ?>
                                    </td>
                                    <td>
                                        <div class="tw-flex tw-items-center tw-space-x-2">
                                            <a href="#"
                                                onclick="edit_travel_package_type(this, <?php echo e($type['id']); ?>); return false"
                                                data-name="<?php echo e($type['name']); ?>"
                                                data-display_order="<?php echo e($type['display_order']); ?>"
                                                data-active="<?php echo e($type['active']); ?>"
                                                class="tw-text-neutral-500 hover:tw-text-neutral-700">
                                                <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                            </a>
                                            <?php if (staff_can('delete', 'travel_agency')) { ?>
                                            <a href="<?php echo admin_url('travel_agency/delete_package_type/' . $type['id']); ?>"
                                                class="tw-text-neutral-500 hover:tw-text-neutral-700 _delete">
                                                <i class="fa-regular fa-trash-can fa-lg"></i>
                                            </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } else { ?>
                        <p class="no-margin"><?php echo _l('travel_agency_package_types_not_found'); ?></p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="travel_package_type_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('travel_agency/package_type')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">
                    <span class="edit-title"><?php echo _l('edit', _l('travel_agency_package_type_lowercase')); ?></span>
                    <span class="add-title"><?php echo _l('add_new', _l('travel_agency_package_type_lowercase')); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div id="travel_package_type_additional"></div>
                <?php echo render_input('name', 'travel_agency_package_type_name'); ?>
                <?php echo render_input('display_order', 'travel_agency_package_type_display_order', 0, 'number'); ?>
                <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="active" id="travel_package_type_active" checked>
                    <label for="travel_package_type_active"><?php echo _l('travel_agency_active'); ?></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(function() {
    appValidateForm($('#travel_package_type_modal form'), {
        name: 'required',
    });

    $('#travel_package_type_modal').on('hidden.bs.modal', function() {
        $('#travel_package_type_additional').html('');
        $('#travel_package_type_modal input[name="name"]').val('');
        $('#travel_package_type_modal input[name="display_order"]').val(0);
        $('#travel_package_type_active').prop('checked', true);
        $('.add-title').removeClass('hide');
        $('.edit-title').removeClass('hide');
    });
});

function new_travel_package_type() {
    $('#travel_package_type_modal').modal('show');
    $('.edit-title').addClass('hide');
}

function edit_travel_package_type(invoker, id) {
    var name = $(invoker).data('name');
    var display_order = $(invoker).data('display_order');
    var active = $(invoker).data('active');

    $('#travel_package_type_additional').html('<input type="hidden" name="id" value="' + id + '">');
    $('#travel_package_type_modal input[name="name"]').val(name);
    $('#travel_package_type_modal input[name="display_order"]').val(display_order);
    $('#travel_package_type_active').prop('checked', active == 1);

    $('#travel_package_type_modal').modal('show');
    $('.add-title').addClass('hide');
}
</script>
</body>

</html>
