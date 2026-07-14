<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $products = Product::active()->orderBy('sort_order')->get();

        return view('storefront.home', [
            'webTiers' => $products->where('category', ProductCategory::WebDevelopment),
            'hostingPlans' => $products->where('category', ProductCategory::Hosting),
            'categories' => $products->groupBy('category'),
        ]);
    }

    public function services(): View
    {
        $grouped = Product::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('storefront.services', ['grouped' => $grouped]);
    }

    public function service(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('storefront.service', ['product' => $product]);
    }
}
