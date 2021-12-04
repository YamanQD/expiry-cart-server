<?php

namespace App\Models;

use App\Models\Category;
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
        'category_id',
        'user_id',
    ];

    // Calculate price based on expiry date
    public function getPriceAttribute()
    {
        if ($this->expiry_date->diffInDays() <= 30) {
            return $this->price * $this->thirty_days_discount / 100;
        } else if ($this->expiry_date->diffInDays() <= 15) {
            return $this->price * $this->fifteen_days_discount / 100;
        } else {
            return $this->price;
        }
    }

    // Save the image on the server and return its name
    public function setImageAttribute($value)
    {
        $path = public_path('images/products');
        $name = time() . '.' . $value->getClientOriginalExtension();

        $value->move($path, $name);
        $this->attributes['image'] = $name;
    }

    public function setExpiryDateAttribute($value)
    {
        $this->attributes['expiry_date'] = date('Y-m-d', strtotime($value));
    }

    public function getExpiryDateAttribute()
    {
        return date('Y-m-d', strtotime($this->attributes['expiry_date']));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
