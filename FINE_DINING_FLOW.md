# Fine Dining Restaurant Order Flow

## Correct Status Progression

### Status IDs:
1. **pending** (status_id = 1) - Order placed but not paid
2. **paid** (status_id = 2) - Order paid (SALES RECORDED HERE)
3. **preparing** (status_id = 3) - Order is being prepared in the kitchen
4. **ready** (status_id = 4) - Order is ready for serving
5. **completed** (status_id = 5) - Order has been served and completed
6. **cancelled** (status_id = 6) - Order has been cancelled

## Fine Dining Flow:

### Regular Counter Orders:
1. Customer orders at counter → **pending** (status 1)
2. Counter processes payment → **paid** (status 2) ✅ SALES RECORDED
3. Counter sends to kitchen → **preparing** (status 3)
4. Kitchen prepares food → **preparing** (status 3)
5. Kitchen marks ready → **ready** (status 4)
6. Kitchen serves to customer → **completed** (status 5)

### QR Orders (Customers order via QR code at table):
1. Customer orders via QR → **pending** (status 1)
2. Counter confirms order → **pending** (status 1)
3. Counter sends to kitchen → **preparing** (status 3)
4. Kitchen prepares food → **preparing** (status 3)
5. Kitchen marks ready → **ready** (status 4)
6. Kitchen serves food → **completed** (status 5) ⚠️ NO PAYMENT YET
7. Customer requests bill → Bill notification sent to counter
8. Counter processes payment → **paid** (status 2) ✅ SALES RECORDED

## Key Principle:
**Sales are ONLY recorded when counter processes payment (status 2), regardless of when the food is served!**

## Current Issues to Fix:

1. ❌ Kitchen marking orders as "completed" (status 5) instead of keeping track of serve status
2. ❌ QR payment not updating orders table to "paid" status
3. ❌ Status transitions allowing orders to skip "paid" status
4. ❌ Confusion between "served" and "paid" statuses

## Solution:

For fine dining, we need to recognize that:
- **Status 5 (completed)** should actually mean "served to customer" (food delivered)
- **Status 2 (paid)** is the FINAL status after payment is processed
- Orders can be served (status 5) BEFORE being paid (status 2)
- Sales recording happens at payment time (status 2), not serve time (status 5)

## Proposed Status Transition Rules:

### Counter Orders:
pending (1) → **paid (2)** → preparing (3) → ready (4) → completed (5)

### QR Orders:
pending (1) → preparing (3) → ready (4) → completed (5) → **paid (2)**

Note: Status 2 (paid) can come BEFORE or AFTER status 5 (completed/served) depending on order type!




