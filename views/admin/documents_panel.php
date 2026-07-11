<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s" id="travel-documents-panel">
    <div class="panel-body">
        <h4 class="tw-mt-0 tw-font-bold tw-text-base tw-text-neutral-700"><?php echo _l('travel_agency_documents'); ?></h4>

        <?php if (staff_can('edit', 'travel_agency')) { ?>
        <?php echo form_open_multipart(admin_url('travel_agency/upload_document/' . $document_rel_type . '/' . $document_rel_id)); ?>
        <div class="row">
            <div class="col-md-3">
                <?php
                $document_type_options = [
                    ['id' => 'visa', 'name' => _l('travel_agency_document_type_visa')],
                    ['id' => 'ticket', 'name' => _l('travel_agency_document_type_ticket')],
                    ['id' => 'voucher', 'name' => _l('travel_agency_document_type_voucher')],
                    ['id' => 'contract', 'name' => _l('travel_agency_document_type_contract')],
                    ['id' => 'other', 'name' => _l('travel_agency_document_type_other')],
                ];
                echo render_select('document_type', $document_type_options, ['id', 'name'], 'travel_agency_document_type', 'other', [], [], '', '', false);
                ?>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo _l('travel_agency_document_file'); ?></label>
                    <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required>
                    <p class="help-block tw-mb-0"><?php echo _l('travel_agency_document_size_hint'); ?></p>
                </div>
            </div>
            <div class="col-md-5">
                <?php echo render_input('notes', 'travel_agency_document_notes'); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo _l('travel_agency_document_upload'); ?></button>
        <?php echo form_close(); ?>
        <hr>
        <?php } ?>

        <?php if (empty($documents)) { ?>
        <p class="text-muted"><?php echo _l('travel_agency_document_none'); ?></p>
        <?php } else { ?>
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo _l('travel_agency_document_type'); ?></th>
                    <th><?php echo _l('travel_agency_document_file'); ?></th>
                    <th><?php echo _l('travel_agency_document_notes'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc) { ?>
                <tr>
                    <td><?php echo e(_l('travel_agency_document_type_' . $doc['document_type'])); ?></td>
                    <td><a href="<?php echo admin_url('travel_agency/view_document/' . $doc['id']); ?>" target="_blank"><?php echo e($doc['original_name']); ?></a></td>
                    <td><?php echo e($doc['notes']); ?></td>
                    <td class="text-right">
                        <?php if (staff_can('edit', 'travel_agency')) { ?>
                        <a href="<?php echo admin_url('travel_agency/delete_document/' . $doc['id']); ?>" class="text-danger _delete"><?php echo _l('delete'); ?></a>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } ?>
    </div>
</div>
