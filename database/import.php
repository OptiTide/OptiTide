<?php

use App\Core\Database;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\Invoices\InvoiceService;
use App\Support\Money;

/**
 * `php bin/console import:clients <file.csv>` / `import:invoices <file.csv>`
 *
 * Bring history over from another system without typing it in one row at a time.
 *
 * DRY RUN BY DEFAULT. It parses, validates and reports, and writes nothing unless you
 * pass --commit. You get to see exactly what it would do — and what it would reject —
 * before it touches a live database.
 *
 * Everything it creates is deliberately SILENT: notify=false so a client is never
 * emailed a "new" invoice they settled two years ago, and no_auto_chase on unpaid rows
 * so the overdue engine doesn't fine and suspend someone because you changed CRM.
 *
 * Idempotent on re-run: clients match on email, invoices on number. A second run
 * updates nothing and skips what already exists, so a half-finished import can simply
 * be run again.
 */
return new class {
    /**
     * clients.csv — business_name is the only required column.
     *
     * business_name,contact_name,email,phone,abn,address_line1,address_locality,
     * address_region,address_postcode,notes,created_at
     */
    public function clients(callable $out, string $path, bool $commit): int
    {
        $rows = $this->read($path, ['business_name'], $out);
        if ($rows === null) {
            return 1;
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $line = $row["__line"];
            $name = trim((string) ($row['business_name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));

            if ($name === '') {
                $out("  line {$line}: SKIP — business_name is empty");
                $errors++;
                continue;
            }

            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out("  line {$line}: SKIP — '{$email}' is not a valid email");
                $errors++;
                continue;
            }

            // Idempotent. Match on email where there is one; fall back to the exact
            // business name where there isn't, or an email-less row would be created
            // afresh on every re-run — and re-running is the whole point of a dry run
            // followed by a commit, or of resuming a half-finished import.
            $existing = $email !== ''
                ? Client::firstWhere('email', $email)
                : Client::firstWhere('business_name', $name);

            if ($existing) {
                $out("  line {$line}: exists — {$name}" . ($email !== '' ? " <{$email}>" : ' (matched on name — no email)'));
                $skipped++;
                continue;
            }

            $since = $this->date($row['created_at'] ?? null);

            $out(sprintf('  line %d: %s %s%s', $line, $commit ? 'CREATE' : 'would create', $name, $since ? " (since {$since})" : ''));

            if ($commit) {
                Client::create(array_filter([
                    'business_name'    => $name,
                    'contact_name'     => $this->str($row['contact_name'] ?? null),
                    'email'            => $email ?: null,
                    'phone'            => $this->str($row['phone'] ?? null),
                    'abn'              => $this->str($row['abn'] ?? null),
                    'address_line1'    => $this->str($row['address_line1'] ?? null),
                    'address_locality' => $this->str($row['address_locality'] ?? null),
                    'address_region'   => $this->str($row['address_region'] ?? null),
                    'address_postcode' => $this->str($row['address_postcode'] ?? null),
                    'notes'            => $this->str($row['notes'] ?? null),
                    'status'           => 'active',
                    // Backdate only: a future "client since" is nonsense.
                    'created_at'       => $since && $since < today() ? $since . ' 09:00:00' : null,
                ], fn ($v) => $v !== null));
            }

            $created++;
        }

        $this->summary($out, $commit, $created, $skipped, $errors, 'client');

        return $errors > 0 && $created === 0 ? 1 : 0;
    }

    /**
     * invoices.csv — one row per invoice (single line item).
     *
     * client_email,number,description,amount,issue_date,due_date,paid_date,status
     *
     * amount is DOLLARS, GST-INCLUSIVE (the house convention: GST is the component
     * within the total, never added on top). status: paid | unpaid.
     */
    public function invoices(callable $out, string $path, bool $commit): int
    {
        $rows = $this->read($path, ['client_email', 'amount'], $out);
        if ($rows === null) {
            return 1;
        }

        $svc = new InvoiceService();
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $line = $row["__line"];
            $email = strtolower(trim((string) ($row['client_email'] ?? '')));
            $number = trim((string) ($row['number'] ?? ''));

            $client = $email !== '' ? Client::firstWhere('email', $email) : null;
            if (! $client) {
                $out("  line {$line}: SKIP — no client with email '{$email}' (import clients first)");
                $errors++;
                continue;
            }

            if ($number !== '' && Invoice::firstWhere('number', $number)) {
                $out("  line {$line}: exists — {$number}");
                $skipped++;
                continue;
            }

            $amount = trim((string) ($row['amount'] ?? ''));
            if ($amount === '' || ! is_numeric(str_replace(['$', ','], '', $amount))) {
                $out("  line {$line}: SKIP — amount '{$amount}' is not a number");
                $errors++;
                continue;
            }

            $currency = config('company.currency', 'AUD');
            $money = Money::fromDollars((float) str_replace(['$', ','], '', $amount), $currency);

            $issue = $this->date($row['issue_date'] ?? null) ?: today();
            $due = $this->date($row['due_date'] ?? null) ?: $issue;
            $paidDate = $this->date($row['paid_date'] ?? null);
            $paid = $paidDate !== null || strtolower(trim((string) ($row['status'] ?? ''))) === 'paid';
            $backdated = $issue < today();

            $out(sprintf(
                '  line %d: %s %s for %s — %s, %s%s',
                $line,
                $commit ? 'CREATE' : 'would create',
                $number ?: '(auto number)',
                $client['business_name'],
                $money->format(),
                $paid ? 'paid' : 'UNPAID',
                $backdated && ! $paid ? ' [exempt from auto-chase]' : ''
            ));

            if ($commit) {
                $invoice = $svc->create([
                    'client_id'  => $client['id'],
                    'currency'   => $currency,
                    'number'     => $number !== '' ? $number : null,
                    'status'     => Invoice::STATUS_SENT,
                    'issue_date' => $issue,
                    'due_date'   => $due,
                    'created_at' => $backdated ? $issue . ' 09:00:00' : null,
                    // Never email history. The client settled this two years ago.
                    'notify'     => false,
                    // An unpaid backdated invoice is long past due, so without this the
                    // sweep would fine it and suspend the client for migrating CRM.
                    'no_auto_chase' => $backdated && ! $paid ? 1 : 0,
                ], [[
                    'description'      => $this->str($row['description'] ?? null) ?: 'Imported',
                    'quantity'         => 1,
                    'unit_price_cents' => $money->minorUnits,
                ]]);

                if ($paid) {
                    // recordPayment emails a receipt, which we must not send for
                    // history — it reconciles status/amount_paid, so do it directly.
                    Invoice::updateById($invoice['id'], [
                        'status'            => Invoice::STATUS_PAID,
                        'amount_paid_cents' => (int) $invoice['total_cents'],
                        'paid_at'           => $paidDate ?: $issue,
                    ]);
                    \App\Models\Payment::create([
                        'invoice_id'   => $invoice['id'],
                        'client_id'    => $client['id'],
                        'amount_cents' => (int) $invoice['total_cents'],
                        'currency'     => $currency,
                        'method'       => 'imported',
                        'reference'    => 'Ported from previous system',
                        'paid_at'      => $paidDate ?: $issue,
                        'recorded_by'  => null,
                        'created_at'   => ($paidDate ?: $issue) . ' 09:00:00',
                    ]);
                }
            }

            $created++;
        }

        $this->summary($out, $commit, $created, $skipped, $errors, 'invoice');

        return $errors > 0 && $created === 0 ? 1 : 0;
    }

    /** Parse the CSV to an array of assoc rows, or null if it is unusable. */
    private function read(string $path, array $required, callable $out): ?array
    {
        if (! is_file($path) || ! is_readable($path)) {
            $out("Can't read {$path}");

            return null;
        }

        $fh = fopen($path, 'r');
        $header = fgetcsv($fh);

        if (! $header) {
            $out('That file has no header row.');
            fclose($fh);

            return null;
        }

        // Tolerate a UTF-8 BOM (Excel adds one) and stray case/spacing in headers.
        $header = array_map(
            fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h))),
            $header
        );

        $missing = array_diff($required, $header);
        if ($missing !== []) {
            $out('Missing required column(s): ' . implode(', ', $missing));
            $out('Found: ' . implode(', ', $header));
            fclose($fh);

            return null;
        }

        $rows = [];
        $lineNo = 1; // the header
        while (($line = fgetcsv($fh)) !== false) {
            $lineNo++;
            if ($line === [null] || $line === []) {
                continue; // blank line
            }
            // Pad/trim so a ragged row cannot misalign every column after it.
            $line = array_slice(array_pad($line, count($header), null), 0, count($header));
            $row = array_combine($header, $line);
            // Carry the REAL file line: skipped blanks would otherwise shift every
            // number after them, and an error pointing at the wrong row of a 200-line
            // CSV is worse than no line number at all.
            $row['__line'] = $lineNo;
            $rows[] = $row;
        }
        fclose($fh);

        return $rows;
    }

    /** Accept the formats a real export actually uses; null if unparseable. */
    private function date(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // d/m/Y first: an Australian export means 03/06/2024 = 3 June, and strtotime
        // would read it as 6 March.
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        $ts = strtotime($value);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    private function str(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function summary(callable $out, bool $commit, int $created, int $skipped, int $errors, string $noun): void
    {
        $out('');
        $out(sprintf(
            '%s %d %s%s, skipped %d already present, %d rejected.',
            $commit ? 'Imported' : 'DRY RUN — would import',
            $created,
            $noun,
            $created === 1 ? '' : 's',
            $skipped,
            $errors
        ));

        if (! $commit && $created > 0) {
            $out('Nothing was written. Re-run with --commit to do it for real.');
        }
    }
};
