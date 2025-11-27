# Grouped Billing Implementation

## Overview
Implemented grouped billing system that allows multiple pets with the same owner to have their services billed together on the same day, with a single payment process while maintaining individual billing records for viewing and printing.

## Database Changes
✅ **Migration Created**: `2025_11_27_000001_add_grouped_billing_fields_to_tbl_bill.php`

### New Columns in `tbl_bill`:
- `billing_group_id` (string, nullable) - Groups billings for the same owner
- `owner_id` (bigint, nullable) - Direct reference to owner
- `total_amount` (decimal) - Total amount for this billing
- `paid_amount` (decimal) - Amount paid so far
- `is_group_parent` (boolean) - True for the main billing in a group

## New Service Class
✅ **Created**: `app/Services/GroupedBillingService.php`

### Key Methods:
1. `generateGroupedBilling($visitIds)` - Creates grouped billing for multiple visits
2. `generateSingleBilling($visitId)` - Creates individual billing for one visit
3. `autoGenerateGroupedBillingForOwner($ownerId, $date)` - Auto-groups visits by owner
4. `getTodayGroupedBillings($branchId)` - Gets today's grouped billings
5. `getGroupSummary($billingGroupId)` - Gets billing group details

## Controller Changes
✅ **Updated**: `app/Http/Controllers/SalesManagementController.php`

### New/Modified Methods:
1. `__construct()` - Added GroupedBillingService injection
2. `generateBill()` - Updated to use GroupedBillingService
3. `autoGenerateGroupedBillings()` - Auto-generates grouped billings for completed visits
4. `generateGroupedBilling()` - Manually generate grouped billing
5. `index()` - Modified to show only parent billings in the list

### Still TODO:
- [ ] Update `markAsPaid()` to handle grouped billing payments
- [ ] Update `showReceipt()` to show grouped or individual receipts
- [ ] Add method to view individual bills within a group
- [ ] Add method to get billing group details for modal display

## Model Changes
✅ **Updated**: `app/Models/Billing.php`

### New Relationships:
- `owner()` - BelongsTo Owner
- `groupedBillings()` - Get all billings in the same group
- `parentBilling()` - Get the parent billing for payment

### New Methods:
- `canGenerateBill()` - Check if visit services are completed

## How It Works

### 1. Visit Completion
When services for multiple pets (same owner) are completed on the same day:
```
Owner: John Doe
Pets: Max (Dog), Luna (Cat)
Date: 2025-11-27

Max Visit: Vaccination + Checkup = ₱1,500
Luna Visit: Deworming + Grooming = ₱800
```

### 2. Billing Generation
Option A: **Auto-generate** (recommended)
- Call `autoGenerateGroupedBillings()` at end of day or when needed
- System automatically detects completed visits by owner
- Creates group if multiple pets, single if one pet

Option B: **Manual grouping**
- Staff selects multiple completed visits
- Calls `generateGroupedBilling()` with visit IDs

### 3. Billing Records Created
```
Billing Group ID: BG-123-20251127153045

Bill #1 (Parent):
- Visit ID: 501 (Max)
- Total: ₱1,500
- is_group_parent: true
- billing_group_id: BG-123-20251127153045

Bill #2:
- Visit ID: 502 (Luna)  
- Total: ₱800
- is_group_parent: false
- billing_group_id: BG-123-20251127153045

Group Total: ₱2,300
```

### 4. Payment Process (TODO - Implementation Needed)
When staff processes payment:
1. Select parent billing (shows group total: ₱2,300)
2. Enter payment amount
3. System updates ALL billings in the group:
   - Distributes payment proportionally
   - Updates paid_amount for each bill
   - Updates bill_status (pending/partial/paid)
   - Creates payment record linked to parent

### 5. Viewing & Printing
- **List View**: Shows one entry per owner with group total
- **Individual View**: Each pet's bill can be viewed separately
- **Group View**: All pet bills shown together
- **Print**: Can print individual or grouped receipt

## Frontend Changes Needed (TODO)

### 1. Billing List (orderBilling.blade.php)
```html
<!-- Show grouped billing -->
<tr class="group-parent">
    <td>2025-11-27</td>
    <td>John Doe (2 pets)</td>
    <td>₱2,300</td>
    <td>
        <button onclick="expandGroup('BG-123-20251127153045')">
            View Details
        </button>
    </td>
</tr>

<!-- Expandable child billings -->
<tr class="group-child" data-group="BG-123-20251127153045" style="display:none">
    <td colspan="4">
        <div>
            <strong>Max</strong> - ₱1,500
            <button>View</button>
            <button>Print</button>
        </div>
        <div>
            <strong>Luna</strong> - ₱800
            <button>View</button>
            <button>Print</button>
        </div>
    </div>
</tr>
```

### 2. Payment Modal
- Show group total when parent billing is selected
- Display breakdown of individual pet amounts
- Process payment for entire group at once

### 3. Receipt View
- Option to print individual pet receipt
- Option to print grouped receipt with all pets

## Routes to Add
```php
// In web.php
Route::post('/sales/auto-generate-billings', [SalesManagementController::class, 'autoGenerateGroupedBillings'])->name('sales.auto-generate');
Route::post('/sales/generate-grouped', [SalesManagementController::class, 'generateGroupedBilling'])->name('sales.generate-grouped');
Route::get('/sales/billing-group/{groupId}', [SalesManagementController::class, 'showBillingGroup'])->name('sales.billing-group');
```

## Testing Checklist
- [ ] Create visits for multiple pets (same owner, same day)
- [ ] Complete all services
- [ ] Auto-generate grouped billing
- [ ] Verify billing_group_id is set correctly
- [ ] Verify only parent shows in list
- [ ] Process payment for group
- [ ] Verify all bills in group are updated
- [ ] Print individual receipt
- [ ] Print grouped receipt
- [ ] Test with single pet (no grouping)
- [ ] Test with different owners (separate groups)

## Benefits
1. **Customer Convenience**: One payment for multiple pets
2. **Staff Efficiency**: Process multiple bills at once
3. **Record Keeping**: Individual bills maintained for audit/reference
4. **Flexibility**: Can still view/print individual bills
5. **Accurate Tracking**: Each pet's services properly documented

## Next Steps
1. Complete markAsPaid() update for grouped payment processing
2. Add billing group detail modal/page
3. Update frontend views
4. Add routes
5. Test thoroughly
6. Document user guide
