<?php

namespace App\Enums;

enum Product
{
    const CONDITIONS = [
        'new' => 1000,
        'refurbished' => 2000,
        'open_box' => 1500,
        'seller_refurbished' => 2500,
        'used' => 3000,
        'like_new' => 2750,
        'very_good' => 2750,
        'good' => 4000,
        'acceptable' => 5000,
        'for_parts_or_not_working' => 7000,
    ];

    const FIELD_MAPPING = [
        'sku' => 'SKU',
        'title' => 'Title',
        'description' => 'Description',
        'category_id' => 'PrimaryCategory',
        'price' => 'StartPrice',
        'stock' => 'Quantity',
        'brand' => 'Brand',
        'model' => 'Model',
        'images' => 'PictureDetails',
        'condition' => 'ConditionID',
        'shipping_details' => 'ShippingDetails',
    ];
}
