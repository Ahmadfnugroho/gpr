<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Brand extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'logo',
        'slug',
        'premiere',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'logo',
                'slug',
                'premiere',
            ]);
    }
    public function getLogoUrlAttribute()
    {
        return $this->logo
            ? url('storage/' . ltrim($this->logo, '/'))
            : null;
    }

    public function setNameAttribute($value)
    {
        // Jika nilai slug tidak diberikan, generate slug dari nm_produk
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function bundlings()
    {
        // Get bundlings that contain products from this brand
        return Bundling::whereHas('products', function($query) {
            $query->where('brand_id', $this->id);
        });
    }
}
