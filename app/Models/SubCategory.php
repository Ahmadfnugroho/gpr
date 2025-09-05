<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubCategory extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'photo',
        'slug',
        'category_id',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function setNameAttribute($value)
    {
        // Jika nilai slug tidak diberikan, generate slug dari nm_produk
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name']);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
