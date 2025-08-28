#!/bin/bash

# Files that need to be updated with their entity names
declare -A files=(
    ["app/Filament/Resources/ApiKeyResource/Pages/EditApiKey.php"]="API Key"
    ["app/Filament/Resources/PromoResource/Pages/EditPromo.php"]="Promo"
    ["app/Filament/Resources/SubCategoryResource/Pages/EditSubCategory.php"]="Sub Category"
    ["app/Filament/Resources/ProductPhotoResource/Pages/EditProductPhoto.php"]="Product Photo"
    ["app/Filament/Resources/ProductSpecificationResource/Pages/EditProductSpecification.php"]="Product Specification"
    ["app/Filament/Resources/RentalIncludeResource/Pages/EditRentalInclude.php"]="Rental Include"
    ["app/Filament/Resources/UserPhoneNumberResource/Pages/EditUserPhoneNumber.php"]="User Phone Number"
    ["app/Filament/Resources/UserPhotoResource/Pages/EditUserPhoto.php"]="User Photo"
    ["app/Filament/Resources/BundlingPhotoResource/Pages/EditBundlingPhoto.php"]="Bundling Photo"
)

# Function to update a single file
update_file() {
    local file_path="$1"
    local entity_name="$2"
    
    if [ ! -f "$file_path" ]; then
        echo "File not found: $file_path"
        return 1
    fi
    
    # Check if already updated
    if grep -q "HasSuccessNotification" "$file_path"; then
        echo "Already updated: $file_path"
        return 0
    fi
    
    # Extract class name and namespace
    local class_name=$(grep -o "class [A-Za-z]* extends" "$file_path" | cut -d' ' -f2)
    local namespace=$(grep -o "namespace [^;]*" "$file_path" | cut -d' ' -f2)
    local resource=$(grep -o "use [^;]*Resource" "$file_path" | cut -d' ' -f2)
    
    if [ -z "$class_name" ] || [ -z "$namespace" ] || [ -z "$resource" ]; then
        echo "Could not parse: $file_path"
        return 1
    fi
    
    # Create new file content
    cat > "$file_path" << EOF
<?php

namespace $namespace;

use $resource;
use App\\Filament\\Concerns\\HasSuccessNotification;
use Filament\\Actions;
use Filament\\Resources\\Pages\\EditRecord;
use Filament\\Notifications\\Notification;

class $class_name extends EditRecord
{
    use HasSuccessNotification;
    
    protected static string \$resource = $(basename "$resource")::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('$entity_name berhasil diperbarui!')
            ->body('Data $entity_name telah berhasil disimpan.')
            ->send();
    }
}
EOF
    
    echo "✅ Updated: $file_path"
}

# Update all files
for file_path in "${!files[@]}"; do
    entity_name="${files[$file_path]}"
    update_file "$file_path" "$entity_name"
done

echo ""
echo "All remaining EditPage files have been updated!"
