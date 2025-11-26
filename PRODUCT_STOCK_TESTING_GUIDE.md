# Product Stock System - Testing Guide

## Pre-Testing Setup

1. **Database Migration**
   ```bash
   cd f:\laragon\www\veterinary-cms
   php artisan migrate --path=database/migrations/2025_11_26_000001_create_product_stock_table.php
   php artisan migrate --path=database/migrations/2025_11_26_000002_create_product_damage_pullout_table.php
   ```

2. **Optional: Migrate Existing Data**
   ```bash
   # Run the SQL script in database/migrations/data_migration_product_stock.sql
   # This will create initial batches for existing products
   ```

## Test Scenarios

### 1. Add New Product (Without Initial Stock)

**Steps**:
1. Navigate to Products, Services & Equipment page
2. Click "Add Product"
3. Fill in:
   - Product Name: "Test Medicine A"
   - Category: "Prescription Medicines"
   - Type: "Consumable"
   - Price: "500.00"
   - Reorder Level: "10" (required, must be >= 0)
   - Description: "Test product"
4. Click "Save"

**Expected Result**:
- ✅ Product created successfully
- ✅ Message: "Product added successfully! Use Add Stock to add inventory batches."
- ✅ Product shows in list with 0 stock
- ✅ No expiry date shown
- ✅ Cannot set reorder level to -1 (validation error)

### 2. Add Stock Batch to Product

**Steps**:
1. Find the product in the list
2. Click "Add Stock" button
3. Fill in:
   - Batch Number: "BATCH-2025-001"
   - Quantity: "100"
   - Expiry Date: Select a future date (e.g., "2026-12-31")
   - Notes: "From Supplier A, PO#12345"
4. Click "Add Batch"

**Expected Result**:
- ✅ Success message: "Stock batch added successfully! Added 100 units (Batch: BATCH-2025-001)"
- ✅ Product's available stock updates to 100
- ✅ Stock batch record created in `product_stock` table
- ✅ Inventory transaction recorded

**Database Verification**:
```sql
SELECT * FROM product_stock WHERE batch = 'BATCH-2025-001';
SELECT * FROM tbl_inventory_transactions WHERE prod_id = [product_id] ORDER BY created_at DESC LIMIT 1;
```

### 3. Add Multiple Stock Batches

**Steps**:
1. Click "Add Stock" again for the same product
2. Fill in:
   - Batch Number: "BATCH-2025-002"
   - Quantity: "50"
   - Expiry Date: "2026-06-30"
   - Notes: "From Supplier B"
3. Click "Add Batch"

**Expected Result**:
- ✅ Success message with new batch info
- ✅ Product's available stock updates to 150 (100 + 50)
- ✅ Two separate batch records in database

**Database Verification**:
```sql
SELECT batch, quantity, expire_date, note 
FROM product_stock 
WHERE stock_prod_id = [product_id]
ORDER BY expire_date;
```

### 4. View Product Details (Stock History)

**Steps**:
1. Click "View Details" icon/button on the product
2. Check the "Stock Batches" section

**Expected Result**:
- ✅ Shows all stock batches with:
  - Batch number
  - Original quantity
  - Available quantity
  - Expiry date
  - Notes
  - Created date
- ✅ Shows "Complete Stock Movement History" section
- ✅ Lists all stock additions with expiry dates
- ✅ No damage/pullout records yet (empty section)

### 5. Record Damage from Specific Batch

**Steps**:
1. Click "Damage/Pullout" button on the product
2. Select batch from dropdown: "BATCH-2025-001 | Available: 100/100 | Exp: 12/31/2026"
3. Fill in:
   - Damaged Quantity: "5"
   - Pullout Quantity: "0"
   - Reason: "Broken bottles during transport"
4. Click "Record"

**Expected Result**:
- ✅ Success message: "Batch 'BATCH-2025-001': Damaged: 5 | New available stock: 145"
- ✅ Product's available stock updates to 145 (was 150)
- ✅ Batch BATCH-2025-001 now shows: Available: 95/100
- ✅ Damage record created in `product_damage_pullout` table
- ✅ Inventory transaction recorded as negative quantity

**Database Verification**:
```sql
SELECT * FROM product_damage_pullout WHERE stock_id = [batch_id];
SELECT quantity, quantity - COALESCE((
    SELECT SUM(damage_quantity + pullout_quantity)
    FROM product_damage_pullout
    WHERE stock_id = product_stock.id
), 0) as available
FROM product_stock WHERE id = [batch_id];
```

### 6. Record Pullout from Specific Batch

**Steps**:
1. Click "Damage/Pullout" button
2. Select batch: "BATCH-2025-002"
3. Fill in:
   - Damaged Quantity: "0"
   - Pullout Quantity: "10"
   - Reason: "Quality control testing"
4. Click "Record"

**Expected Result**:
- ✅ Success message with pullout info
- ✅ Product's available stock updates to 135 (was 145)
- ✅ Batch BATCH-2025-002 shows: Available: 40/50
- ✅ Pullout record created
- ✅ Cumulative prod_pullout field updated

### 7. Stock Expiration Handling

**Test Setup**:
Create a batch with past expiry date:
```sql
INSERT INTO product_stock (stock_prod_id, batch, quantity, expire_date, note, created_by, created_at, updated_at)
VALUES ([product_id], 'EXPIRED-BATCH', 20, '2024-01-01', 'Test expired batch', 1, NOW(), NOW());
```

**Steps**:
1. Refresh the product list
2. Check the product's available stock
3. Click "View Details"
4. Try to select the expired batch in Damage/Pullout modal

**Expected Result**:
- ✅ Product's available stock does NOT include expired batch (still 135)
- ✅ View Details shows expired batch marked as "(EXPIRED)"
- ✅ Expired batch is disabled in Damage/Pullout dropdown
- ✅ Cannot select expired batch for damage/pullout

### 8. Validation Tests

#### A. Add Stock with Past Expiry Date
**Steps**:
1. Click "Add Stock"
2. Enter batch info but set expiry date to yesterday
3. Click "Add Batch"

**Expected Result**:
- ❌ Validation error: "The new expiry field must be a date after today"

#### B. Select Batch with Insufficient Quantity
**Steps**:
1. Click "Damage/Pullout"
2. Select a batch
3. Enter damage quantity > available quantity
4. Click "Record"

**Expected Result**:
- ❌ Error: "Insufficient stock in batch '[batch_name]'! Available: X, Requested: Y"

#### C. No Reason Provided
**Steps**:
1. Click "Damage/Pullout"
2. Select batch and enter quantities
3. Leave reason blank
4. Click "Record"

**Expected Result**:
- ❌ Validation error: "The reason field is required"

#### D. No Batch Selected
**Steps**:
1. Click "Damage/Pullout"
2. Don't select any batch
3. Enter quantities and reason
4. Click "Record"

**Expected Result**:
- ❌ Validation error: "The stock id field is required"

#### E. Negative Reorder Level
**Steps**:
1. Click "Add Product"
2. Try to set reorder level to -1
3. Submit form

**Expected Result**:
- ❌ HTML5 validation prevents submission (min="0")
- ❌ Or backend validation error if bypassed

### 9. Complete Stock Movement History

**Steps**:
1. Add 2-3 stock batches at different times
2. Record damage/pullout from different batches
3. Click "View Product Details"
4. Check "Complete Stock Movement History" section

**Expected Result**:
- ✅ Shows chronological list of ALL stock movements:
  - Each stock addition with batch number and expiry date
  - Each damage record with batch reference
  - Each pullout record with batch reference
  - Transaction date and time
  - Created by (user)
- ✅ Can trace which batch each item came from
- ✅ Can see expiry dates for each batch added

### 10. Low Stock Alert Verification

**Steps**:
1. Create product with reorder level = 20
2. Add stock batches totaling 25 units
3. Record damage/pullout reducing available stock to 15

**Expected Result**:
- ✅ Product shows low stock warning
- ✅ Low stock calculation uses only non-expired available stock
- ✅ Expired stock doesn't count toward low stock threshold

### 11. Service Usage Integration Test

**Prerequisites**: Product is linked to a service (e.g., vaccination)

**Steps**:
1. Perform a service that uses the product
2. Check product's available stock
3. View Product Details

**Expected Result**:
- ✅ Stock automatically deducted (FIFO - from oldest batch first)
- ✅ Inventory transaction recorded as "service_usage"
- ✅ Can trace which batch was used

### 12. View All Product Batches

**Steps**:
1. Go to product list
2. Click "Inventory Overview" button (if available)
3. Or click "View Details" on any product

**Expected Result**:
- ✅ Shows all batches for all products
- ✅ Color coding:
  - Red: Expired
  - Yellow: Expiring soon (within 30 days)
  - Green: Good
- ✅ Shows available vs total quantity per batch
- ✅ Shows damage/pullout summary per batch

## Database Integrity Checks

Run these queries after testing:

```sql
-- Check stock calculations are correct
SELECT 
    p.prod_id,
    p.prod_name,
    p.prod_stocks as recorded_stock,
    SUM(CASE WHEN ps.expire_date >= CURDATE() 
        THEN ps.quantity - COALESCE((
            SELECT SUM(pdp.damage_quantity + pdp.pullout_quantity)
            FROM product_damage_pullout pdp
            WHERE pdp.stock_id = ps.id
        ), 0)
        ELSE 0
    END) as calculated_available_stock,
    SUM(ps.quantity) as total_batch_quantity,
    p.prod_damaged,
    p.prod_pullout
FROM tbl_prod p
LEFT JOIN product_stock ps ON ps.stock_prod_id = p.prod_id
WHERE p.prod_id IN (SELECT DISTINCT stock_prod_id FROM product_stock)
GROUP BY p.prod_id
HAVING recorded_stock != calculated_available_stock;

-- Check for orphaned records
SELECT * FROM product_stock 
WHERE stock_prod_id NOT IN (SELECT prod_id FROM tbl_prod);

SELECT * FROM product_damage_pullout 
WHERE pd_prod_id NOT IN (SELECT prod_id FROM tbl_prod)
   OR stock_id NOT IN (SELECT id FROM product_stock);

-- Check damage/pullout totals
SELECT 
    ps.id,
    ps.batch,
    ps.quantity,
    COALESCE(SUM(pdp.damage_quantity + pdp.pullout_quantity), 0) as total_affected,
    ps.quantity - COALESCE(SUM(pdp.damage_quantity + pdp.pullout_quantity), 0) as available
FROM product_stock ps
LEFT JOIN product_damage_pullout pdp ON pdp.stock_id = ps.id
GROUP BY ps.id;
```

## Performance Tests

1. **Large Batch Count**
   - Add 50+ batches to a single product
   - Check page load time
   - Check View Details load time

2. **High Transaction Volume**
   - Add/remove stock 100+ times
   - Check stock movement history loads correctly
   - Verify calculations remain accurate

## Rollback Plan

If issues are found:

1. **Keep new tables but revert code**:
   ```bash
   git checkout HEAD -- app/Http/Controllers/ProdServEquipController.php
   git checkout HEAD -- resources/views/prodServEquip.blade.php
   git checkout HEAD -- app/Models/Product.php
   ```

2. **Remove new tables**:
   ```sql
   DROP TABLE IF EXISTS product_damage_pullout;
   DROP TABLE IF EXISTS product_stock;
   ```

3. **Rollback migrations**:
   ```bash
   php artisan migrate:rollback --step=2
   ```

## Success Criteria

✅ All 12 test scenarios pass
✅ No database integrity issues
✅ Stock calculations are accurate
✅ Expired stock properly excluded
✅ Complete audit trail maintained
✅ Performance is acceptable
✅ UI is intuitive and error-free
✅ Validation prevents data corruption
