<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Product;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    public function productPrice() {
        return $this->hasMany(ProductVariantPrice::class, 'product_id')->with('productVariantOne', 'productVariantTwo', 'productVariantThree');
    }

    public function productVariant() {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

}
