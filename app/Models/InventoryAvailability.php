<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAvailability extends Model
{
    // This is a virtual model for combining product and bundling availability
    // It doesn't have a real database table
    
    protected $fillable = [];
    
    // Disable timestamps since this is a virtual model
    public $timestamps = false;
}
