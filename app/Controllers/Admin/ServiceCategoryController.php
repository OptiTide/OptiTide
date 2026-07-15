<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ServiceCategory;

class ServiceCategoryController extends Controller
{
    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'       => 'required|max:80',
            'description' => 'nullable|max:255',
        ]);

        ServiceCategory::create([
            'name'        => $data['name'],
            'slug'        => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'sort_order'  => (int) (ServiceCategory::query()->selectRaw('COALESCE(MAX(sort_order), 0) AS m')->first()['m'] ?? 0) + 1,
        ]);

        Session::flash('success', 'Service line added.');

        return $this->redirect(route('admin.services.index'));
    }

    public function update(Request $request, string $id): Response
    {
        ServiceCategory::findOrFail($id);
        $data = $this->validate($request, [
            'name'        => 'required|max:80',
            'description' => 'nullable|max:255',
        ]);

        ServiceCategory::updateById($id, ['name' => $data['name'], 'description' => $data['description'] ?? null]);
        Session::flash('success', 'Service line updated.');

        return $this->redirect(route('admin.services.index'));
    }

    public function destroy(Request $request, string $id): Response
    {
        ServiceCategory::findOrFail($id);
        ServiceCategory::deleteById($id);
        Session::flash('status', 'Service line removed.');

        return $this->redirect(route('admin.services.index'));
    }

    protected function uniqueSlug(string $name): string
    {
        $base = slugify($name);
        $slug = $base;
        $i = 1;
        while (ServiceCategory::firstWhere('slug', $slug)) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
