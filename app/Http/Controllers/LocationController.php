<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class LocationController extends Controller
{
    /** City landing page: /web-design-{city} */
    public function show(string $city): View
    {
        $location = Arr::first(config('locations.cities', []), fn ($c) => $c['slug'] === $city);

        abort_unless($location, 404);

        $products = Product::active()->orderBy('sort_order')->get();

        return view('locations.show', [
            'location' => $location,
            'others' => collect(config('locations.cities', []))->reject(fn ($c) => $c['slug'] === $city)->values(),
            'webTiers' => $products->where('category', ProductCategory::WebDevelopment),
        ]);
    }
}
