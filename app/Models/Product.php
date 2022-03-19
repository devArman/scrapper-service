<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'asin',
        'cat_id',
        'research_id',
        'on_amazon_page',
        'price',
        'url',
        'variants',
        'image',
        'currency',
        'description',
        'availability',
        'rating',
        'total_reviews',
        'amazonChoice',
        'prime',
        'dispatches_from',
        'sold_by',
        'feature_bullets',
        'amazon_product_list_scrapped',
        'amazon_single_product_scrapped',
        'keepa_api_scrapped',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'feature_bullets' => 'array',
        'variants' => 'array',
        'ebay_products' => 'array'
    ];
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use($search){
                $query->where('name', 'like', '%'.$search.'%')->
                orWhere('asin', 'like', '%'.$search.'%');
            });
        })->when($filters['rating'] ?? null, function ($query, $search) {
            $query->where('rating','>=', $search);
        })->when($filters['availability'] ?? null, function ($query, $search) {
            $query->whereIn('availability', $search);
        })->when($filters['amazonChoice'] ?? null, function ($query, $search) {
            $query->where('amazon_choice', $search);
        })->when($filters['prime'] ?? null, function ($query, $search) {
            $query->where('prime', $search);
        });
    }

    public function images(){
        return $this->hasMany('App\Models\ProductImages', 'product_id', 'id');
    }
}
