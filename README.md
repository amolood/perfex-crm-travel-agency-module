# Travel Agency Module for Perfex CRM

A complete travel-agency workflow for [Perfex CRM](https://www.perfexcrm.com/): tour packages, customer bookings (with automatic invoicing), group trips with multi-city itineraries and traveler/passport tracking, suppliers, and per-supplier accounts/payments — plus a client-portal experience for browsing packages and viewing bookings.

**Requires:** Perfex CRM 2.3.* or later.

For a deep, feature-by-feature walkthrough in Arabic, see [FEATURES.ar.md](FEATURES.ar.md).

---

## Features

- **Package types** — trip category taxonomy (tourism, Hajj, Umrah, business, family, personal visits...), seeded with sensible defaults on install.
- **Packages** — destination, supplier, trip type, duration, dates, seat capacity, currency, sell price vs. supplier cost, with automatic per-seat profit/margin calculation.
- **Bookings** — pick an existing client or quick-add a new one inline, automatic total calculation (price × travelers), optional one-click invoice generation tied to the booking, and status tracking (pending/confirmed/cancelled/completed).
- **Groups (multi-traveler trips)** — multi-city itinerary stops, multiple transport legs, and a full traveler roster per group with passport number/expiry, visa status, MRZ-derived fields, and uploaded photo/passport-scan attachments.
- **Suppliers** — simple supplier directory (hotels, carriers, ticket agents...).
- **Supplier accounts & payments** — a dedicated overview page showing what's owed to every supplier vs. what's been paid, per currency, plus per-supplier payment recording.
- **Client portal** — clients can view their bookings, browse open/bookable packages, and submit self-service booking applications directly from their own portal.
- **Capacity enforcement** — seat availability is checked and race-safe (via a MySQL named lock) on every booking, booking update, status reactivation, and group-member addition.
- **Fully localized** — English and Arabic language files, 1:1 parity.

## Installation

1. Copy this repository's contents into your Perfex CRM installation at:
   ```
   modules/travel_agency/
   ```
2. Log in to the admin panel and go to **Setup → Modules**.
3. Activate the **Travel Agency** module. This runs the module's `install.php`, which creates the required database tables and seeds the default package types.
4. A new **Travel Agency** item appears in the admin sidebar.

## Permissions

The module registers two independent staff capability groups (`view`/`create`/`edit`/`delete` each):

- **`travel_agency`** — packages, package types, bookings, and groups.
- **`travel_agency_suppliers`** — suppliers and their accounts/payments.

Assign these under **Setup → Staff → [staff member] → Permissions** as needed. Keeping them separate lets you grant booking access without exposing supplier financials, or vice versa.

## Database tables

| Table | Purpose |
|---|---|
| `travel_package_types` | Trip type taxonomy |
| `travel_suppliers` | Supplier directory |
| `travel_packages` | Tour packages (price, cost, currency, duration, seats) |
| `travel_bookings` | Customer bookings (linked invoice, travelers, total) |
| `travel_groups` | Group trips |
| `travel_group_itinerary_stops` | Per-group multi-city itinerary stops |
| `travel_group_transport` | Per-group transport legs |
| `travel_group_members` | Travelers within a group, with passport/visa/photo data |
| `travel_supplier_payments` | Supplier payment records |

## License

This module is provided as-is for use with Perfex CRM. See the repository owner for licensing terms.
