<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_agency extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('travel_agency/travel_packages_model');
        $this->load->model('travel_agency/travel_bookings_model');
        $this->load->model('travel_agency/travel_suppliers_model');
        $this->load->model('travel_agency/travel_groups_model');
        $this->load->model('travel_agency/travel_package_types_model');
        $this->load->model('travel_agency/travel_supplier_payments_model');
        $this->load->model('travel_agency/travel_client_passports_model');
        $this->load->model('currencies_model');
    }

    /* Redirect base module url to packages */
    public function index()
    {
        redirect(admin_url('travel_agency/packages'));
    }

    /* ---------------------------------------------------------------- */
    /* Packages                                                          */
    /* ---------------------------------------------------------------- */

    public function packages()
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('travel_agency', 'admin/packages/table'));
        }

        $data['title'] = _l('travel_agency_packages');
        $this->load->view('admin/packages/manage', $data);
    }

    public function package($id = '')
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'travel_agency')) {
                    access_denied('travel_agency');
                }
                $id = $this->travel_packages_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('travel_agency_package')));
                    redirect(admin_url('travel_agency/package/' . $id));
                }
            } else {
                if (staff_cant('edit', 'travel_agency')) {
                    access_denied('travel_agency');
                }
                $success = $this->travel_packages_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('travel_agency_package')));
                }
                redirect(admin_url('travel_agency/package/' . $id));
            }
        }

        if ($id == '') {
            $title = _l('add_new', _l('travel_agency_package_lowercase'));
        } else {
            $data['package'] = $this->travel_packages_model->get($id);

            if (!$data['package']) {
                show_404();
            }

            $title = _l('edit', _l('travel_agency_package_lowercase'));
        }

        $data['suppliers']  = $this->travel_suppliers_model->get();
        $data['types']      = $this->travel_package_types_model->get_active();
        $data['currencies'] = $this->currencies_model->get();
        $data['title']      = $title;
        $this->load->view('admin/packages/package', $data);
    }

    public function delete_package($id)
    {
        if (staff_cant('delete', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if (!$id) {
            redirect(admin_url('travel_agency/packages'));
        }

        $response = $this->travel_packages_model->delete($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('travel_agency_package')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('travel_agency_package_lowercase')));
        }

        redirect(admin_url('travel_agency/packages'));
    }

    /* ---------------------------------------------------------------- */
    /* Package Types (نوع الرحلة: سياحة، حج، عمرة، ...)                  */
    /* ---------------------------------------------------------------- */

    public function package_types()
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        $data['types'] = $this->travel_package_types_model->get();
        $data['title'] = _l('travel_agency_package_types');
        $this->load->view('admin/package_types/manage', $data);
    }

    /* Add or update a package type - id is passed as a hidden POST field when editing */
    public function package_type()
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if ($this->input->post()) {
            $id = $this->input->post('id');

            if ($id == '') {
                if (staff_cant('create', 'travel_agency')) {
                    access_denied('travel_agency');
                }
                $inserted = $this->travel_package_types_model->add($this->input->post());
                if ($inserted) {
                    set_alert('success', _l('added_successfully', _l('travel_agency_package_type')));
                }
            } else {
                if (staff_cant('edit', 'travel_agency')) {
                    access_denied('travel_agency');
                }
                $success = $this->travel_package_types_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('travel_agency_package_type')));
                }
            }
        }

        redirect(admin_url('travel_agency/package_types'));
    }

    public function delete_package_type($id)
    {
        if (staff_cant('delete', 'travel_agency')) {
            access_denied('travel_agency');
        }

        $response = $this->travel_package_types_model->delete($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('travel_agency_package_type')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('travel_agency_package_type_lowercase')));
        }

        redirect(admin_url('travel_agency/package_types'));
    }

    /* ---------------------------------------------------------------- */
    /* Bookings                                                          */
    /* ---------------------------------------------------------------- */

    public function bookings()
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('travel_agency', 'admin/bookings/table'));
        }

        $data['title'] = _l('travel_agency_bookings');
        $this->load->view('admin/bookings/manage', $data);
    }

    public function booking($id = '')
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'travel_agency')) {
                    access_denied('travel_agency');
                }

                $post_data = $this->input->post();

                if ($this->input->post('client_source') === 'new') {
                    if (staff_cant('create', 'customers')) {
                        access_denied('customers');
                    }

                    $this->load->model('clients_model');
                    $client_id = $this->clients_model->add([
                        'company'     => $this->input->post('new_client_company'),
                        'phonenumber' => $this->input->post('new_client_phonenumber'),
                        'firstname'   => $this->input->post('new_client_firstname'),
                        'lastname'    => $this->input->post('new_client_lastname'),
                        'email'       => $this->input->post('new_client_email'),
                    ], true);

                    if (!$client_id) {
                        set_alert('danger', _l('travel_agency_booking_problem_adding_client'));
                        redirect(admin_url('travel_agency/booking'));
                    }

                    $post_data['clientid'] = $client_id;
                }

                unset($post_data['client_source'], $post_data['new_client_company'], $post_data['new_client_phonenumber'], $post_data['new_client_firstname'], $post_data['new_client_lastname'], $post_data['new_client_email']);

                $result = $this->travel_bookings_model->add($post_data);

                if ($result === 'no_seats') {
                    set_alert('danger', _l('travel_agency_booking_no_seats_available'));
                    redirect(admin_url('travel_agency/booking'));
                }

                if ($result) {
                    set_alert('success', _l('added_successfully', _l('travel_agency_booking')));
                    redirect(admin_url('travel_agency/booking/' . $result));
                }
            } else {
                if (staff_cant('edit', 'travel_agency')) {
                    access_denied('travel_agency');
                }
                $success = $this->travel_bookings_model->update($this->input->post(), $id);

                if ($success === 'no_seats') {
                    set_alert('danger', _l('travel_agency_booking_no_seats_available'));
                    redirect(admin_url('travel_agency/booking/' . $id));
                }

                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('travel_agency_booking')));
                }
                redirect(admin_url('travel_agency/booking/' . $id));
            }
        }

        if ($id == '') {
            $title = _l('add_new', _l('travel_agency_booking_lowercase'));
        } else {
            $data['booking'] = $this->travel_bookings_model->get($id);

            if (!$data['booking']) {
                show_404();
            }

            $title = _l('edit', _l('travel_agency_booking_lowercase'));
        }

        $this->load->model('clients_model');
        $data['clients']  = $this->clients_model->get();
        $data['packages'] = $this->travel_packages_model->get_active();

        $data['statuses'] = [];
        foreach (get_travel_booking_statuses() as $status_id => $status_name) {
            $data['statuses'][] = ['id' => $status_id, 'name' => $status_name];
        }

        $data['title'] = $title;
        $this->load->view('admin/bookings/booking', $data);
    }

    public function update_booking_status($id, $status)
    {
        if (staff_cant('edit', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if (!$id) {
            redirect(admin_url('travel_agency/bookings'));
        }

        $success = $this->travel_bookings_model->update_status($id, $status);

        if ($success === 'no_seats') {
            set_alert('danger', _l('travel_agency_booking_no_seats_available'));
        } elseif ($success) {
            set_alert('success', _l('updated_successfully', _l('travel_agency_booking')));
        }

        redirect(admin_url('travel_agency/booking/' . $id));
    }

    public function delete_booking($id)
    {
        if (staff_cant('delete', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if (!$id) {
            redirect(admin_url('travel_agency/bookings'));
        }

        $response = $this->travel_bookings_model->delete($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('travel_agency_booking')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('travel_agency_booking_lowercase')));
        }

        redirect(admin_url('travel_agency/bookings'));
    }

    /* ---------------------------------------------------------------- */
    /* Groups (التفويج)                                                  */
    /* ---------------------------------------------------------------- */

    public function groups()
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('travel_agency', 'admin/groups/table'));
        }

        $data['title'] = _l('travel_agency_groups');
        $this->load->view('admin/groups/manage', $data);
    }

    public function group($id = '')
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if ($this->input->post()) {
            // stops/transport are separate child-table rows (travel_group_itinerary_stops /
            // travel_group_transport), submitted as nested arrays alongside the group's own
            // fields on the same form - they must never reach travel_groups_model->add()/
            // update(), which inserts/updates the travel_groups row directly. Passing them
            // through un-stripped fatals with "Unknown column 'stops' in 'SET'" the moment the
            // form actually has itinerary/transport rows to submit (previously silent only
            // because those arrays were empty/absent on essentially every real submission so
            // far).
            $group_data = $this->input->post();
            unset($group_data['stops'], $group_data['transport']);

            if ($id == '') {
                if (staff_cant('create', 'travel_agency')) {
                    access_denied('travel_agency');
                }
                $id = $this->travel_groups_model->add($group_data);
                if ($id) {
                    $this->travel_groups_model->save_itinerary_stops($id, $this->input->post('stops') ?: []);
                    $this->travel_groups_model->save_transport($id, $this->input->post('transport') ?: []);
                    set_alert('success', _l('added_successfully', _l('travel_agency_group')));
                    redirect(admin_url('travel_agency/group/' . $id));
                }
            } else {
                if (staff_cant('edit', 'travel_agency')) {
                    access_denied('travel_agency');
                }
                $success = $this->travel_groups_model->update($group_data, $id);
                $this->travel_groups_model->save_itinerary_stops($id, $this->input->post('stops') ?: []);
                $this->travel_groups_model->save_transport($id, $this->input->post('transport') ?: []);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('travel_agency_group')));
                }
                redirect(admin_url('travel_agency/group/' . $id));
            }
        }

        if ($id == '') {
            $title            = _l('add_new', _l('travel_agency_group_lowercase'));
            $data['stops']    = [];
            $data['transport'] = [];
        } else {
            $data['group'] = $this->travel_groups_model->get($id);

            if (!$data['group']) {
                show_404();
            }

            $data['members']   = $this->travel_groups_model->get_members($id);
            $data['stops']     = $this->travel_groups_model->get_itinerary_stops($id);
            $data['transport'] = $this->travel_groups_model->get_transport($id);

            $title = _l('edit', _l('travel_agency_group_lowercase'));
        }

        $data['packages'] = $this->travel_packages_model->get_active();

        $data['statuses'] = [];
        foreach (get_travel_group_statuses() as $status_id => $status_name) {
            $data['statuses'][] = ['id' => $status_id, 'name' => $status_name];
        }

        $data['visa_statuses'] = [];
        foreach (get_travel_visa_statuses() as $status_id => $status_name) {
            $data['visa_statuses'][] = ['id' => $status_id, 'name' => $status_name];
        }

        $data['title'] = $title;
        $this->load->view('admin/groups/group', $data);
    }

    public function delete_group($id)
    {
        if (staff_cant('delete', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if (!$id) {
            redirect(admin_url('travel_agency/groups'));
        }

        $response = $this->travel_groups_model->delete($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('travel_agency_group')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('travel_agency_group_lowercase')));
        }

        redirect(admin_url('travel_agency/groups'));
    }

    /* Add an existing booking's traveler(s) to a group roster */
    public function add_group_member($group_id)
    {
        if (staff_cant('edit', 'travel_agency')) {
            access_denied('travel_agency');
        }

        $group = $this->travel_groups_model->get($group_id);

        if (!$group) {
            show_404();
        }

        if ($this->input->post()) {
            $result = $this->travel_groups_model->add_member($group_id, $this->input->post());

            if ($result === 'no_seats') {
                set_alert('danger', _l('travel_agency_group_no_seats_available'));
            } elseif ($result === 'invalid_booking') {
                set_alert('danger', _l('travel_agency_group_invalid_booking'));
            } elseif ($result === 'group_not_active') {
                set_alert('danger', _l('travel_agency_group_not_active'));
            } elseif ($result) {
                set_alert('success', _l('added_successfully', _l('travel_agency_group_member')));
            }
        }

        redirect(admin_url('travel_agency/group/' . $group_id));
    }

    /* Get bookings eligible to be added to a group, for the "add traveler" dropdown */
    public function get_eligible_group_bookings($group_id)
    {
        if (staff_cant('view', 'travel_agency')) {
            ajax_access_denied();
        }

        $group = $this->travel_groups_model->get($group_id);

        if (!$group) {
            show_404();
        }

        $bookings = $this->travel_groups_model->get_eligible_bookings($group_id, $group->package_id);

        // Passport number/expiry/name are customer PII, not something implied by "can view
        // travel bookings" - staff without the customers view permission still need the
        // booking list itself (to pick who to add), just not their passport data riding along
        // in the same response.
        if (staff_cant('view', 'customers')) {
            foreach ($bookings as &$booking) {
                unset(
                    $booking['passport_number'],
                    $booking['passport_expiry'],
                    $booking['passport_surname'],
                    $booking['passport_given_names'],
                    $booking['passport_nationality'],
                    $booking['passport_date_of_birth'],
                    $booking['passport_gender']
                );
            }
            unset($booking);
        }

        echo json_encode($bookings);
    }

    public function update_group_member($id, $group_id)
    {
        if (staff_cant('edit', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if ($this->input->post()) {
            $success = $this->travel_groups_model->update_member($this->input->post(), $id, $group_id);

            if ($success) {
                set_alert('success', _l('updated_successfully', _l('travel_agency_group_member')));
            } else {
                set_alert('warning', _l('problem_updating', _l('travel_agency_group_member_lowercase')));
            }
        }

        redirect(admin_url('travel_agency/group/' . $group_id));
    }

    /* Re-copy passport fields onto an existing group member from the client's CURRENT passport */
    public function refresh_group_member_passport($id, $group_id)
    {
        if (staff_cant('edit', 'travel_agency')) {
            access_denied('travel_agency');
        }

        $result = $this->travel_groups_model->refresh_member_from_client_passport($id, $group_id);

        if ($result === true) {
            set_alert('success', _l('travel_agency_group_member_passport_refreshed'));
        } elseif ($result === 'no_client_passport') {
            set_alert('warning', _l('travel_agency_group_member_passport_refresh_no_passport'));
        } else {
            set_alert('warning', _l('problem_updating', _l('travel_agency_group_member_lowercase')));
        }

        redirect(admin_url('travel_agency/group/' . $group_id));
    }

    public function update_group_member_visa_status($id, $group_id, $status)
    {
        if (staff_cant('edit', 'travel_agency')) {
            access_denied('travel_agency');
        }

        $success = $this->travel_groups_model->update_member_visa_status($id, $group_id, $status);

        if ($success) {
            set_alert('success', _l('updated_successfully', _l('travel_agency_group_member')));
        }

        redirect(admin_url('travel_agency/group/' . $group_id));
    }

    public function remove_group_member($id, $group_id)
    {
        if (staff_cant('edit', 'travel_agency')) {
            access_denied('travel_agency');
        }

        $response = $this->travel_groups_model->remove_member($id, $group_id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('travel_agency_group_member')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('travel_agency_group_member_lowercase')));
        }

        redirect(admin_url('travel_agency/group/' . $group_id));
    }

    /* Upload the traveler's personal photo or passport scan */
    public function upload_group_member_file($id, $group_id, $field)
    {
        if (staff_cant('edit', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if (!in_array($field, ['photo', 'passport_scan'])) {
            show_404();
        }

        // A member id only belongs to this group if travel_groups_model confirms it - checked
        // up front so upload_group_member_file can't be used to write a file into any member's
        // folder just by supplying an unrelated group_id in the URL.
        $member = $this->travel_groups_model->get_member($id);

        if (!$member || (int) $member->group_id !== (int) $group_id) {
            show_404();
        }

        if (isset($_FILES[$field]['name']) && $_FILES[$field]['name'] != '') {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            $extension          = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $allowed_extensions)) {
                set_alert('warning', _l('file_php_extension_blocked'));
                redirect(admin_url('travel_agency/group/' . $group_id));
            }

            // The extension is just the claimed filename - verify the actual bytes match one of
            // the allowed formats before trusting it, so a renamed non-image/non-PDF file (e.g.
            // a script renamed to .jpg) can't be stored and later served back out.
            $tmpPath = $_FILES[$field]['tmp_name'];
            $isValid = false;

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $imageInfo = @getimagesize($tmpPath);
                $isValid   = $imageInfo !== false && in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG]);
            } elseif ($extension === 'pdf') {
                $handle  = @fopen($tmpPath, 'rb');
                $header  = $handle ? fread($handle, 5) : '';
                if ($handle) {
                    fclose($handle);
                }
                $isValid = $header === '%PDF-';
            }

            if (!$isValid) {
                set_alert('warning', _l('file_php_extension_blocked'));
                redirect(admin_url('travel_agency/group/' . $group_id));
            }

            $max_size_bytes = 8 * 1024 * 1024;

            if ($_FILES[$field]['size'] > $max_size_bytes) {
                set_alert('warning', _l('file_too_big'));
                redirect(admin_url('travel_agency/group/' . $group_id));
            }

            // Self-heals installs where the parent folder was created before this protection
            // existed - not just relying on the activation hook having run.
            travel_agency_secure_uploads_folder(TRAVEL_GROUP_MEMBERS_UPLOADS_FOLDER);

            $path = travel_agency_group_member_upload_path($id);
            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES[$field]['name']);
            $newFilePath = $path . $filename;

            if (move_uploaded_file($tmpPath, $newFilePath)) {
                $this->travel_groups_model->update_member_file($id, $group_id, $field, $filename);
                set_alert('success', _l('updated_successfully', _l('travel_agency_group_member')));
            }
        }

        redirect(admin_url('travel_agency/group/' . $group_id));
    }

    /* Serve the traveler's personal photo or passport scan, gated by staff permission */
    public function view_group_member_file($id, $group_id, $field)
    {
        if (staff_cant('view', 'travel_agency')) {
            access_denied('travel_agency');
        }

        if (!in_array($field, ['photo', 'passport_scan'])) {
            show_404();
        }

        $member = $this->travel_groups_model->get_member($id);

        if (!$member || (int) $member->group_id !== (int) $group_id || $member->{$field} == '') {
            show_404();
        }

        $path = travel_agency_group_member_upload_path($id) . $member->{$field};

        if (!file_exists($path)) {
            show_404();
        }

        // Served inline so it can be embedded directly (photo thumbnail, or a lightbox-viewable
        // passport scan) instead of always forcing a download.
        travel_agency_serve_file_inline($path);
    }

    /* ---------------------------------------------------------------- */
    /* Suppliers                                                         */
    /* ---------------------------------------------------------------- */

    public function suppliers()
    {
        if (staff_cant('view', 'travel_agency_suppliers')) {
            access_denied('travel_agency_suppliers');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('travel_agency', 'admin/suppliers/table'));
        }

        $data['title'] = _l('travel_agency_suppliers');
        $this->load->view('admin/suppliers/manage', $data);
    }

    public function supplier_accounts()
    {
        if (staff_cant('view', 'travel_agency_suppliers')) {
            access_denied('travel_agency_suppliers');
        }

        $data['suppliers']        = $this->travel_suppliers_model->get();
        $data['account_summaries'] = $this->travel_supplier_payments_model->get_all_account_summaries();
        $data['title']            = _l('travel_agency_supplier_accounts');
        $this->load->view('admin/suppliers/accounts', $data);
    }

    public function supplier($id = '')
    {
        if (staff_cant('view', 'travel_agency_suppliers')) {
            access_denied('travel_agency_suppliers');
        }

        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'travel_agency_suppliers')) {
                    access_denied('travel_agency_suppliers');
                }
                $new_id = $this->travel_suppliers_model->add($this->input->post());

                if ($new_id === 'invalid_data') {
                    set_alert('danger', _l('travel_agency_supplier_required_fields'));
                    redirect(admin_url('travel_agency/supplier'));
                }

                if ($new_id) {
                    set_alert('success', _l('added_successfully', _l('travel_agency_supplier')));
                    redirect(admin_url('travel_agency/supplier/' . $new_id));
                }
            } else {
                if (staff_cant('edit', 'travel_agency_suppliers')) {
                    access_denied('travel_agency_suppliers');
                }
                $success = $this->travel_suppliers_model->update($this->input->post(), $id);

                if ($success === 'invalid_data') {
                    set_alert('danger', _l('travel_agency_supplier_required_fields'));
                } elseif ($success) {
                    set_alert('success', _l('updated_successfully', _l('travel_agency_supplier')));
                }
                redirect(admin_url('travel_agency/supplier/' . $id));
            }
        }

        if ($id == '') {
            $title = _l('add_new', _l('travel_agency_supplier_lowercase'));
        } else {
            $data['supplier'] = $this->travel_suppliers_model->get($id);

            if (!$data['supplier']) {
                show_404();
            }

            $data['account_summary'] = $this->travel_supplier_payments_model->get_account_summary($id);
            $data['payments']        = $this->travel_supplier_payments_model->get_for_supplier($id);
            $data['currencies']      = $this->currencies_model->get();

            $title = _l('edit', _l('travel_agency_supplier_lowercase'));
        }

        $data['title'] = $title;
        $this->load->view('admin/suppliers/supplier', $data);
    }

    public function delete_supplier($id)
    {
        if (staff_cant('delete', 'travel_agency_suppliers')) {
            access_denied('travel_agency_suppliers');
        }

        if (!$id) {
            redirect(admin_url('travel_agency/suppliers'));
        }

        $response = $this->travel_suppliers_model->delete($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('travel_agency_supplier')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('travel_agency_supplier_lowercase')));
        }

        redirect(admin_url('travel_agency/suppliers'));
    }

    /* ---------------------------------------------------------------- */
    /* Supplier Payments (سدادات المورد)                                 */
    /* ---------------------------------------------------------------- */

    public function supplier_payment($supplier_id)
    {
        if (staff_cant('edit', 'travel_agency_suppliers')) {
            access_denied('travel_agency_suppliers');
        }

        if (!$this->travel_suppliers_model->get($supplier_id)) {
            show_404();
        }

        if ($this->input->post()) {
            $post_data                = $this->input->post();
            $post_data['supplier_id'] = $supplier_id;

            $id = $this->travel_supplier_payments_model->add($post_data);

            if ($id === 'invalid_amount') {
                set_alert('danger', _l('travel_agency_supplier_payment_invalid_amount'));
            } elseif ($id) {
                set_alert('success', _l('added_successfully', _l('travel_agency_supplier_payment')));
            }
        }

        redirect(admin_url('travel_agency/supplier/' . $supplier_id));
    }

    public function delete_supplier_payment($id)
    {
        if (staff_cant('edit', 'travel_agency_suppliers')) {
            access_denied('travel_agency_suppliers');
        }

        $payment = $this->travel_supplier_payments_model->get($id);

        if (!$payment) {
            show_404();
        }

        $response = $this->travel_supplier_payments_model->delete($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('travel_agency_supplier_payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('travel_agency_supplier_payment_lowercase')));
        }

        redirect(admin_url('travel_agency/supplier/' . $payment->supplier_id));
    }

    /* List clients, for staff to pick one and manage their passport records */
    public function client_passports()
    {
        if (staff_cant('view', 'customers')) {
            access_denied('customers');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('travel_agency', 'admin/client_passports/table'));

            return;
        }

        $data['title'] = _l('travel_agency_client_passports');
        $this->load->view('admin/client_passports/manage', $data);
    }

    /* View a client's current + historical passports, and upload a new one */
    public function client_passport($clientid)
    {
        if (staff_cant('view', 'customers')) {
            access_denied('customers');
        }

        $this->load->model('clients_model');
        $client = $this->clients_model->get($clientid);

        if (!$client) {
            show_404();
        }

        if ($this->input->post()) {
            if (staff_cant('edit', 'customers')) {
                access_denied('customers');
            }

            $insert_id = $this->travel_client_passports_model->add($clientid, $this->input->post());

            if ($insert_id === 'invalid_passport_number') {
                set_alert('danger', _l('travel_agency_client_passport_number_required'));
            } elseif ($insert_id) {
                if (!empty($_FILES['passport_scan']['name'])) {
                    $this->_handle_passport_scan_upload($insert_id, $clientid);
                }

                set_alert('success', _l('added_successfully', _l('travel_agency_client_passport')));
            } else {
                set_alert('danger', _l('problem_adding', _l('travel_agency_client_passport_lowercase')));
            }

            redirect(admin_url('travel_agency/client_passport/' . $clientid));
        }

        $data['client']     = $client;
        $data['current']    = $this->travel_client_passports_model->get_current($clientid);
        $data['history']    = $this->travel_client_passports_model->get_history($clientid);
        $data['title']      = _l('travel_agency_client_passport') . ' - ' . $client->company;
        $this->load->view('admin/client_passports/passport', $data);
    }

    /**
     * Shared upload handler for a client passport scan - validates actual file content (not
     * just the claimed extension), same as upload_group_member_file(), then records the
     * filename against the given passport row.
     * @param  mixed $passport_id
     * @param  mixed $clientid
     * @return void
     */
    private function _handle_passport_scan_upload($passport_id, $clientid)
    {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $extension          = strtolower(pathinfo($_FILES['passport_scan']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed_extensions)) {
            set_alert('warning', _l('file_php_extension_blocked'));

            return;
        }

        $tmpPath = $_FILES['passport_scan']['tmp_name'];
        $isValid = false;

        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $imageInfo = @getimagesize($tmpPath);
            $isValid   = $imageInfo !== false && in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG]);
        } elseif ($extension === 'pdf') {
            $handle = @fopen($tmpPath, 'rb');
            $header = $handle ? fread($handle, 5) : '';
            if ($handle) {
                fclose($handle);
            }
            $isValid = $header === '%PDF-';
        }

        if (!$isValid) {
            set_alert('warning', _l('file_php_extension_blocked'));

            return;
        }

        $max_size_bytes = 8 * 1024 * 1024;

        if ($_FILES['passport_scan']['size'] > $max_size_bytes) {
            set_alert('warning', _l('file_too_big'));

            return;
        }

        travel_agency_secure_uploads_folder(TRAVEL_CLIENT_PASSPORTS_UPLOADS_FOLDER);

        $path = travel_agency_client_passport_upload_path($clientid);
        _maybe_create_upload_path($path);

        $filename    = unique_filename($path, $_FILES['passport_scan']['name']);
        $newFilePath = $path . $filename;

        if (move_uploaded_file($tmpPath, $newFilePath)) {
            $this->travel_client_passports_model->update_scan_file($passport_id, $clientid, $filename);
        }
    }

    /* Serve a client passport scan file, gated by staff permission */
    public function view_client_passport_file($passport_id)
    {
        if (staff_cant('view', 'customers')) {
            access_denied('customers');
        }

        $passport = $this->travel_client_passports_model->get($passport_id);

        if (!$passport || $passport->scan_file == '') {
            show_404();
        }

        $path = travel_agency_client_passport_upload_path($passport->clientid) . $passport->scan_file;

        if (!file_exists($path)) {
            show_404();
        }

        // Served inline (not as a forced download) so it can be embedded directly as an <img>
        // in the admin passport screen - core's force_download() always sends
        // Content-Disposition: attachment, which would make an <img> tag fail to render and
        // instead prompt a download on every page load.
        travel_agency_serve_file_inline($path);
    }

    /* Delete a client's passport record (and its scan file, if any) */
    public function delete_client_passport($passport_id)
    {
        if (staff_cant('edit', 'customers')) {
            access_denied('customers');
        }

        $passport = $this->travel_client_passports_model->get($passport_id);

        if (!$passport) {
            show_404();
        }

        $clientid = $passport->clientid;
        $response = $this->travel_client_passports_model->delete($passport_id, $clientid);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('travel_agency_client_passport')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('travel_agency_client_passport_lowercase')));
        }

        redirect(admin_url('travel_agency/client_passport/' . $clientid));
    }
}
