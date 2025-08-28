<?php

// Array of EditPage files with their corresponding entity names
$files = [
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

// Template for updated EditPage
function generateEditPageContent($namespace, $className, $resourceClass, $entityName) {
    return "<?php

namespace $namespace;

use $resourceClass;
use App\\Filament\\Concerns\\HasSuccessNotification;
use Filament\\Actions;
use Filament\\Resources\\Pages\\EditRecord;
use Filament\\Notifications\\Notification;

class $className extends EditRecord
{
    use HasSuccessNotification;
    
    protected static string \$resource = " . basename($resourceClass) . "::class;

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
}

// Process each file
foreach ($files as $filePath => $entityName) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        continue;
    }

    // Read current file
    $content = file_get_contents($filePath);
    
    // Skip if already has the trait
    if (strpos($content, 'HasSuccessNotification') !== false) {
        echo "Already updated: $filePath\n";
        continue;
    }

    // Extract namespace and class name
    preg_match('/namespace (.+);/', $content, $namespaceMatch);
    preg_match('/class (\w+) extends/', $content, $classMatch);
    preg_match('/use (.+Resource);/', $content, $resourceMatch);
    
    if (!$namespaceMatch || !$classMatch || !$resourceMatch) {
        echo "Could not parse file: $filePath\n";
        continue;
    }

    $namespace = $namespaceMatch[1];
    $className = $classMatch[1];
    $resourceClass = $resourceMatch[1];

    // Generate new content
    $newContent = generateEditPageContent($namespace, $className, $resourceClass, $entityName);
    
    // Write updated file
    if (file_put_contents($filePath, $newContent)) {
        echo "✅ Updated: $filePath\n";
    } else {
        echo "❌ Failed to update: $filePath\n";
    }
}

echo "\nBatch update completed!\n";

?>
