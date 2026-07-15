<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\Money;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.services.index', [
            'title'      => 'Services',
            'services'   => Service::query()->orderBy('name')->get(),
            'categories' => ServiceCategory::ordered(),
            'cat_names'  => array_column(ServiceCategory::all(), 'name', 'id'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.services.form', [
            'title'      => 'New service',
            'service'    => null,
            'categories' => ServiceCategory::ordered(),
        ]);
    }

    public function store(Request $request): Response
    {
        Service::create($this->serviceData($request));
        Session::flash('success', 'Service created.');

        return $this->redirect(route('admin.services.index'));
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->view('admin.services.form', [
            'title'      => 'Edit service',
            'service'    => Service::findOrFail($id),
            'categories' => ServiceCategory::ordered(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        Service::findOrFail($id);
        Service::updateById($id, $this->serviceData($request));
        Session::flash('success', 'Service updated.');

        return $this->redirect(route('admin.services.index'));
    }

    public function destroy(Request $request, string $id): Response
    {
        Service::findOrFail($id);
        Service::deleteById($id);
        Session::flash('status', 'Service deleted.');

        return $this->redirect(route('admin.services.index'));
    }

    protected function serviceData(Request $request): array
    {
        $data = $this->validate($request, [
            'name'         => 'required|max:160',
            'category_id'  => 'nullable|exists:service_categories,id',
            'description'  => 'nullable|max:1000',
            'billing_type' => 'required|in:one_off,recurring',
            'interval'     => 'nullable|in:monthly,quarterly,yearly',
            'price'        => 'required|numeric|min:0',
        ]);

        $recurring = $data['billing_type'] === Service::BILLING_RECURRING;

        return [
            'name'         => $data['name'],
            'category_id'  => ! empty($data['category_id']) ? (int) $data['category_id'] : null,
            'description'  => $data['description'] ?? null,
            'billing_type' => $data['billing_type'],
            'interval'     => $recurring ? ($data['interval'] ?: Service::INTERVAL_MONTHLY) : null,
            'price_cents'  => Money::fromDollars($data['price'])->minorUnits,
            'currency'     => config('company.currency', 'AUD'),
            'active'       => $request->boolean('active') ? 1 : 0,
        ];
    }
}
