<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\Discount;
use App\Models\DiscountRedemption;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Audit\AuditLog;

/**
 * Admin: discount codes and site-wide sales.
 *
 * Giving money away is a real financial action, so every create/edit/delete is
 * audit-logged and every discount carries a redemption trail showing exactly
 * what was given and to whom.
 */
class DiscountController extends Controller
{
    public function index(Request $request): Response
    {
        $discounts = Discount::ordered();

        $given = [];
        foreach ($discounts as $d) {
            $given[$d['id']] = DiscountRedemption::totalGiven($d['id']);
        }

        return $this->view('admin.discounts.index', [
            'title'     => 'Discounts & Sales',
            'discounts' => $discounts,
            'given'     => $given,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.discounts.form', [
            'title'      => 'New Discount',
            'discount'   => null,
            'categories' => ServiceCategory::ordered(),
            'services'   => Service::active(),
            'clients'    => Client::query()->orderBy('business_name')->get(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->discountData($request, null);
        $discount = Discount::create($data);

        AuditLog::record('discount.created', 'discount', $discount['id'], [
            'code' => $discount['code'], 'name' => $discount['name'],
        ]);
        Session::flash('success', $discount['is_sale']
            ? 'Sale created. It applies automatically — no code needed.'
            : 'Discount created. Share the code with your client.');

        return $this->redirect(route('admin.discounts.edit', ['id' => $discount['id']]));
    }

    public function edit(Request $request, string $id): Response
    {
        $discount = Discount::findOrFail($id);

        return $this->view('admin.discounts.form', [
            'title'       => 'Edit Discount',
            'discount'    => $discount,
            'categories'  => ServiceCategory::ordered(),
            'services'    => Service::active(),
            'clients'     => Client::query()->orderBy('business_name')->get(),
            'redemptions' => DiscountRedemption::forDiscount($id),
            'given'       => DiscountRedemption::totalGiven($id),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $existing = Discount::findOrFail($id);
        Discount::updateById($id, $this->discountData($request, $existing));

        AuditLog::record('discount.updated', 'discount', $id, ['code' => $existing['code']]);
        Session::flash('success', 'Discount saved.');

        return $this->redirect(route('admin.discounts.edit', ['id' => $id]));
    }

    public function destroy(Request $request, string $id): Response
    {
        $discount = Discount::findOrFail($id);
        Discount::deleteById($id);

        AuditLog::record('discount.deleted', 'discount', $id, ['code' => $discount['code']]);
        Session::flash('status', 'Discount deleted. Invoices that already used it keep their discount.');

        return $this->redirect(route('admin.discounts.index'));
    }

    /** @param array<string,mixed>|null $existing */
    private function discountData(Request $request, ?array $existing): array
    {
        $isSale = $request->boolean('is_sale');

        $data = $this->validate($request, [
            'name'                => 'required|max:120',
            'code'                => 'nullable|max:40',
            'type'                => 'required|in:' . implode(',', array_keys(Discount::TYPES)),
            'amount'              => 'required|numeric',
            'scope'               => 'required|in:' . implode(',', array_keys(Discount::SCOPES)),
            'category_id'         => 'nullable|integer',
            'service_id'          => 'nullable|integer',
            'client_id'           => 'nullable|integer',
            'min_spend'           => 'nullable|numeric',
            'starts_at'           => 'nullable|date',
            'ends_at'             => 'nullable|date',
            'max_uses'            => 'nullable|integer',
            'max_uses_per_client' => 'nullable|integer',
        ], [
            'amount' => 'Amount',
        ]);

        // Percent -> basis points (house convention); fixed -> cents.
        $amount = (float) $data['amount'];
        $value = $data['type'] === Discount::TYPE_PERCENT
            ? (int) round($amount * 100)
            : (int) round($amount * 100);

        if ($data['type'] === Discount::TYPE_PERCENT) {
            // A >100% discount would just clamp to free, but it means the admin
            // typed something wrong — cap it rather than silently give it away.
            $value = max(0, min($value, 10000));
        }

        $code = Discount::normaliseCode($data['code'] ?? null);
        // A sale needs no code; a non-sale without a code could never be used.
        if ($isSale) {
            $code = null;
        }
        if (! $isSale && $code === null) {
            $code = $this->uniqueCodeFrom($data['name']);
        }
        if ($code !== null && $this->codeTaken($code, $existing['id'] ?? null)) {
            $code = $this->uniqueCodeFrom($code);
        }

        $scope = $data['scope'];

        return [
            'code'                => $code,
            'name'                => $data['name'],
            'type'                => $data['type'],
            'value'               => $value,
            'currency'            => config('company.currency') ?: 'AUD',
            'scope'               => $scope,
            'category_id'         => $scope === Discount::SCOPE_CATEGORY ? ($data['category_id'] ?: null) : null,
            'service_id'          => $scope === Discount::SCOPE_SERVICE ? ($data['service_id'] ?: null) : null,
            'min_spend_cents'     => ($data['min_spend'] ?? '') === '' ? null : (int) round((float) $data['min_spend'] * 100),
            'starts_at'           => $data['starts_at'] ?: null,
            'ends_at'             => $data['ends_at'] ?: null,
            'max_uses'            => ($data['max_uses'] ?? '') === '' ? null : (int) $data['max_uses'],
            'max_uses_per_client' => ($data['max_uses_per_client'] ?? '') === '' ? null : (int) $data['max_uses_per_client'],
            'client_id'           => $data['client_id'] ?: null,
            'is_sale'             => $isSale ? 1 : 0,
            'active'              => $request->boolean('active') ? 1 : 0,
        ];
    }

    private function codeTaken(string $code, int|string|null $ignoreId): bool
    {
        $existing = Discount::firstWhere('code', $code);

        return $existing && (string) $existing['id'] !== (string) $ignoreId;
    }

    /** Derive a readable, unique code from a name ("Spring Sale" -> SPRINGSALE). */
    private function uniqueCodeFrom(string $seed): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $seed) ?: 'SAVE');
        $base = substr($base, 0, 24) ?: 'SAVE';
        $code = $base;
        $n = 2;
        while (Discount::firstWhere('code', $code)) {
            $code = $base . $n++;
        }

        return $code;
    }
}
