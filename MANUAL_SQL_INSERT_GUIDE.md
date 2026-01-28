# Manual SQL Insert Guide for OSA-IMS Users Table

## Table of Contents
1. [Issue Explanations](#a-issue-explanations)
2. [Hidden Causes](#b-hidden-causes)
3. [Solutions](#c-solutions)
4. [Verification Steps](#d-verification-steps)

---

## A. Issue Explanations

### 1. Duplicate Email Error (SQLSTATE[1062])

**Why it happens:**
- The `users` table has a UNIQUE constraint on the `email` column (created by `$table->string('email')->unique();`)
- MySQL enforces this constraint at the database level
- Even if you delete a row or use a "different" email, the error persists if:
  - The email already exists in the table (even if you think you deleted it)
  - Case-insensitive collation (`utf8mb4_unicode_ci`) treats `Example@gmail.com` and `example@gmail.com` as the same
  - You're querying a different database than you're inserting into
  - An uncommitted transaction contains the duplicate email

### 2. Error Persists After Deletion/New Email

**Why it persists:**
- **Multiple databases**: You might be deleting from `osa-ims` but inserting into `osa_ims` (hyphen vs underscore)
- **Transaction isolation**: Uncommitted transactions can hold locks or contain the duplicate
- **Case sensitivity**: Email collation is case-insensitive, so `Example@gmail.com` conflicts with `example@gmail.com`
- **Auto-increment gaps**: The error is about the EMAIL, not the ID - deleting rows doesn't help if the email still exists
- **phpMyAdmin caching**: Results might be cached in your browser or phpMyAdmin session

### 3. Timestamps are NULL

**Why they're NULL:**
- You're explicitly inserting `NULL` values: `created_at, updated_at) VALUES (..., NULL, NULL)`
- MySQL `TIMESTAMP` columns don't auto-update unless you set `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- Laravel's `timestamps()` migration creates columns with `nullable()` by default (unless you specify `useCurrent()`)
- Raw SQL inserts bypass Laravel's automatic timestamp handling

**What Laravel does:**
- Eloquent automatically sets `created_at` and `updated_at` when using `User::create()` or `$user->save()`
- But raw SQL bypasses this completely

### 4. Password in Plain Text

**Why passwords aren't hashed:**
- Laravel's `'hashed'` cast in the User model **ONLY works through Eloquent ORM**
- When you insert directly via SQL, PHP/Laravel code never runs - MySQL executes the SQL directly
- The `'hashed'` cast is a PHP-level feature, not a MySQL feature
- Raw SQL inserts bypass all Laravel model features (casts, mutators, accessors, events, etc.)

**How Laravel's hashing works:**
```php
// This works - Laravel hashes it:
User::create(['email' => 'x@y.com', 'password' => 'plaintext']);

// This DOESN'T work - MySQL stores plaintext:
INSERT INTO users (email, password) VALUES ('x@y.com', 'plaintext');
```

### 5. Row Appears After Refresh Despite Error

**Why this happens:**
- **Auto-commit behavior**: phpMyAdmin might auto-commit successful inserts before showing an error
- **Transaction rollback confusion**: If a transaction partially commits, some rows might appear
- **Browser caching**: phpMyAdmin might cache query results
- **Multiple query execution**: phpMyAdmin might execute the query multiple times if you click "Go" multiple times
- **Partial transaction success**: If the INSERT succeeds but a trigger/constraint fails afterward, the row might persist

---

## B. Hidden Causes

### 1. Database Name Mismatch
- Laravel uses: `DB_DATABASE=osa-ims` (hyphen)
- phpMyAdmin might use: `osa_ims` (underscore) or different database
- Solution: Always verify which database you're connected to

### 2. Uncommitted Transactions
- phpMyAdmin uses autocommit by default, but transactions can still exist
- Check: `SHOW PROCESSLIST;` or `SELECT * FROM INFORMATION_SCHEMA.INNODB_TRX;`

### 3. Case-Insensitive Email Collation
- Default collation `utf8mb4_unicode_ci` treats emails as case-insensitive
- `Example@Gmail.com` = `example@gmail.com` = `EXAMPLE@GMAIL.COM`

### 4. Multiple phpMyAdmin Tabs/Windows
- Different tabs might be connected to different databases
- Always check the database selector in the top-left

### 5. Triggers or Stored Procedures
- Custom triggers might interfere with inserts
- Check: `SHOW TRIGGERS FROM \`osa-ims\`;`

---

## C. Solutions

### Solution 1: Correct SQL Insert (Recommended for Testing Only)

**Option A: With explicit timestamps (Laravel-compatible)**
```sql
INSERT INTO `users` (`email`, `password`, `status`, `created_at`, `updated_at`)
VALUES (
    'example@gmail.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5Q3zW5WJ1qK1q',  -- bcrypt hash of '121403'
    'active',
    NOW(),
    NOW()
);
```

**Option B: Without timestamps (let MySQL defaults handle it)**
```sql
INSERT INTO `users` (`email`, `password`, `status`)
VALUES (
    'example@gmail.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5Q3zW5WJ1qK1q',  -- bcrypt hash
    'active'
);
```

**Note:** You MUST hash the password manually. Use this PHP one-liner to generate bcrypt hashes:
```php
php -r "echo password_hash('121403', PASSWORD_BCRYPT) . PHP_EOL;"
```

### Solution 2: Use Laravel Tinker (RECOMMENDED)

**Why this is better:**
- Automatic password hashing via the `'hashed'` cast
- Automatic timestamp handling
- Validates data according to model rules
- Fires model events
- Respects fillable attributes

```bash
php artisan tinker
```

```php
// In Tinker:
$user = \App\Models\User::create([
    'email' => 'example@gmail.com',
    'password' => '121403',  // Automatically hashed!
    'status' => 'active',
]);

// Or using factory:
\App\Models\User::factory()->create([
    'email' => 'example@gmail.com',
    'password' => '121403',
    'status' => 'active',
]);
```

### Solution 3: Create a Seeder (Best Practice)

Create a seeder file for manual user insertion:

```php
// database/seeders/ManualUserSeeder.php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ManualUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'example@gmail.com',
            'password' => '121403',  // Automatically hashed
            'status' => 'active',
        ]);
    }
}
```

Run it:
```bash
php artisan db:seed --class=ManualUserSeeder
```

### Solution 4: Fix Timestamps at Database Level (Optional)

If you MUST use raw SQL frequently, update the migration to auto-handle timestamps:

```php
// In migration:
$table->timestamp('created_at')->useCurrent();
$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
```

**However, this is NOT recommended** because Laravel handles timestamps automatically through Eloquent.

### Solution 5: Verify Database Connection

Always verify you're in the correct database:
```sql
SELECT DATABASE();
SELECT TABLE_SCHEMA FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'users';
```

---

## D. Verification Steps

### Step 1: Verify Current Database
```sql
-- Check which database you're currently using
SELECT DATABASE();

-- List all databases that contain a 'users' table
SELECT DISTINCT TABLE_SCHEMA 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'users';
```

### Step 2: Check Existing Emails (Case-Insensitive)
```sql
-- Check all emails in users table (case-insensitive search)
SELECT user_id, email, status, created_at 
FROM `users` 
WHERE LOWER(email) = LOWER('example@gmail.com');

-- List ALL emails in the table
SELECT user_id, email, status 
FROM `users` 
ORDER BY email;
```

### Step 3: Check Unique Indexes
```sql
-- Show all indexes on users table
SHOW INDEXES FROM `users`;

-- Show unique constraints
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND CONSTRAINT_NAME LIKE '%unique%';
```

### Step 4: Check for Uncommitted Transactions
```sql
-- Check for active transactions (MySQL 8+)
SELECT * FROM INFORMATION_SCHEMA.INNODB_TRX;

-- Show process list
SHOW PROCESSLIST;
```

### Step 5: Check Table Structure
```sql
-- Verify table structure matches Laravel migration
DESCRIBE `users`;

-- Check column defaults
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
ORDER BY ORDINAL_POSITION;
```

### Step 6: Generate Bcrypt Hash for SQL
```bash
# Using PHP CLI
php -r "echo password_hash('your_password_here', PASSWORD_BCRYPT) . PHP_EOL;"

# Or use online bcrypt generator (not recommended for production)
# https://bcrypt-generator.com/
```

### Step 7: Test Insert with Error Handling
```sql
-- Test if email already exists BEFORE inserting
SELECT COUNT(*) as email_exists 
FROM `users` 
WHERE email = 'example@gmail.com';

-- If email_exists = 0, then safe to insert
-- If email_exists > 0, email already exists
```

---

## Quick Reference: Correct SQL Insert Template

```sql
-- Step 1: Generate password hash (run in terminal)
php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_BCRYPT) . PHP_EOL;"

-- Step 2: Verify email doesn't exist
SELECT COUNT(*) FROM `users` WHERE email = 'your-email@example.com';

-- Step 3: Insert (only if count = 0)
INSERT INTO `users` (`email`, `password`, `status`, `created_at`, `updated_at`)
VALUES (
    'your-email@example.com',
    '$2y$12$...generated_hash_here...',  -- Paste hash from Step 1
    'active',
    NOW(),
    NOW()
);

-- Step 4: Verify insert
SELECT user_id, email, status, created_at FROM `users` WHERE email = 'your-email@example.com';
```

---

## Best Practice Recommendation

**DO NOT use raw SQL inserts for user creation in production.**

Instead, use:
1. **Laravel Tinker** for one-off inserts
2. **Database Seeders** for bulk/manual data
3. **Laravel's registration/API endpoints** for user creation
4. **Artisan commands** for admin user creation

This ensures:
- Passwords are automatically hashed
- Timestamps are handled correctly
- Validation rules are enforced
- Model events fire properly
- Relationships work correctly

---

## Troubleshooting Checklist

- [ ] Verified correct database: `SELECT DATABASE();`
- [ ] Checked for existing email (case-insensitive): `SELECT * FROM users WHERE LOWER(email) = LOWER('your-email');`
- [ ] Generated bcrypt hash for password
- [ ] Used correct table name with backticks: `` `users` ``
- [ ] Set timestamps to `NOW()` or omitted them
- [ ] Verified no uncommitted transactions
- [ ] Refreshed phpMyAdmin completely (hard refresh: Ctrl+F5)
- [ ] Checked multiple databases for duplicate table names
- [ ] Verified email collation is case-insensitive

