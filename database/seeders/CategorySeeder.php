<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['ebay_category_id' => '15032', 'name' => 'Cell Phones & Smartphones'],
            ['ebay_category_id' => '9394', 'name' => 'Cell Phone Accessories'],
            ['ebay_category_id' => '175672', 'name' => 'Laptops & Netbooks'],
            ['ebay_category_id' => '31569', 'name' => 'Computer Components & Parts'],
            ['ebay_category_id' => '155040', 'name' => 'Men\'s Clothing'],
            ['ebay_category_id' => '15724', 'name' => 'Women\'s Clothing'],
            ['ebay_category_id' => '3034', 'name' => 'Men\'s Shoes'],
            ['ebay_category_id' => '3035', 'name' => 'Women\'s Shoes'],
            ['ebay_category_id' => '267', 'name' => 'Books'],
            ['ebay_category_id' => '171228', 'name' => 'Textbooks, Education'],
            ['ebay_category_id' => '11450', 'name' => 'Electronics'],
            ['ebay_category_id' => '220', 'name' => 'Toys & Hobbies'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['ebay_category_id' => $category['ebay_category_id']],
                ['name' => $category['name']]
            );
        }
    }
} 