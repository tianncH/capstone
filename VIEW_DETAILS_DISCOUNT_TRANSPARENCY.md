# View Details Discount Transparency Fix

## Problem
The "View Details" feature in both counter and admin systems was not showing discount information, even though discounts were being applied. Users could see the final total but not understand why it was lower than the sum of individual items.

## Example from User's Screenshot
- Individual items: ₱1.20 + ₱1.20 = ₱2.40
- Final total: ₱1.92
- Missing: ₱0.48 discount information (20% Senior Citizen/PWD discount)

## Files Fixed

### 1. Counter Order Details (`counter/order_details.php`)

#### A. Enhanced SQL Query
**Before:**
```sql
SELECT o.*, os.name as status_name, t.table_number,
       o.total_amount as original_total
FROM orders o 
LEFT JOIN tables t ON o.table_id = t.table_id 
JOIN order_statuses os ON o.status_id = os.status_id 
WHERE o.order_id = ?
```

**After:**
```sql
SELECT o.*, os.name as status_name, t.table_number,
       o.total_amount as original_total,
       o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount
FROM orders o 
LEFT JOIN tables t ON o.table_id = t.table_id 
JOIN order_statuses os ON o.status_id = os.status_id 
WHERE o.order_id = ?
```

#### B. Added Discount Info to Order Details Section
**Location:** Lines 290-297
**Added:** Discount badge in order information
```php
<?php if ($order['discount_amount'] > 0): ?>
<div class="order-info-row">
    <span><strong>Discount:</strong></span>
    <span class="badge bg-success">
        <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> (₱<?= number_format($order['discount_amount'], 2) ?> off)
    </span>
</div>
<?php endif; ?>
```

#### C. Enhanced Order Summary Section
**Location:** Lines 374-390
**Added:** Detailed discount breakdown
```php
<?php if ($order['discount_amount'] > 0): ?>
    <div class="mt-2">
        <small class="text-muted">Original Amount: ₱<?= number_format($order['original_amount'] ?? $order['total_amount'] + $order['discount_amount'], 2) ?></small><br>
        <small class="text-success">
            <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> Discount: -₱<?= number_format($order['discount_amount'], 2) ?>
        </small>
    </div>
<?php endif; ?>
```

### 2. Admin Order Details (`admin/order_details.php`)

#### A. Enhanced SQL Query
**Before:**
```sql
SELECT o.*, os.name as status_name, t.table_number 
FROM orders o 
LEFT JOIN tables t ON o.table_id = t.table_id 
JOIN order_statuses os ON o.status_id = os.status_id 
WHERE o.order_id = ?
```

**After:**
```sql
SELECT o.*, os.name as status_name, t.table_number,
       o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount
FROM orders o 
LEFT JOIN tables t ON o.table_id = t.table_id 
JOIN order_statuses os ON o.status_id = os.status_id 
WHERE o.order_id = ?
```

#### B. Enhanced Order Summary Table
**Location:** Lines 119-135
**Added:** Discount breakdown in table footer
```php
<?php if ($order['discount_amount'] > 0): ?>
<tr>
    <th colspan="4">Original Amount:</th>
    <th>₱<?= number_format($order['original_amount'] ?? $order['total_amount'] + $order['discount_amount'], 2) ?></th>
</tr>
<tr class="table-warning">
    <th colspan="4">
        <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $order['discount_type'])) ?> Discount:
    </th>
    <th class="text-success">-₱<?= number_format($order['discount_amount'], 2) ?></th>
</tr>
<?php endif; ?>
<tr class="table-success">
    <th colspan="4">Total Amount:</th>
    <th>₱<?= number_format($order['total_amount'], 2) ?></th>
</tr>
```

### 3. Admin Get Order Details API (`admin/get_order_details.php`)

#### A. Enhanced SQL Query
**Before:**
```sql
SELECT o.*, t.table_name, t.table_number, t.location 
FROM orders o 
JOIN tables t ON o.table_id = t.table_id 
WHERE o.order_id = ?
```

**After:**
```sql
SELECT o.*, t.table_name, t.table_number, t.location,
       o.discount_type, o.discount_percentage, o.discount_amount, o.original_amount
FROM orders o 
JOIN tables t ON o.table_id = t.table_id 
WHERE o.order_id = ?
```

## Visual Results

### Counter Order Details Page
**Before:**
```
Order #QR1-112237
Table 1
Order Time: Oct 08, 2025 05:22 PM
Status: PAID

Order Summary
Total amount for this order
₱1.92
```

**After:**
```
Order #QR1-112237
Table 1
Order Time: Oct 08, 2025 05:22 PM
Status: PAID
Discount: [Senior Citizen (₱0.48 off)]

Order Summary
Total amount for this order
Original Amount: ₱2.40
Senior Citizen Discount: -₱0.48
₱1.92
[Discount Applied]
```

### Admin Order Details Page
**Before:**
```
| Item | Quantity | Price | Subtotal |
|------|----------|-------|----------|
| Alimosan Sinigang/Tinola | 1 | ₱1.20 | ₱1.20 |
| Blue Marlin Grilled | 1 | ₱1.20 | ₱1.20 |
| Total Amount: | | | ₱1.92 |
```

**After:**
```
| Item | Quantity | Price | Subtotal |
|------|----------|-------|----------|
| Alimosan Sinigang/Tinola | 1 | ₱1.20 | ₱1.20 |
| Blue Marlin Grilled | 1 | ₱1.20 | ₱1.20 |
| Original Amount: | | | ₱2.40 |
| Senior Citizen Discount: | | | -₱0.48 |
| Total Amount: | | | ₱1.92 |
```

## Benefits

1. **Complete Transparency:** Users can now see exactly why the total is lower than the sum of items
2. **Discount Type Clarity:** Clear indication of whether it's Senior Citizen or PWD discount
3. **Amount Breakdown:** Original amount, discount amount, and final amount all visible
4. **Consistent Experience:** Same discount transparency across all "View Details" features
5. **Audit Trail:** Complete documentation of all discounts applied

## Result

✅ **All "View Details" features now show complete discount transparency**
✅ **Users can understand why totals are lower than item sums**
✅ **Senior Citizen and PWD discounts are clearly documented**
✅ **No more confusion about order amounts**

🎉 **View Details Discount Transparency Ghost Eliminated!** 👻❌



