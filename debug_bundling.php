<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

$bundling = App\Models\Bundling::where('slug', 'canon-eos-60d--canon-ef-50mm-f18-stm')->first();

if ($bundling) {
    echo "Bundling found: " . $bundling->name . "\n";
    echo "Products count: " . $bundling->products->count() . "\n";
    
    foreach ($bundling->products as $product) {
        echo "Product: " . $product->name . " (Qty: " . $product->pivot->quantity . ")\n";
    }
} else {
    echo "Bundling not found\n";
}
