<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Comment;
use Carbon\Carbon;
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
        $expiry_date = Carbon::parse($this->attributes['expiry_date']);
        $now = Carbon::now();
        $price = $this->attributes['price'];
        $discount = 0.0;

        if ($expiry_date->diffInDays($now) <= 15) {
            $discount = $price * $this->attributes['fifteen_days_discount'] / 100;
        } else if ($expiry_date->diffInDays($now) <= 30) {
            $discount = $price * $this->attributes['thirty_days_discount'] / 100;
        }

        return number_format($price - $discount, 2, '.', '');
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
        $this->attributes['expiry_date'] = Carbon::parse($value);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
