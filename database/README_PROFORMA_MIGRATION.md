# Proforma Item Columns Migration

This migration adds two optional columns to the `proforma` table:
- `item_details_url` (VARCHAR(500)) - URL for item details upload file
- `item_info` (TEXT) - Item information: new/old item code, name, price stored as comment

Both columns are optional (NULL allowed).

## Migration File

- **Location**: `database/migrations/add_proforma_item_columns.sql`

## How to Run

### Option 1: Using PHP Migration Runner (Recommended)

From the project root directory:

```bash
php database/run-migration.php add_proforma_item_columns.sql
```

### Option 2: Using Batch Script (Windows)

1. Navigate to the `database` directory
2. Double-click `add-proforma-item-columns.bat` or run from command prompt:
   ```cmd
   cd database
   add-proforma-item-columns.bat
   ```

### Option 3: Using Shell Script (Linux/Mac)

1. Make the script executable (first time only):
   ```bash
   chmod +x database/add-proforma-item-columns.sh
   ```
2. Run the script:
   ```bash
   cd database
   ./add-proforma-item-columns.sh
   ```

### Option 4: Direct MySQL Command

```bash
mysql -h [host] -u [user] -p [database] < database/migrations/add_proforma_item_columns.sql
```

## What This Migration Does

1. Checks if `item_details_url` column exists, adds it if not
2. Checks if `item_info` column exists, adds it if not
3. Both columns are added after the `filename` column
4. Safe to run multiple times (won't duplicate columns)

## Usage in Code

After running the migration, you can use these columns when uploading proforma files:

### In API calls (POST to `api/update-files.php`):

```php
// For proforma type uploads
$_POST['item_details_url'] = 'path/to/item/details/file.pdf';  // Optional
$_POST['item_info'] = 'Item Code: ABC123, Name: Product Name, Price: $100, Type: New Item';  // Optional
```

### In fetch-files.php response:

The API will automatically return these fields when fetching proforma files:

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "url": "uploads/proforma/file.pdf",
      "filename": "file.pdf",
      "item_details_url": "uploads/item-details/details.pdf",
      "item_info": "Item Code: ABC123, Name: Product Name, Price: $100"
    }
  ]
}
```

## Notes

- The migration is idempotent (safe to run multiple times)
- If columns already exist, the migration will skip adding them
- Both columns are optional and can be NULL
- The migration uses prepared statements to safely check for existing columns

