<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $categories = Category::factory(3)->create();
        $products = Product::factory(3)->create();
        foreach ($products as $product) {
            $product->categories()->sync([$categories[0]['id'], $categories[1]['id']]);
        }
    }
}
