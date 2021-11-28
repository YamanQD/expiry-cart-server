<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'image',
        'price',
        'quantity',
        'contact_info',
        'expiry_date',
        'thirty_days_discount',
        'fifteen_days_discount',
    ];

    public function setImageAttribute($value)
    {
        $path = public_path('images/products');
        $name = time() . '.' . $value->getClientOriginalExtension();

        $value->move($path, $name);
        $this->attributes['image'] = $name;
    }
}
