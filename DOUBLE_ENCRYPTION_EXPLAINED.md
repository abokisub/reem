# Double-Encryption Issue Explained

## What Happened

Your webhook secrets are showing as:
```
s:70:"whsec_aad2319b0eb134b2f598ad63d4c2a7a2a4b054f42781b6599b92a7cc1a97fc68";
```

This is PHP serialized format, which means the encryption happened like this:

### Wrong Process (What Happened):
1. Original value: `whsec_abc123...`
2. Laravel encrypted it: `encrypted_blob_1`
3. Laravel's encrypted cast SERIALIZED it: `s:70:"whsec_abc123..."`
4. Then encrypted AGAIN: `encrypted_blob_2`
5. Stored in database: `encrypted_blob_2`

When you decrypt:
1. First decrypt: `s:70:"whsec_abc123..."` (serialized string)
2. This is what you see in the API response

### Correct Process (What Should Happen):
1. Original value: `whsec_abc123...`
2. Encrypt once: `encrypted_blob`
3. Store in database: `encrypted_blob`

When you decrypt:
1. Decrypt: `whsec_abc123...` (clean string)
2. This is what should appear in API response

## Why This Happened

Laravel's `encrypted` cast in the model does this:
- On save: `serialize()` then `encrypt()`
- On retrieve: `decrypt()` then `unserialize()`

But somewhere in your code, the value was already encrypted before being saved to the model, causing double-encryption.

## The Fix

The `fix_double_encryption.php` script:

1. Reads the encrypted value from database
2. Decrypts it once → gets serialized string `s:70:"whsec_..."`
3. Unserializes it → gets clean string `whsec_...`
4. Re-encrypts properly → stores correctly encrypted value
5. Now decrypt() returns clean string

## After the Fix

### API Response Before:
```json
{
  "webhook_secret": "s:70:\"whsec_aad2319b0eb134b2f598ad63d4c2a7a2a4b054f42781b6599b92a7cc1a97fc68\";"
}
```

### API Response After:
```json
{
  "webhook_secret": "whsec_aad2319b0eb134b2f598ad63d4c2a7a2a4b054f42781b6599b92a7cc1a97fc68"
}
```

## Technical Details

### Serialized Format Indicators:
- `s:70:"..."` - String with length 70
- `s:75:"..."` - String with length 75
- `a:2:{...}` - Array with 2 elements
- `i:123;` - Integer 123

If you see these patterns in your decrypted data, it means the data was serialized.

### Laravel's Encrypted Cast:
```php
// In Company model
protected $casts = [
    'webhook_secret' => 'encrypted',
];
```

This cast automatically:
- Encrypts on save (with serialization)
- Decrypts on retrieve (with unserialization)

### The Problem:
If you manually encrypt before saving:
```php
$company->webhook_secret = encrypt($value); // Manual encryption
$company->save(); // Cast encrypts AGAIN (double encryption)
```

### The Solution:
Let the cast handle encryption:
```php
$company->webhook_secret = $value; // Plain value
$company->save(); // Cast encrypts once
```

Or use raw DB queries:
```php
DB::table('companies')->update([
    'webhook_secret' => encrypt($value) // Manual encryption, no cast
]);
```

## Prevention

To prevent this in the future:

1. **Option A**: Remove encrypted cast, always use manual encryption
```php
// In model - remove cast
protected $casts = [
    // 'webhook_secret' => 'encrypted', // REMOVE THIS
];

// In code - always encrypt manually
DB::table('companies')->update([
    'webhook_secret' => encrypt($value)
]);
```

2. **Option B**: Keep encrypted cast, never encrypt manually
```php
// In model - keep cast
protected $casts = [
    'webhook_secret' => 'encrypted',
];

// In code - let cast handle it
$company->webhook_secret = $value; // Plain value
$company->save();
```

## Current Implementation

Your current code uses **Option A** (manual encryption):
- CompanyController uses `DB::table()` with `encrypt()`
- This bypasses the model cast
- This is correct and prevents double-encryption

The fix script corrects the old double-encrypted values to work with this approach.
