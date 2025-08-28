<?php

/**
 * Script to update all EditPage files with HasSuccessNotification trait
 * Run this script from the project root directory
 */

$editPages = [
    'app/Filament/Resources/CategoryResource/Pages/EditCategory.php' => 'Category',
    'app/Filament/Resources/ProductResource/Pages/EditProduct.php' => 'Product',
    'app/Filament/Resources/TransactionResource/Pages/EditTransaction.php' => 'Transaction',
    'app/Filament/Resources/ApiKeyResource/Pages/EditApiKey.php' => 'API Key',
    'app/Filament/Resources/BundlingResource/Pages/EditBundling.php' => 'Bundling',
    'app/Filament/Resources/BundlingPhotoResource/Pages/EditBundlingPhoto.php' => 'Bundling Photo',
    'app/Filament/Resources/ProductPhotoResource/Pages/EditProductPhoto.php' => 'Product Photo',
    'app/Filament/Resources/ProductSpecificationResource/Pages/EditProductSpecification.php' => 'Product Specification',
    'app/Filament/Resources/PromoResource/Pages/EditPromo.php' => 'Promo',
    'app/Filament/Resources/RentalIncludeResource/Pages/EditRentalInclude.php' => 'Rental Include',
    'app/Filament/Resources/SubCategoryResource/Pages/EditSubCategory.php' => 'Sub Category',
    'app/Filament/Resources/UserPhoneNumberResource/Pages/EditUserPhoneNumber.php' => 'User Phone Number',
    'app/Filament/Resources/UserPhotoResource/Pages/EditUserPhoto.php' => 'User Photo',
];

foreach ($editPages as $filePath => $entityName) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        continue;
    }

    $content = file_get_contents($filePath);
    
    // Skip if already updated
    if (strpos($content, 'HasSuccessNotification') !== false) {
        echo "Already updated: $filePath\n";
        continue;
    }

    // Parse the current class
    preg_match('/namespace (.+);/', $content, $namespaceMatches);
    preg_match('/class (\w+) extends/', $content, $classMatches);
    
    if (!$namespaceMatches || !$classMatches) {
        echo "Could not parse: $filePath\n";
        continue;
    }

    $namespace = $namespaceMatches[1];
    $className = $classMatches[1];

    // Generate new content
    $newContent = "<?php

namespace $namespace;

use App\\Filament\\Concerns\\HasSuccessNotification;
use Filament\\Actions;
use Filament\\Resources\\Pages\\EditRecord;
use Filament\\Notifications\\Notification;

class $className extends EditRecord
{
    use HasSuccessNotification;
    
    protected static string \$resource = " . str_replace(['\\Pages\\Edit', 'Edit'], ['', ''], $className) . "Resource::class;

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
            ->title('$entityName berhasil diperbarui!')
            ->body('Data $entityName telah berhasil disimpan.')
            ->send();
    }
}
";

    // Write the updated content
    if (file_put_contents($filePath, $newContent)) {
        echo "Updated: $filePath\n";
    } else {
        echo "Failed to update: $filePath\n";
    }
}

echo "Update completed!\n";
?>
