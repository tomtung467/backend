# Database Schema (ERD) - Updated

## Core Tables

### users
```sql
id (PK)
name
email (UNIQUE)
password (hashed)
phone
role_id (FK → roles)
department_id (FK → departments) -- for employees
avatar_url
is_active
last_login_at
remember_token
created_at
updated_at
deleted_at (soft delete)
```

### roles
```sql
id (PK)
name (admin|manager|chef|staff|customer)
description
permissions (JSON)
created_at
```

### departments
```sql
id (PK)
name (Chef, Waiter, Manager, etc.)
description
manager_id (FK → users)
created_at
updated_at
```

### employees
```sql
id (PK)
user_id (FK → users) UNIQUE
first_name
last_name
department_id (FK → departments)
position
hire_date
salary
employee_id_number (unique code)
phone
address
bank_account
status (active|inactive|on_leave)
is_active
created_at
updated_at
deleted_at
```

---

## Table Management

### tables
```sql
id (PK)
table_number
capacity (max seats)
section (area in restaurant)
status (empty|occupied|reserved|under_cleaning)
current_customer_count (nullable)
occupied_since (timestamp, nullable)
reserved_until (timestamp, nullable)
is_active
created_at
updated_at
```

### table_reservations
```sql
id (PK)
table_id (FK → tables)
customer_name
customer_phone
reserved_at (datetime)
duration_minutes
status (confirmed|completed|cancelled)
notes
created_at
updated_at
```

### table_merges
```sql
id (PK)
group_name (Group A, Group B, etc.)
table_ids (JSON array or separate junction table)
merged_at
created_by_id (FK → users)
is_active
created_at
```

---

## Menu Management

### categories
```sql
id (PK)
name
description
image_url
display_order
is_active
created_at
updated_at
```

### foods (Menu Items/Dishes)
```sql
id (PK)
name
category_id (FK → categories)
description
price (in VND)
image_url
recipe_id (FK → recipes) -- to define ingredients
preparation_time (minutes)
spicy_level (1-5)
calories
allergens (JSON array: nuts|seafood|dairy|gluten)
is_available
is_popular (for recommendations)
created_at
updated_at
deleted_at
```

### recipes
```sql
id (PK)
name
food_id (FK → foods)
yield_quantity (how much it makes)
yield_unit (bowl|plate|portion)
preparation_instructions (text)
created_at
updated_at
```

### recipe_items
```sql
id (PK)
recipe_id (FK → recipes)
ingredient_id (FK → ingredients)
quantity (amount needed)
unit (g|kg|ml|l|piece)
created_at
```

---

## Inventory Management

### ingredients
```sql
id (PK)
name
category (Meat|Vegetable|Spice|Beverage)
description
unit (g|kg|ml|l|piece)
current_quantity (stock level)
min_quantity (alert level)
max_quantity (storage limit)
unit_cost (price per unit)
supplier_id (FK → suppliers) [optional]
is_active
created_at
updated_at
```

### inventory_logs
```sql
id (PK)
ingredient_id (FK → ingredients)
type (in|out|adjustment)
quantity
unit
reason (purchase|daily_consumption|damage|waste|adjustment|shrinkage)
reference_id (FK → orders or purchase order)
reference_type (order|purchase|manual)
previous_quantity
new_quantity
created_by_id (FK → users)
notes
created_at
```

### inventory_alerts
```sql
id (PK)
ingredient_id (FK → ingredients)
alert_type (low_stock|out_of_stock|expiring_soon)
quantity_remaining
min_quantity
created_at
acknowledged_at (nullable)
acknowledged_by_id (FK → users)
```

### suppliers
```sql
id (PK)
name
contact_person
phone
email
address
delivery_days
payment_terms
is_active
created_at
updated_at
```

---

## Orders & Order Items

### orders
```sql
id (PK)
order_number (unique string: ORD-20260425-001)
table_id (FK → tables)
status (pending|confirmed|in_progress|ready|served|paid|cancelled)
total_price
subtotal
tax_amount (10% VAT)
service_charge
discount_amount
coupon_id (FK → coupons) [nullable]
customer_notes
special_requests
created_by_id (FK → users) [who took the order]
source (table|web|mobile_app)
estimated_completion_time
actual_completion_time (nullable)
paid_at (nullable)
created_at
updated_at
```

### order_items
```sql
id (PK)
order_id (FK → orders)
food_id (FK → foods)
quantity
unit_price (price at time of order)
total_price (quantity × unit_price)
special_notes (no spicy, extra sauce, etc)
status (pending|preparing|ready|served)
created_at
updated_at
```

### order_item_history
```sql
id (PK)
order_item_id (FK → order_items)
status
changed_at
changed_by_id (FK → users)
notes
```

---

## Payments & Billing

### payments
```sql
id (PK)
order_id (FK → orders)
amount
payment_method (cash|qr_code|card|digital_wallet)
payment_gateway (momo|vnpay|vietqr|manual)
transaction_id (from gateway)
status (pending|completed|failed|refunded)
paid_at (nullable)
receipt_number
created_by_id (FK → users)
notes
created_at
updated_at
```

### invoices
```sql
id (PK)
order_id (FK → orders)
invoice_number (unique)
customer_name
total_before_tax
tax_amount
total_after_tax
discount
final_total
invoice_type (pro_forma|final)
notes
issued_at
email_sent_at (nullable)
printed_at (nullable)
is_finalized
created_at
```

### coupons
```sql
id (PK)
code (UNIQUE: SUMMER2026, LOYALTY10)
name
description
discount_type (percent|fixed_amount)
discount_value
min_order_value
max_uses_per_customer
total_uses_limit
current_uses
start_date
end_date
is_active
created_by_id (FK → users)
created_at
updated_at
```

### coupon_usage
```sql
id (PK)
coupon_id (FK → coupons)
order_id (FK → orders)
discount_amount
used_at
created_at
```

### payment_refunds
```sql
id (PK)
payment_id (FK → payments)
amount
reason
status (pending|approved|completed|rejected)
requested_at
approved_at (nullable)
completed_at (nullable)
approved_by_id (FK → users)
notes
created_at
```

---

## AI & Recommendation

### ai_voice_logs
```sql
id (PK)
user_id (FK → users) [customer]
audio_file_url
audio_duration (seconds)
transcribed_text
detected_intent (order|search|info|complaint)
confidence_score (0-1)
extracted_items (JSON: which foods mentioned)
status (success|partial|failed)
error_message (nullable)
language (vi|en)
created_at
```

### ai_recommendations
```sql
id (PK)
user_id (FK → users) [customer]
food_id (FK → foods)
recommendation_type (trending|personalized|complementary)
score (0-1)
reason (JSON: why it was recommended)
clicked_at (nullable)
ordered_at (nullable)
created_at
```

### ai_interaction_logs
```sql
id (PK)
user_id (FK → users)
interaction_type (voice|text|search|recommendation)
query_text
response_text
interaction_data (JSON)
feedback_rating (1-5) [nullable]
created_at
```

---

## Kitchen Management

### kitchen_queue_items
```sql
id (PK)
order_item_id (FK → order_items)
section (cold_prep|hot_station|beverage_bar|dessert)
status (queue|preparing|ready|served)
position_in_queue (order number)
estimated_time (minutes)
started_at (nullable)
completed_at (nullable)
priority (normal|high|urgent)
chef_assigned_id (FK → users) [nullable]
created_at
updated_at
```

### kitchen_section_config
```sql
id (PK)
section_name
max_concurrent_items
average_prep_time
station_id
is_active
created_at
```

---

## Analytics & Audit

### audit_logs
```sql
id (PK)
user_id (FK → users)
action (create|read|update|delete|payment|status_change)
model_type (Food|Order|Payment|Inventory|Employee)
model_id
old_values (JSON)
new_values (JSON)
description
ip_address
user_agent
created_at
```

### kpi_snapshots
```sql
id (PK)
employee_id (FK → employees)
metric_type (orders_processed|avg_order_time|items_cooked|customer_satisfaction)
metric_value (numeric)
period_date (date)
notes
created_at
```

### sales_reports
```sql
id (PK)
report_date (date)
report_type (daily|weekly|monthly)
total_revenue
total_orders
average_order_value
best_seller_id (FK → foods)
worst_seller_id (FK → foods)
total_customers
data (JSON: detailed breakdown)
created_at
```

---

## Relationships Summary

```
users
├─ has_many: orders (as created_by)
├─ has_many: payments (as created_by)
├─ has_many: audit_logs
├─ belongs_to: department
└─ belongs_to: role

orders
├─ belongs_to: table
├─ has_many: order_items
├─ has_one: payment
├─ has_one: invoice
├─ belongs_to: coupon (optional)
└─ belongs_to: user (as created_by)

order_items
├─ belongs_to: order
├─ belongs_to: food
└─ has_many: kitchen_queue_items

foods
├─ belongs_to: category
├─ has_one: recipe
└─ has_many: order_items

recipes
├─ belongs_to: food
└─ has_many: recipe_items

recipe_items
├─ belongs_to: recipe
└─ belongs_to: ingredient

ingredients
├─ has_many: inventory_logs
└─ has_many: recipe_items

payments
├─ belongs_to: order
└─ has_many: refunds
```

---

## Key Constraints & Validations

1. **Atomic Operations**: Orders + Inventory changes must be transactional
2. **Foreign Keys**: All FK references must have CASCADE/RESTRICT rules
3. **Unique Fields**: 
   - `users.email`
   - `orders.order_number`
   - `invoices.invoice_number`
   - `coupons.code`
   - `recipes.food_id`
4. **Indexes**:
   - `orders.created_at`, `orders.table_id`, `orders.status`
   - `order_items.order_id`, `order_items.status`
   - `payments.created_at`, `payments.status`
   - `inventory_logs.ingredient_id`, `inventory_logs.created_at`
   - `audit_logs.user_id`, `audit_logs.created_at`
5. **Soft Deletes**: `users`, `employees`, `foods`, `ingredients`, `categories`

---

## Migration Order

1. Create base tables: `roles`, `departments`, `users`
2. Create table management: `tables`, `table_reservations`, `table_merges`
3. Create menu: `categories`, `foods`, `recipes`, `recipe_items`
4. Create inventory: `ingredients`, `suppliers`, `inventory_logs`, `inventory_alerts`
5. Create orders: `orders`, `order_items`, `order_item_history`
6. Create payments: `payments`, `invoices`, `coupons`, `coupon_usage`, `payment_refunds`
7. Create AI: `ai_voice_logs`, `ai_recommendations`, `ai_interaction_logs`
8. Create kitchen: `kitchen_queue_items`, `kitchen_section_config`
9. Create analytics: `audit_logs`, `kpi_snapshots`, `sales_reports`
10. Create relationships: Foreign keys and indexes

