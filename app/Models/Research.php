<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Research extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','name', 'results','scans','data','account_id'
    ];
    public function campaign()
    {
        return $this->belongsTo('User', 'user_id');
    }

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'data' => 'array'

    ];

    public function getTotalsAttribute(){
        return Product::where('research_id',$this->id)->count();
    }
    public function getScannedAttribute(){
        return Product::where('research_id',$this->id)->where('amazon_product_list_scrapped',1)->where('amazon_single_product_scrapped',1)->count();
    }
    public function account()
    {
        return $this->hasOne('account', 'account_id');
    }
}
