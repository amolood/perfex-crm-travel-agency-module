<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_clients extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('travel_agency/travel_bookings_model');
        $this->load->model('travel_agency/travel_packages_model');
        $this->load->model('travel_agency/travel_client_passports_model');
    }

    /* List all bookings belonging to the logged in customer */
    public function index()
    {
        $data['bookings'] = $this->travel_bookings_model->get_customer_bookings(get_client_user_id());
        $data['title']    = _l('travel_agency_my_bookings');

        $this->data($data);
        $this->view('client/bookings/list');
        $this->layout();
    }

    /* Browse packages open for self-service booking */
    public function packages()
    {
        $this->load->model('currencies_model');

        $packages = $this->travel_packages_model->get_bookable();

        foreach ($packages as $key => $package) {
            $packages[$key]['currency_row'] = $this->currencies_model->get($package['currency']);
        }

        $data['packages'] = $packages;
        $data['title']    = _l('travel_agency_available_packages');

        $this->data($data);
        $this->view('client/packages/list');
        $this->layout();
    }

    /* Show the application form for a package, and handle its submission */
    public function apply($package_id)
    {
        $package = $this->travel_packages_model->get($package_id);
        $package = $package ? (array) $package : null;

        if (!$package || $package['active'] != 1) {
            show_404();
        }

        if ($this->input->post()) {
            $travelers = (int) $this->input->post('travelers');
            $travelers = $travelers > 0 ? $travelers : 1;

            $travel_date = $package['start_date'] ? _d($package['start_date']) : $this->input->post('travel_date');

            $result = $this->travel_bookings_model->add([
                'package_id'  => $package_id,
                'clientid'    => get_client_user_id(),
                'contact_id'  => get_contact_user_id(),
                'travelers'   => $travelers,
                'travel_date' => $travel_date,
                'status'      => TRAVEL_BOOKING_STATUS_PENDING,
                'notes'       => $this->input->post('notes'),
            ]);

            if ($result === 'no_seats') {
                set_alert('danger', _l('travel_agency_booking_no_seats_available'));
                redirect(site_url('travel_agency/apply/' . $package_id));
            }

            if ($result) {
                set_alert('success', _l('travel_agency_application_submitted'));
                redirect(site_url('travel_agency/itinerary/' . $result));
            }

            set_alert('danger', _l('travel_agency_application_failed'));
        }

        $this->load->model('currencies_model');
        $data['package']       = $package;
        $data['currency_row']  = $this->currencies_model->get($package['currency']);
        $data['title']         = _l('travel_agency_apply_for_package');

        $this->data($data);
        $this->view('client/packages/apply');
        $this->layout();
    }

    /* View a single booking / itinerary, scoped to the logged in customer */
    public function itinerary($id)
    {
        $booking = $this->travel_bookings_model->get($id);

        if (!$booking || $booking->clientid != get_client_user_id()) {
            show_404();
        }

        if ($booking->invoiceid) {
            $this->load->model('invoices_model');
            $invoice                  = $this->invoices_model->get($booking->invoiceid);
            $data['invoice_hash']     = $invoice ? $invoice->hash : '';
            $data['invoice_total_left'] = $invoice ? get_invoice_total_left_to_pay($invoice->id, $invoice->total) : null;
        }

        $this->load->model('currencies_model');
        $booking_currency       = $this->currencies_model->get($booking->package_currency);
        $data['booking_currency'] = $booking_currency ? $booking_currency : get_base_currency();

        $this->load->model('travel_agency/travel_documents_model');
        $data['documents'] = $this->travel_documents_model->get_for('booking', $id);

        $data['booking'] = $booking;
        $data['title']   = _l('travel_agency_itinerary');

        $this->data($data);
        $this->view('client/itineraries/view');
        $this->layout();
    }

    /* Client requests staff to cancel their own booking - flags it, doesn't cancel outright */
    public function request_cancellation($id)
    {
        $response = $this->travel_bookings_model->request_cancellation($id, get_client_user_id(), $this->input->post('notes'));

        if ($response === true) {
            set_alert('success', _l('travel_agency_cancellation_request_submitted'));
        } elseif ($response === 'not_found') {
            show_404();
        } else {
            set_alert('danger', _l('travel_agency_cancellation_request_failed'));
        }

        redirect(site_url('travel_agency/itinerary/' . $id));
    }

    /* Client uploads their own document (visa/ticket/etc) to their own booking */
    public function upload_booking_document($id)
    {
        $booking = $this->travel_bookings_model->get($id);

        if (!$booking || $booking->clientid != get_client_user_id()) {
            show_404();
        }

        if (isset($_FILES['document']['name']) && $_FILES['document']['name'] != '') {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            $extension          = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));

            if (in_array($extension, $allowed_extensions)) {
                $tmpPath = $_FILES['document']['tmp_name'];
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

                $max_size_bytes = 8 * 1024 * 1024;

                if ($isValid && $_FILES['document']['size'] <= $max_size_bytes) {
                    travel_agency_secure_uploads_folder(TRAVEL_DOCUMENTS_UPLOADS_FOLDER);

                    $path = travel_agency_document_upload_path('booking', $id);
                    _maybe_create_upload_path($path);

                    $filename    = unique_filename($path, $_FILES['document']['name']);
                    $newFilePath = $path . $filename;

                    if (move_uploaded_file($tmpPath, $newFilePath)) {
                        $this->load->model('travel_agency/travel_documents_model');
                        $this->travel_documents_model->add([
                            'rel_type'      => 'booking',
                            'rel_id'        => $id,
                            'document_type' => $this->input->post('document_type'),
                            'original_name' => $_FILES['document']['name'],
                            'filename'      => $filename,
                            'notes'         => $this->input->post('notes'),
                        ]);
                        set_alert('success', _l('travel_agency_document_uploaded'));
                    }
                } else {
                    set_alert('warning', _l('file_php_extension_blocked'));
                }
            } else {
                set_alert('warning', _l('file_php_extension_blocked'));
            }
        }

        redirect(site_url('travel_agency/itinerary/' . $id));
    }

    /* Serve a document attached to the logged-in client's own booking - never another client's */
    public function booking_document($doc_id)
    {
        $this->load->model('travel_agency/travel_documents_model');
        $document = $this->travel_documents_model->get($doc_id);

        if (!$document || $document->rel_type !== 'booking') {
            show_404();
        }

        $booking = $this->travel_bookings_model->get($document->rel_id);

        if (!$booking || $booking->clientid != get_client_user_id()) {
            show_404();
        }

        $path = travel_agency_document_upload_path('booking', $document->rel_id) . $document->filename;

        if (!file_exists($path)) {
            show_404();
        }

        force_download($path, null);
    }

    /* View own passport history and upload a new one, scoped strictly to the logged in client */
    public function passport()
    {
        $clientid = get_client_user_id();

        if ($this->input->post()) {
            $insert_id = $this->travel_client_passports_model->add($clientid, $this->input->post());

            if ($insert_id === 'invalid_passport_number') {
                set_alert('danger', _l('travel_agency_client_passport_number_required'));
            } elseif ($insert_id) {
                if (!empty($_FILES['passport_scan']['name'])) {
                    $this->_handle_passport_scan_upload($insert_id, $clientid);
                }

                set_alert('success', _l('travel_agency_client_passport_updated'));
            } else {
                set_alert('danger', _l('travel_agency_client_passport_update_failed'));
            }

            redirect(site_url('travel_agency/passport'));
        }

        $data['current'] = $this->travel_client_passports_model->get_current($clientid);
        $data['history'] = $this->travel_client_passports_model->get_history($clientid);
        $data['title']   = _l('travel_agency_my_passport');

        $this->data($data);
        $this->view('client/passport/view');
        $this->layout();
    }

    /* Serve the logged-in client's own passport scan file - never another client's */
    public function passport_file($passport_id)
    {
        $passport = $this->travel_client_passports_model->get($passport_id);

        if (!$passport || $passport->clientid != get_client_user_id() || $passport->scan_file == '') {
            show_404();
        }

        $path = travel_agency_client_passport_upload_path($passport->clientid) . $passport->scan_file;

        if (!file_exists($path)) {
            show_404();
        }

        // Served inline so it can be embedded directly as an <img> on the "My Passport" page.
        travel_agency_serve_file_inline($path);
    }

    /* Delete one of the logged-in client's own passport records - never another client's */
    public function delete_passport($passport_id)
    {
        $clientid = get_client_user_id();
        $passport = $this->travel_client_passports_model->get($passport_id);

        if (!$passport || $passport->clientid != $clientid) {
            show_404();
        }

        $response = $this->travel_client_passports_model->delete($passport_id, $clientid);

        if ($response == true) {
            set_alert('success', _l('travel_agency_client_passport_deleted'));
        } else {
            set_alert('danger', _l('travel_agency_client_passport_delete_failed'));
        }

        redirect(site_url('travel_agency/passport'));
    }

    /**
     * Shared upload handler for a client's own passport scan - validates actual file content
     * (not just the claimed extension), same as the admin-side handler.
     * @param  mixed $passport_id
     * @param  mixed $clientid
     * @return void
     */
    private function _handle_passport_scan_upload($passport_id, $clientid)
    {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $extension          = strtolower(pathinfo($_FILES['passport_scan']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed_extensions)) {
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
            return;
        }

        $max_size_bytes = 8 * 1024 * 1024;

        if ($_FILES['passport_scan']['size'] > $max_size_bytes) {
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
}
