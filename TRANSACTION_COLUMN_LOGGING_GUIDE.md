# 📊 TRANSACTIONRESOURCE TABLE COLUMN LOGGING GUIDE

## 🎯 OVERVIEW

I have added comprehensive logging to the TransactionResource table columns to help you debug and track the calculation logic. The logging covers all monetary columns and provides detailed insights into the decision-making process.

## 📝 LOGGED COLUMNS

### 1. **Grand Total Column** - `[GRAND_TOTAL_COLUMN]`
### 2. **Down Payment Column** - `[DOWN_PAYMENT_COLUMN]`  
### 3. **Remaining Payment Column** - `[REMAINING_PAYMENT_COLUMN]`
### 4. **Cancellation Fee Column** - `[CANCELLATION_FEE_COLUMN]`

## 🔍 LOG LEVELS USED

- **`Log::info()`**: Normal processing steps and results
- **`Log::debug()`**: Less critical information (color determination)
- **`Log::error()`**: Errors and exceptions during calculations

## 📋 LOG STRUCTURE FOR EACH COLUMN

### **GRAND TOTAL COLUMN LOGS**

```php
[GRAND_TOTAL_COLUMN] Processing transaction ID: {id}
- state_parameter: The $state parameter passed to formatStateUsing
- record_grand_total: Database value from $record->grand_total
- booking_status: Current booking status

[GRAND_TOTAL_COLUMN] Database value check for transaction {id}
- database_grand_total: Processed database value (int)
- will_use_fallback: Boolean indicating if fallback calculation needed

// If fallback is needed:
[GRAND_TOTAL_COLUMN] Using fallback calculation for transaction {id}
[GRAND_TOTAL_COLUMN] Fallback calculation result for transaction {id}
- calculated_grand_total: Final calculated value
- additional_services_total: Sum from JSON additional_services
- base_price: Base product/bundling price
- duration: Transaction duration
- discount_amount: Applied discount

// If using database value:
[GRAND_TOTAL_COLUMN] Using database value for transaction {id}
- database_value: The stored grand_total value

[GRAND_TOTAL_COLUMN] Final result for transaction {id}
- final_grand_total: The final amount used
- formatted_display: The "Rp X.XXX.XXX" formatted string
```

### **DOWN PAYMENT COLUMN LOGS**

```php
[DOWN_PAYMENT_COLUMN] Processing transaction ID: {id}
- state_parameter: The $state parameter (down_payment value)
- record_down_payment: Database value from $record->down_payment
- booking_status: Current booking status

[DOWN_PAYMENT_COLUMN] Database value processing for transaction {id}
- raw_state: Original $state parameter value
- processed_down_payment: Final integer value used
- is_null_or_zero: Boolean indicating if value is 0/null

[DOWN_PAYMENT_COLUMN] Final result for transaction {id}
- final_down_payment: The final amount used
- formatted_display: The "Rp X.XXX.XXX" formatted string
```

### **REMAINING PAYMENT COLUMN LOGS**

```php
[REMAINING_PAYMENT_COLUMN] Processing transaction ID: {id}
- state_parameter: The $state parameter
- record_grand_total: Database grand_total value
- record_down_payment: Database down_payment value
- record_remaining_payment: Database remaining_payment value (if exists)
- booking_status: Current booking status

[REMAINING_PAYMENT_COLUMN] Initial values for transaction {id}
- database_grand_total: Grand total from database
- database_down_payment: Down payment from database
- will_use_fallback_for_grand_total: Boolean for fallback need

// If fallback calculation needed:
[REMAINING_PAYMENT_COLUMN] Using fallback for grand_total calculation for transaction {id}
[REMAINING_PAYMENT_COLUMN] Fallback grand_total result for transaction {id}
- fallback_grand_total: Calculated grand total value
- additional_services_included: Sum from additional_services JSON

// If using database value:
[REMAINING_PAYMENT_COLUMN] Using database grand_total for transaction {id}
- database_grand_total: The stored value used

[REMAINING_PAYMENT_COLUMN] Calculation result for transaction {id}
- final_grand_total: Grand total value used in calculation
- final_down_payment: Down payment value used
- calculated_remaining: Raw calculation (grand_total - down_payment)
- final_remaining_payment: Final value after max(0, ...) applied
- is_fully_paid: Boolean indicating if remaining is 0

[REMAINING_PAYMENT_COLUMN] Final display result for transaction {id}
- display_result: Final display string ("LUNAS" or "Rp X.XXX.XXX")
- is_lunas: Boolean indicating if shows "LUNAS"

[REMAINING_PAYMENT_COLUMN] Color determination for transaction {id}
- remaining_payment: Value used for color logic
- color: Final color ('warning' or 'success')
```

### **CANCELLATION FEE COLUMN LOGS**

```php
[CANCELLATION_FEE_COLUMN] Processing transaction ID: {id}
- state_parameter: The $state parameter
- record_cancellation_fee: Database cancellation_fee value
- record_grand_total: Database grand_total value
- booking_status: Current booking status

// If using stored value:
[CANCELLATION_FEE_COLUMN] Using stored cancellation fee for transaction {id}
- stored_cancellation_fee: The database value used

[CANCELLATION_FEE_COLUMN] Final result (stored) for transaction {id}
- final_cancellation_fee: The stored value
- formatted_display: The formatted display string

// If calculating:
[CANCELLATION_FEE_COLUMN] No stored cancellation fee, calculating for transaction {id}

[CANCELLATION_FEE_COLUMN] Grand total check for transaction {id}
- database_grand_total: Grand total from database
- will_use_fallback_calculation: Boolean for fallback need

// If fallback needed:
[CANCELLATION_FEE_COLUMN] Using fallback grand total calculation for transaction {id}
[CANCELLATION_FEE_COLUMN] Fallback grand total result for transaction {id}
- fallback_grand_total: Calculated grand total
- includes_additional_services: Additional services sum

// If using database:
[CANCELLATION_FEE_COLUMN] Using database grand total for transaction {id}
- database_grand_total: The stored value

[CANCELLATION_FEE_COLUMN] Final result (calculated) for transaction {id}
- final_grand_total: Grand total used in calculation
- calculated_cancellation_fee: 50% calculation result
- percentage: Always "50%"
- formatted_display: Final display string
```

## 🚀 HOW TO USE THE LOGS

### **1. View Logs in Laravel**

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Filter by specific column
tail -f storage/logs/laravel.log | grep "GRAND_TOTAL_COLUMN"
tail -f storage/logs/laravel.log | grep "DOWN_PAYMENT_COLUMN"
tail -f storage/logs/laravel.log | grep "REMAINING_PAYMENT_COLUMN"
tail -f storage/logs/laravel.log | grep "CANCELLATION_FEE_COLUMN"

# Filter by specific transaction
tail -f storage/logs/laravel.log | grep "transaction ID: 123"
```

### **2. Debug Specific Issues**

**Grand Total Issues:**
```bash
# Check if fallback calculations are being used
grep "Using fallback calculation" storage/logs/laravel.log

# Check additional services inclusion
grep "additional_services_total" storage/logs/laravel.log
```

**Down Payment Issues:**
```bash
# Check for null/zero values
grep "is_null_or_zero" storage/logs/laravel.log

# Check database vs display consistency
grep "DOWN_PAYMENT_COLUMN.*Final result" storage/logs/laravel.log
```

**Remaining Payment Issues:**
```bash
# Check calculation logic
grep "Calculation result" storage/logs/laravel.log

# Check LUNAS display
grep "is_lunas.*true" storage/logs/laravel.log
```

### **3. Performance Analysis**

```bash
# Count fallback calculations (should be minimal)
grep -c "Using fallback" storage/logs/laravel.log

# Check for errors in calculations
grep "Error in.*calculation" storage/logs/laravel.log
```

## 🔧 TROUBLESHOOTING SCENARIOS

### **Scenario 1: Grand Total Not Including Additional Services**
Look for logs:
```
[GRAND_TOTAL_COLUMN] Fallback calculation result
- additional_services_total: Should show sum > 0
```

### **Scenario 2: Down Payment Showing Wrong Values**
Look for logs:
```
[DOWN_PAYMENT_COLUMN] Database value processing
- raw_state vs processed_down_payment comparison
```

### **Scenario 3: Remaining Payment Not Calculating Correctly**
Look for logs:
```
[REMAINING_PAYMENT_COLUMN] Calculation result
- Check final_grand_total and final_down_payment values
- Verify calculated_remaining vs final_remaining_payment
```

### **Scenario 4: Cancellation Fee Issues**
Look for logs:
```
[CANCELLATION_FEE_COLUMN] Final result (calculated)
- Check final_grand_total and calculated_cancellation_fee
- Should be exactly 50% of grand_total
```

## 📈 LOG ANALYSIS EXAMPLES

### **Successful Database Value Usage**
```
[2024-01-09 20:00:24] [GRAND_TOTAL_COLUMN] Using database value for transaction 123
[2024-01-09 20:00:24] [GRAND_TOTAL_COLUMN] Final result for transaction 123
    {"final_grand_total":1500000,"formatted_display":"Rp 1.500.000"}
```

### **Fallback Calculation Triggered**
```
[2024-01-09 20:00:25] [GRAND_TOTAL_COLUMN] Using fallback calculation for transaction 124
[2024-01-09 20:00:25] [GRAND_TOTAL_COLUMN] Fallback calculation result for transaction 124
    {"calculated_grand_total":2000000,"additional_services_total":500000,"base_price":1000000,"duration":1,"discount_amount":0}
```

### **LUNAS Status Detected**
```
[2024-01-09 20:00:26] [REMAINING_PAYMENT_COLUMN] Final display result for transaction 125
    {"display_result":"LUNAS","is_lunas":true}
```

## ⚙️ LOG CONFIGURATION

The logs are written to `storage/logs/laravel.log` by default. You can:

1. **Adjust Log Level** in `config/logging.php`
2. **Separate Log Channel** for transaction columns:

```php
// config/logging.php
'channels' => [
    'transaction_columns' => [
        'driver' => 'single',
        'path' => storage_path('logs/transaction_columns.log'),
        'level' => 'debug',
    ],
];
```

3. **Use Specific Channel** in column logic:
```php
\Log::channel('transaction_columns')->info('[GRAND_TOTAL_COLUMN] ...');
```

## 🎯 BENEFITS OF LOGGING

1. **Debug Calculation Issues**: Track exact values at each step
2. **Performance Monitoring**: Identify when fallback calculations are used
3. **Data Integrity**: Verify database vs calculated values
4. **User Experience**: Ensure correct display formatting
5. **Business Logic**: Validate additional services inclusion
6. **Error Tracking**: Catch and resolve calculation errors

The comprehensive logging will help you identify and resolve any issues with the TransactionResource table column calculations! 🚀
