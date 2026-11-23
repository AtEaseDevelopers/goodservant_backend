# Pending client requests

Requests that were reviewed but not built yet. When a request here is
picked up again, start from its notes; when it is done, move it to the
"Done" section at the bottom with the date and commits.

---

## Good Servant: "Delete invoice" in the driver app

- **Asked:** 24 Nov 2026
- **Status:** PENDING - waiting for the client to confirm that "Delete" can
  be the existing **Cancel invoice** (the owner is checking with them).

### The request (from the client)

1. **Invoice deletion permission**
   - Drivers can only delete invoices they created or converted themselves
     during their **current trip**.
   - Not invoices from previous trips or other drivers.
2. **SO handling**
   - If an invoice converted from an SO is deleted, the SO stays unchanged:
     it is still treated as a converted SO (no revert).
3. **Invoice history**
   - Deleted invoices are still listed in the invoice history.
   - Admin can view the deleted invoice's details.

### Finding: the driver app's Cancel already does this

Driver app: invoice page -> **Cancel** (`DELETE /api/v1/driver/invoice/{id}`,
`DriverController::cancelinvoice`). Checked against the request:

| Request | Today's Cancel | |
|---|---|---|
| Own invoices only | refuses other drivers' invoices | done |
| Current trip only | refuses previous trips, **but** compares with `drivers.trip_id` (can lag behind - see CLAUDE.md "Things that bite") and lets invoices with no `trip_id` through | **fix** |
| SO stays converted | never touches the SO | done |
| Still in history | stays in the app's invoice list as status **Cancelled** (status 2) | done (wording) |
| Admin can view | Invoices list shows **Cancelled** (+ filter); the invoice page shows all details | done (wording) |
| Stock / payments | stock back to the lorry, the invoice's payments set to status 2 | done |
| AutoCount | invoices synced to AutoCount cannot be cancelled | done |
| Number | never handed out again (cancelled numbers are not re-used) | done |

### To do when the client confirms "Delete = Cancel"

1. **Trip check:** compare with the driver's real open trip (latest `trips`
   row with `type = 1`); refuse when the invoice's `trip_id` is not that
   trip, or is empty.
2. **Wording (if the client wants the word "Delete"):** rename Cancel ->
   Delete in the app (new labels in en / ms / zh via a `mobile_translations`
   migration + version bump) and show **Deleted** (by a driver) separately
   from **Cancelled** (by the office) in admin. Keep status 2 internally, so
   every report / credit / AutoCount query that leaves out cancelled invoices
   also leaves out deleted ones; add `cancelled_by_driver_id`, `cancelled_at`
   (and optional reason) and show "Deleted by KD1 on 24-11-2026 14:05" on the
   admin invoice page.
3. Offline documents page: "Cancel document" -> "Delete document" for the
   same wording.
4. New app version for the wording change.

### Extra gaps found (raise with the client, not part of their request)

- **Invoice made from DOs** (driver combine DO -> invoice): cancelling leaves
  the DOs linked to the cancelled invoice, so they count as converted forever
  and can never be invoiced again. Suggest: release the DOs on delete.
- **Credit payment collected later:** if a credit invoice already has a Pay
  Credit payment, cancelling also wipes that payment, so the cash the driver
  holds disappears from the End Trip totals. Suggest: refuse delete when the
  invoice has a payment made after it was created ("ask the office").
- **FOC progress:** creating an invoice adds to the customer's FOC count;
  cancel does not take it back. Suggest: reverse it.
- After deleting an invoice converted from an SO, the SO stays "converted"
  (as asked), so the driver re-does it with Create Invoice.

---

## Done

- *(nothing yet)*
