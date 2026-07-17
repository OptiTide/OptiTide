# Porting history in from another system

Two commands. **Both are a dry run unless you pass `--commit`** — they parse, validate
and report what they *would* do, and write nothing.

```
php bin/console import:clients  clients.csv            # dry run
php bin/console import:clients  clients.csv  --commit  # do it
php bin/console import:invoices invoices.csv --commit  # clients FIRST
```

Import clients before invoices — invoices are matched to a client by email.

## clients.csv

Only `business_name` is required.

```csv
business_name,contact_name,email,phone,abn,address_line1,address_locality,address_region,address_postcode,notes,created_at
Coastline Cafe,Jo Smith,jo@coastlinecafe.com.au,08 9000 1111,12 345 678 901,12 Ocean Dr,Scarborough,WA,6019,Long-standing client,03/06/2023
```

`created_at` is "client since" — backdate only; blank means today.

## invoices.csv

One row per invoice, one line item each.

```csv
client_email,number,description,amount,issue_date,due_date,paid_date,status
jo@coastlinecafe.com.au,INV-000150,Website build,1650.00,03/06/2023,17/06/2023,20/06/2023,paid
jo@coastlinecafe.com.au,INV-000151,SEO retainer,750,01/07/2023,15/07/2023,,unpaid
```

- **`amount` is dollars and GST-INCLUSIVE.** The house rule: GST is the component
  *within* the total (total ÷ 11), never added on top. Put in what you actually charged.
- **`number` keeps your original.** Blank auto-generates. The counter jumps past
  anything you import, so your next real invoice can't collide with ported history.
  Free-form references (`LEGACY-88`) are fine.
- An invoice is treated as paid if `status` is `paid` **or** `paid_date` is set.

## What it does on purpose

- **Emails nobody.** Not the client, not you. Importing history must never send someone
  an "invoice" they settled two years ago.
- **Unpaid backdated invoices are exempt from auto-chase.** Their due date is long gone,
  so the overdue engine would otherwise fine them a late fee, email the client, and
  suspend them — because you changed CRM. You can still chase them by hand.
- **Dates are read Australian.** `03/06/2023` is 3 June, not 6 March. ISO
  (`2023-06-03`) also works.
- **Safe to re-run.** Clients match on email (or exact business name if there's no
  email); invoices match on number. A second run adds nothing, so a half-finished
  import can just be run again.
- Excel's UTF-8 BOM, `$`/comma in amounts, blank lines and ragged rows are all handled.
  Bad rows are reported with their real file line number and skipped — one bad row
  never stops the rest.

## Suggested order

1. Export from the old system, open in Excel, rename the headers to match above.
2. Dry-run both. Read the rejections.
3. Fix the CSV, dry-run again.
4. `--commit` clients, then `--commit` invoices.
5. Spot-check a client in the admin: their invoices, dates and numbers.

Take a database snapshot in Coolify first. There is no undo.
