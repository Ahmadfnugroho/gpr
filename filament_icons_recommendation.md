# 🎨 FILAMENT RESOURCES - ICON & NAVIGATION RECOMMENDATIONS

## 📋 NAVIGATION GROUPS STRUCTURE

### 👥 **User Management** 
**Group Icon**: `heroicon-o-users` 
```php
protected static ?string $navigationGroup = 'User Management';
protected static ?int $navigationSort = 10;
```

### 🛍️ **Product Catalog**
**Group Icon**: `heroicon-o-cube-transparent`
```php
protected static ?string $navigationGroup = 'Product Catalog'; 
protected static ?int $navigationSort = 20;
```

### 💰 **Sales & Transactions**
**Group Icon**: `heroicon-o-banknotes`
```php
protected static ?string $navigationGroup = 'Sales & Transactions';
protected static ?int $navigationSort = 30;
```

### ⚙️ **System & Settings**
**Group Icon**: `heroicon-o-cog-6-tooth`
```php
protected static ?string $navigationGroup = 'System & Settings';
protected static ?int $navigationSort = 40;
```

---

## 🎯 DETAILED RESOURCE RECOMMENDATIONS

### 👥 **USER MANAGEMENT GROUP**

#### **UserResource** 
```php
protected static ?string $navigationIcon = 'heroicon-o-users';
protected static ?string $navigationGroup = 'User Management';
protected static ?string $navigationLabel = 'Users';
protected static ?int $navigationSort = 11;
```

#### **UserPhoneNumberResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-phone';
protected static ?string $navigationGroup = 'User Management';  
protected static ?string $navigationLabel = 'Phone Numbers';
protected static ?int $navigationSort = 12;
```

#### **UserPhotoResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-camera';
protected static ?string $navigationGroup = 'User Management';
protected static ?string $navigationLabel = 'User Photos';  
protected static ?int $navigationSort = 13;
```

---

### 🛍️ **PRODUCT CATALOG GROUP**

#### **ProductResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Products';
protected static ?int $navigationSort = 21;
```

#### **CategoryResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-folder';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Categories';
protected static ?int $navigationSort = 22;
```

#### **SubCategoryResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-folder-open';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Sub Categories';
protected static ?int $navigationSort = 23;
```

#### **BrandResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Brands';
protected static ?int $navigationSort = 24;
```

#### **ProductPhotoResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-photo';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Product Photos';
protected static ?int $navigationSort = 25;
```

#### **ProductSpecificationResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-document-text';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Specifications';
protected static ?int $navigationSort = 26;
```

#### **BundlingResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-archive-box';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Bundles';
protected static ?int $navigationSort = 27;
```

#### **BundlingPhotoResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
protected static ?string $navigationGroup = 'Product Catalog';
protected static ?string $navigationLabel = 'Bundle Photos';
protected static ?int $navigationSort = 28;
```

---

### 💰 **SALES & TRANSACTIONS GROUP**

#### **TransactionResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-banknotes';
protected static ?string $navigationGroup = 'Sales & Transactions';
protected static ?string $navigationLabel = 'Transactions';
protected static ?int $navigationSort = 31;
```

#### **PromoResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-ticket';
protected static ?string $navigationGroup = 'Sales & Transactions';
protected static ?string $navigationLabel = 'Promotions';
protected static ?int $navigationSort = 32;
```

#### **RentalIncludeResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
protected static ?string $navigationGroup = 'Sales & Transactions';
protected static ?string $navigationLabel = 'Rental Includes';
protected static ?int $navigationSort = 33;
```

---

### ⚙️ **SYSTEM & SETTINGS GROUP**

#### **ApiKeyResource**
```php
protected static ?string $navigationIcon = 'heroicon-o-key';
protected static ?string $navigationGroup = 'System & Settings';
protected static ?string $navigationLabel = 'API Keys';
protected static ?int $navigationSort = 41;
```

---

## 🎨 ALTERNATIVE ICON OPTIONS

### **Heroicon Outline Icons (Recommended)**
- `heroicon-o-users` - Users
- `heroicon-o-phone` - Phone  
- `heroicon-o-camera` - Camera
- `heroicon-o-cube-transparent` - Products
- `heroicon-o-folder` - Categories
- `heroicon-o-folder-open` - Sub Categories
- `heroicon-o-building-storefront` - Brands
- `heroicon-o-photo` - Photos
- `heroicon-o-document-text` - Documents/Specs
- `heroicon-o-archive-box` - Bundles
- `heroicon-o-rectangle-stack` - Stacks/Collections
- `heroicon-o-banknotes` - Money/Transactions
- `heroicon-o-ticket` - Tickets/Promos
- `heroicon-o-clipboard-document-list` - Lists
- `heroicon-o-key` - Security/API Keys

### **Alternative Mini Icons (if needed)**
- `heroicon-m-users`
- `heroicon-m-phone`
- `heroicon-m-camera`
- `heroicon-m-cube-transparent`
- `heroicon-m-folder`
- `heroicon-m-building-storefront`
- etc.

### **Solid Icons (for emphasis)**
- `heroicon-s-users`
- `heroicon-s-cube`
- `heroicon-s-folder`
- etc.

---

## 🚀 IMPLEMENTATION PRIORITY

### **High Priority** (Core Business Resources)
1. ✅ TransactionResource
2. ✅ ProductResource  
3. ✅ UserResource
4. ✅ CategoryResource
5. ✅ BrandResource

### **Medium Priority**
6. SubCategoryResource
7. BundlingResource
8. PromoResource
9. ProductPhotoResource
10. UserPhoneNumberResource

### **Low Priority** (Support Resources)  
11. ProductSpecificationResource
12. RentalIncludeResource
13. UserPhotoResource
14. BundlingPhotoResource
15. ApiKeyResource

---

## 💡 IMPLEMENTATION TIPS

### **Navigation Sorting Best Practice:**
```php
// Use decade-based sorting for groups
User Management: 10-19
Product Catalog: 20-29  
Sales & Transactions: 30-39
System & Settings: 40-49
```

### **Consistent Labeling:**
```php
// Use plural forms for resource labels
'Users' instead of 'User'
'Products' instead of 'Product'
'Categories' instead of 'Category'
```

### **Group Color Coding (Optional):**
```php
// You can add custom CSS classes for group styling
User Management → Blue theme
Product Catalog → Green theme  
Sales & Transactions → Orange theme
System & Settings → Gray theme
```
