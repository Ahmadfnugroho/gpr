# 🎯 REMAINING RESOURCES - UPDATE GUIDE

## 🚀 RESOURCES TO UPDATE

### **SubCategoryResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-folder-open';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Sub Categories';
protected static ?int $navigationSort = 23;
```

### **ProductPhotoResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-photo';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Product Photos';
protected static ?int $navigationSort = 25;
```

### **ProductSpecificationResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-document-text';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Specifications';
protected static ?int $navigationSort = 26;
```

### **BundlingResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-archive-box';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Bundles';
protected static ?int $navigationSort = 27;
```

### **BundlingPhotoResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Bundle Photos';
protected static ?int $navigationSort = 28;
```

### **PromoResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-ticket';
protected static ?string $navigationGroup = 'Sales & Transactions';
protected static ?string $navigationLabel = 'Promotions';
protected static ?int $navigationSort = 32;
```

### **RentalIncludeResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
protected static ?string $navigationGroup = 'Sales & Transactions';
protected static ?string $navigationLabel = 'Rental Includes';
protected static ?int $navigationSort = 33;
```

### **UserPhoneNumberResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-phone';
protected static ?string $navigationGroup = 'User Management';
protected static ?string $navigationLabel = 'Phone Numbers';
protected static ?int $navigationSort = 12;
```

### **UserPhotoResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-camera';
protected static ?string $navigationGroup = 'User Management';
protected static ?string $navigationLabel = 'User Photos';
protected static ?int $navigationSort = 13;
```

### **ApiKeyResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-key';
protected static ?string $navigationGroup = 'System & Settings';
protected static ?string $navigationLabel = 'API Keys';
protected static ?int $navigationSort = 41;
```

---

## 🎨 FINAL NAVIGATION STRUCTURE

```
👥 User Management (10-19)
├── Users (11) - heroicon-o-users
├── Phone Numbers (12) - heroicon-o-phone
└── User Photos (13) - heroicon-o-camera

🛍️ Product Catalog (20-29)
├── Products (21) - heroicon-o-cube-transparent
├── Categories (22) - heroicon-o-folder
├── Sub Categories (23) - heroicon-o-folder-open
├── Brands (24) - heroicon-o-building-storefront
├── Product Photos (25) - heroicon-o-photo
├── Specifications (26) - heroicon-o-document-text
├── Bundles (27) - heroicon-o-archive-box
└── Bundle Photos (28) - heroicon-o-rectangle-stack

💰 Sales & Transactions (30-39)
├── Transactions (31) - heroicon-o-banknotes
├── Promotions (32) - heroicon-o-ticket
└── Rental Includes (33) - heroicon-o-clipboard-document-list

⚙️ System & Settings (40-49)
└── API Keys (41) - heroicon-o-key
```

---

## 📝 IMPLEMENTATION STEPS

### **Step 1: Update Resource Files**
For each resource file (e.g., `SubCategoryResource.php`), update these properties:

```php
class SubCategoryResource extends Resource
{
    // Update these 4 properties:
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationGroup = 'Product Catalog';
    protected static ?string $navigationLabel = 'Sub Categories';
    protected static ?int $navigationSort = 23;
    
    // ... rest of the class remains the same
}
```

### **Step 2: Batch Update Command**
You can also use this PHP script to update multiple files at once:

```php
<?php

$updates = [
    'SubCategoryResource.php' => [
        'icon' => 'heroicon-o-folder-open',
        'group' => 'Product Catalog',
        'label' => 'Sub Categories',
        'sort' => 23,
    ],
    // Add other resources...
];

foreach ($updates as $file => $config) {
    $path = "app/Filament/Resources/{$file}";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Replace icon
        $content = preg_replace(
            '/protected static \?string \$navigationIcon = \'[^\']*\';/',
            "protected static ?string \$navigationIcon = '{$config['icon']}';",
            $content
        );
        
        // Replace group
        $content = preg_replace(
            '/protected static \?string \$navigationGroup = \'[^\']*\';/',
            "protected static ?string \$navigationGroup = '{$config['group']}';",
            $content
        );
        
        // Replace label
        $content = preg_replace(
            '/protected static \?string \$navigationLabel = \'[^\']*\';/',
            "protected static ?string \$navigationLabel = '{$config['label']}';",
            $content
        );
        
        // Replace sort
        $content = preg_replace(
            '/protected static \?int \$navigationSort = \d+;/',
            "protected static ?int \$navigationSort = {$config['sort']};",
            $content
        );
        
        file_put_contents($path, $content);
        echo "✅ Updated: {$file}\n";
    }
}
?>
```

---

## 🎯 FINAL RESULT

After implementing all changes, your Filament admin panel will have:

- ✨ **Consistent icon design** using Heroicons
- 📁 **Organized navigation groups** with logical grouping
- 🔢 **Proper sorting** with decade-based numbering
- 🏷️ **Clear labels** using plural forms
- 🎨 **Professional appearance** with semantic icons

The navigation will be clean, intuitive, and easy to use for administrators!
