# Pending client requests

Requests that were reviewed but not built yet. When a request here is
picked up again, start from its notes; when it is done, move it to the
"Done" section at the bottom with the date and commits.

---

*(nothing pending)*

---

## Done

- **Good Servant "Delete invoice"** - closed 24 Nov 2026. A Delete button
  (hidden from the app, "Deleted" status in admin) was built and released in
  1.0.79, then the client decided to keep **only Cancel Invoice**, moved into
  the invoice page's top-right menu. Delete and the admin "Deleted" status
  were removed again (backend revert of 75ddd6f + migration
  2026_11_24_150000; no invoice had been deleted). Kept from that work: the
  last trip summary leaves cancelled invoices out.
  - Not done (raise with the client if needed): Cancel's trip check still
    uses `drivers.trip_id`; cancelling a DO-made invoice leaves its DOs
    linked; FOC count is not reversed; a later credit payment is voided too.
