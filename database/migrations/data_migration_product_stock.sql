-- ============================================================================
-- DATA MIGRATION SCRIPT FOR PRODUCT STOCK SYSTEM
-- ============================================================================
-- Purpose: Migrate existing product stock data to the new batch-based system
-- Run this AFTER migrating the new tables
-- ============================================================================

-- Step 1: Create initial stock batches for all existing products with stock
-- ============================================================================
INSERT INTO product_stock (
    stock_prod_id,
    batch,
    quantity,
    expire_date,
    note,
    created_by,
    created_at,
    updated_at
)
SELECT 
    prod_id,
    CONCAT('INITIAL-', LPAD(prod_id, 5, '0')) as batch,
    prod_stocks as quantity,
    COALESCE(prod_expiry, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) as expire_date,
    'Migrated from existing stock during system upgrade' as note,
    1 as created_by, -- Change to appropriate system user ID
    NOW() as created_at,
    NOW() as updated_at
FROM tbl_prod
WHERE prod_stocks > 0
AND NOT EXISTS (
    SELECT 1 FROM product_stock ps 
    WHERE ps.stock_prod_id = tbl_prod.prod_id 
    AND ps.batch = CONCAT('INITIAL-', LPAD(tbl_prod.prod_id, 5, '0'))
);

-- Step 2: Create damage/pullout records for existing damaged/pullout quantities
-- ============================================================================
INSERT INTO product_damage_pullout (
    pd_prod_id,
    stock_id,
    pullout_quantity,
    damage_quantity,
    reason,
    created_by,
    created_at,
    updated_at
)
SELECT 
    p.prod_id,
    ps.id as stock_id,
    COALESCE(p.prod_pullout, 0) as pullout_quantity,
    COALESCE(p.prod_damaged, 0) as damage_quantity,
    CONCAT(
        'Historical data migrated from old system. ',
        'Damaged: ', COALESCE(p.prod_damaged, 0), ', ',
        'Pullout: ', COALESCE(p.prod_pullout, 0)
    ) as reason,
    1 as created_by, -- Change to appropriate system user ID
    NOW() as created_at,
    NOW() as updated_at
FROM tbl_prod p
JOIN product_stock ps ON ps.stock_prod_id = p.prod_id 
    AND ps.batch = CONCAT('INITIAL-', LPAD(p.prod_id, 5, '0'))
WHERE (COALESCE(p.prod_pullout, 0) + COALESCE(p.prod_damaged, 0)) > 0
AND NOT EXISTS (
    SELECT 1 FROM product_damage_pullout pdp 
    WHERE pdp.pd_prod_id = p.prod_id 
    AND pdp.stock_id = ps.id
);

-- Step 3: Verify the migration
-- ============================================================================
-- Check product stock totals match
SELECT 
    'Stock Total Verification' as check_type,
    COUNT(*) as total_products,
    SUM(CASE 
        WHEN p.prod_stocks = COALESCE(
            (SELECT SUM(ps.quantity - COALESCE((
                SELECT SUM(pdp.damage_quantity + pdp.pullout_quantity)
                FROM product_damage_pullout pdp
                WHERE pdp.stock_id = ps.id
            ), 0))
            FROM product_stock ps
            WHERE ps.stock_prod_id = p.prod_id
            AND ps.expire_date >= CURDATE()
        ), 0)
        THEN 1 ELSE 0 
    END) as matching_products,
    SUM(CASE 
        WHEN p.prod_stocks != COALESCE(
            (SELECT SUM(ps.quantity - COALESCE((
                SELECT SUM(pdp.damage_quantity + pdp.pullout_quantity)
                FROM product_damage_pullout pdp
                WHERE pdp.stock_id = ps.id
            ), 0))
            FROM product_stock ps
            WHERE ps.stock_prod_id = p.prod_id
            AND ps.expire_date >= CURDATE()
        ), 0)
        THEN 1 ELSE 0 
    END) as mismatched_products
FROM tbl_prod p
WHERE p.prod_stocks > 0;

-- Show products with mismatches (if any)
SELECT 
    p.prod_id,
    p.prod_name,
    p.prod_stocks as current_stock,
    COALESCE(
        (SELECT SUM(ps.quantity - COALESCE((
            SELECT SUM(pdp.damage_quantity + pdp.pullout_quantity)
            FROM product_damage_pullout pdp
            WHERE pdp.stock_id = ps.id
        ), 0))
        FROM product_stock ps
        WHERE ps.stock_prod_id = p.prod_id
        AND ps.expire_date >= CURDATE()
    ), 0) as calculated_available_stock,
    p.prod_damaged,
    p.prod_pullout
FROM tbl_prod p
WHERE p.prod_stocks > 0
AND p.prod_stocks != COALESCE(
    (SELECT SUM(ps.quantity - COALESCE((
        SELECT SUM(pdp.damage_quantity + pdp.pullout_quantity)
        FROM product_damage_pullout pdp
        WHERE pdp.stock_id = ps.id
    ), 0))
    FROM product_stock ps
    WHERE ps.stock_prod_id = p.prod_id
    AND ps.expire_date >= CURDATE()
), 0);

-- ============================================================================
-- ROLLBACK SCRIPT (IF NEEDED)
-- ============================================================================
-- Uncomment and run if you need to rollback the migration

-- DELETE FROM product_damage_pullout 
-- WHERE reason LIKE '%Historical data migrated%';

-- DELETE FROM product_stock 
-- WHERE batch LIKE 'INITIAL-%' 
-- AND note = 'Migrated from existing stock during system upgrade';

-- ============================================================================
-- POST-MIGRATION UPDATES
-- ============================================================================
-- After verifying the migration, you may want to update the product stocks
-- to match the calculated values from batches

/*
UPDATE tbl_prod p
SET p.prod_stocks = COALESCE(
    (SELECT SUM(ps.quantity - COALESCE((
        SELECT SUM(pdp.damage_quantity + pdp.pullout_quantity)
        FROM product_damage_pullout pdp
        WHERE pdp.stock_id = ps.id
    ), 0))
    FROM product_stock ps
    WHERE ps.stock_prod_id = p.prod_id
    AND ps.expire_date >= CURDATE()
), 0)
WHERE EXISTS (
    SELECT 1 FROM product_stock ps 
    WHERE ps.stock_prod_id = p.prod_id
);
*/

-- ============================================================================
-- NOTES
-- ============================================================================
-- 1. Update the 'created_by' field (currently set to 1) to your system user ID
-- 2. Run this script in a test environment first
-- 3. Backup your database before running in production
-- 4. The script uses CONCAT('INITIAL-', LPAD(prod_id, 5, '0')) for batch numbers
--    This creates batch numbers like: INITIAL-00001, INITIAL-00123, etc.
-- 5. Products with expired dates will have their expiry set to 1 year from today
-- 6. The verification queries help ensure data integrity after migration
