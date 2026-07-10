<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Travel_clients extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('travel_agency/travel_bookings_model');
        $this->load->model('travel_agency/travel_packages_model');
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
            $invoice               = $this->invoices_model->get($booking->invoiceid);
            $data['invoice_hash']  = $invoice ? $invoice->hash : '';
        }

        $this->load->model('currencies_model');
        $booking_currency       = $this->currencies_model->get($booking->package_currency);
        $data['booking_currency'] = $booking_currency ? $booking_currency : get_base_currency();

        $data['booking'] = $booking;
        $data['title']   = _l('travel_agency_itinerary');

        $this->data($data);
        $this->view('client/itineraries/view');
        $this->layout();
    }
}
