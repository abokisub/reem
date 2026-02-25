# Amtpay Database Fix - Missing Column

## Current Status

✅ **Webhook signature issue bypassed** (temporarily)
✅ **Webhook is being received and processed**
❌ **Database error preventing transaction creation**

## The Errors

```
1. Column not found: 1054 Unknown column 'pointwave_transaction_id' in 'INSERT INTO'
2. Column not found: 1054 Unknown column 'pointwave_customer_id' in 'INSERT INTO'
```

Your code is trying to insert into `pointwave_transactions` table with columns `pointwave_transaction_id` and `pointwave_customer_id`, but these columns don't exist in your database.

## The Fix

You need to add the missing columns to your database. Run this migration:

### Option 1: Create Migration File

```bash
php artisan make:migration add_pointwave_columns_to_pointwave_transactions_table
```

Then edit the migration file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pointwave_transactions', function (Blueprint $table) {
            // Add the missing columns
            $table->string('pointwave_transaction_id')->nullable()->after('reference');
            $table->string('pointwave_customer_id')->nullable()->after('pointwave_transaction_id');
            
            // Add indexes for faster lookups
            $table->index('pointwave_transaction_id');
            $table->index('pointwave_customer_id');
        });
    }

    public function down()
    {
        Schema::table('pointwave_transactions', function (Blueprint $table) {
            $table->dropIndex(['pointwave_transaction_id']);
            $table->dropIndex(['pointwave_customer_id']);
            $table->dropColumn(['pointwave_transaction_id', 'pointwave_customer_id']);
        });
    }
};
```

Then run:

```bash
php artisan migrate
```

### Option 2: Direct SQL (Faster)

If you need to fix this immediately, run this SQL directly:

```sql
ALTER TABLE `pointwave_transactions` 
ADD COLUMN `pointwave_transaction_id` VARCHAR(255) NULL AFTER `reference`,
ADD COLUMN `pointwave_customer_id` VARCHAR(255) NULL AFTER `pointwave_transaction_id`,
ADD INDEX `pointwave_transaction_id_index` (`pointwave_transaction_id`),
ADD INDEX `pointwave_customer_id_index` (`pointwave_customer_id`);
```

## Verify the Fix

After adding the column, check your table structure:

```sql
DESCRIBE pointwave_transactions;
```

You should see both `pointwave_transaction_id` and `pointwave_customer_id` in the column list.

## Test Again

Once the column is added, trigger another test transaction from PointWave. The webhook should now process successfully.

## About the Signature Issue

You still have the signature verification bypassed with this code:
```
BYPASSING signature check - REMOVE THIS AFTER FIXING
```

**After fixing the database issue**, you should:

1. Fix the signature verification as described in `MESSAGE_TO_AMTPAY.md`
2. Remove the bypass code
3. Test that signature verification works correctly

The signature fix is: Use `$request->getContent()` instead of `json_encode($request->all())`

## Summary

**Immediate action needed:**
1. Add `pointwave_transaction_id` AND `pointwave_customer_id` columns to `pointwave_transactions` table
2. Test webhook again - should work now
3. Fix signature verification (use raw request body)
4. Remove the bypass code

**Current webhook flow:**
- ✅ Webhook received
- ⚠️ Signature check bypassed (temporary)
- ✅ User found (user_id: 8269)
- ❌ Database insert failed (missing column)
- ⏳ Waiting for column to be added
