# Pending client requests

Requests that were reviewed but not built yet. When a request here is
picked up again, start from its notes; when it is done, move it to the
"Done" section at the bottom with the date and commits.

---

*(nothing pending)*

---

## Done

- **Good Servant "Delete invoice"** - built 24 Nov 2026 after the client
  asked for **both** buttons:
  - App invoice page: **Cancel Invoice** (stays in the app list as
    Cancelled) and **Delete Invoice** (no longer shown in the app).
  - Delete = Cancel on the server (stock back to the lorry, payments
    voided, status 2 so reports and credit leave it out) plus
    `invoices.driver_deleted_at` / `driver_deleted_by`. Only the driver's
    own invoice of the trip open right now (`POST /driver/invoice/{id}/delete`).
  - Admin Invoices list: new status **Deleted** (column + filter); the
    invoice page shows "Deleted in the driver app by ... on ...".
  - Last trip summary no longer counts cancelled / deleted invoices.
  - Still open (raise with the client): DOs of a deleted DO-invoice stay
    linked; FOC count is not reversed; a later credit payment is voided too.
