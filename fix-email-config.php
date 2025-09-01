<?php

/**
 * Quick Fix Script for Email Configuration
 * This will temporarily disable email verification to prevent SMTP errors
 */

echo "📧 GPR Email Configuration Fix\n";
echo "==============================\n\n";

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "❌ .env file not found!\n";
    exit(1);
}

echo "📝 Reading current .env configuration...\n";
$envContent = file_get_contents($envFile);

// Define email configuration for different scenarios
$emailConfigs = [
    'log' => [
        'MAIL_MAILER' => 'log',
        'MAIL_LOG_CHANNEL' => 'single',
        'MAIL_FROM_ADDRESS' => 'noreply@globalphotorental.com',
        'MAIL_FROM_NAME' => 'Global Photo Rental',
    ],
    'sendmail' => [
        'MAIL_MAILER' => 'sendmail',
        'MAIL_SENDMAIL_PATH' => '/usr/sbin/sendmail -bs -i',
        'MAIL_FROM_ADDRESS' => 'noreply@globalphotorental.com',
        'MAIL_FROM_NAME' => 'Global Photo Rental',
    ],
    'smtp_gmail' => [
        'MAIL_MAILER' => 'smtp',
        'MAIL_HOST' => 'smtp.gmail.com',
        'MAIL_PORT' => '587',
        'MAIL_USERNAME' => 'your-email@gmail.com',
        'MAIL_PASSWORD' => 'your-app-password',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => 'noreply@globalphotorental.com',
        'MAIL_FROM_NAME' => 'Global Photo Rental',
    ]
];

// Use log driver as default (safest option)
$selectedConfig = $emailConfigs['log'];

echo "📧 Applying email configuration (log driver)...\n";

// Update or add email configuration
foreach ($selectedConfig as $key => $value) {
    if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $envContent)) {
        // Replace existing value
        $envContent = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $key . '=' . $value, $envContent);
        echo "✅ Updated: {$key}={$value}\n";
    } else {
        // Add new line
        $envContent .= "\n{$key}={$value}";
        echo "➕ Added: {$key}={$value}\n";
    }
}

// Write back to .env file
if (file_put_contents($envFile, $envContent)) {
    echo "\n✅ Email configuration updated successfully!\n";
    
    // Clear config cache
    if (function_exists('exec')) {
        echo "🧹 Clearing configuration cache...\n";
        exec('php artisan config:clear', $output, $return_var);
        if ($return_var === 0) {
            echo "✅ Configuration cache cleared\n";
        }
        
        // Cache configuration for production
        exec('php artisan config:cache', $output, $return_var);
        if ($return_var === 0) {
            echo "✅ Configuration cached\n";
        }
    }
    
    echo "\n🎉 Email error should now be resolved!\n";
    echo "📧 Emails will be logged to storage/logs/ instead of sent via SMTP\n";
    echo "🔄 User registration should now work without SMTP errors\n";
    
} else {
    echo "\n❌ Failed to update .env file!\n";
    echo "Please check file permissions\n";
    exit(1);
}

echo "\n📊 Current email configuration:\n";
echo "MAIL_MAILER=log (emails logged, not sent)\n";
echo "MAIL_FROM_ADDRESS=noreply@globalphotorental.com\n";
echo "MAIL_FROM_NAME=Global Photo Rental\n";

echo "\n💡 To configure proper SMTP later:\n";
echo "1. Edit .env file\n";
echo "2. Set MAIL_MAILER=smtp\n";
echo "3. Configure MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD\n";
echo "4. Run: php artisan config:cache\n";

?>
