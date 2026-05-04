# API Endpoints Specification

**Base URL**: `https://api.restaurant.local/api/v1`
**Authentication**: JWT Token (Bearer Token)
**Content-Type**: `application/json`

---

## Authentication

### POST `/auth/login`
Login with credentials
```json
{
  "email": "user@example.com",
  "password": "password",
  "role": "customer|staff|admin|chef|manager"
}
```

### POST `/auth/logout`
Logout and invalidate token

### POST `/auth/refresh`
Refresh JWT token

---

## Module 1: Customer Experience

### GET `/customer/menu`
Get all menu categories and dishes
**Query Params**: `category_id`, `search`, `page`, `limit`
**Response**: Category tree with foods

### GET `/customer/menu/{food_id}`
Get food details with ingredients and reviews
**Response**: Food object with nutritional info

### POST `/customer/orders`
Create new order
```json
{
  "table_id": 1,
  "items": [
    {"food_id": 5, "quantity": 2, "notes": "no spicy"}
  ],
  "special_requests": "Fast order",
  "source": "table|app|website"
}
```
**Response**: Order object with order_id, total_price

### GET `/customer/orders/{order_id}`
Get order details and status

### GET `/customer/orders/table/{table_id}`
Get all orders for a specific table

### POST `/customer/cart`
Create/update cart (for web orders)
```json
{
  "items": [
    {"food_id": 5, "quantity": 2, "notes": "no onion"}
  ]
}
```

---

## Module 1: AI Services

### POST `/ai/voice`
Process voice input and return recommendations
```json
{
  "audio_file": "base64_audio",
  "duration": 5,
  "language": "vi"
}
```
**Response**: `{text: "...", intent: "order|search|info", items: [...]}`

### POST `/ai/search`
AI-powered food search/recommendation
```json
{
  "query": "noodle dishes",
  "dietary_preference": "vegetarian",
  "trending": true
}
```
**Response**: List of recommended foods

### GET `/ai/recommendations`
Get trending/personalized food recommendations
**Query Params**: `user_id`, `limit`, `category`
**Response**: List of food items with trending score

### GET `/ai/voice-logs`
Get AI voice interaction history (admin only)
**Query Params**: `date_from`, `date_to`, `user_id`

---

## Module 2: Operations - Floor Plan

### GET `/operations/tables`
Get all tables with current status
**Query Params**: `status` (empty|occupied|reserved)
**Response**: Table list with status, occupied_since

### GET `/operations/floor-plan`
Get visual floor plan representation
**Response**: Table positions with color coding

### PUT `/operations/tables/{table_id}/status`
Update table status
```json
{
  "status": "empty|occupied|reserved",
  "customer_count": 4
}
```

### POST `/operations/tables/{table_id}/merge`
Merge multiple tables
```json
{
  "table_ids": [1, 2, 3],
  "group_name": "Group A"
}
```

### DELETE `/operations/tables/{table_id}/merge`
Unmerge tables

### POST `/operations/tables/{table_id}/reserve`
Reserve a table
```json
{
  "customer_name": "John Doe",
  "customer_phone": "0901234567",
  "reserve_time": "2026-04-25T18:00:00",
  "duration_minutes": 90
}
```

### GET `/operations/notifications`
Get service notifications (items ready for pickup)
**Real-time**: WebSocket `/ws/operations/{staff_id}`
**Response**: List of ready orders

### PUT `/operations/notifications/{order_id}/acknowledge`
Mark order as served

---

## Module 3: Kitchen Display System

### GET `/kitchen/queue`
Get kitchen queue (FIFO) with filtering
**Query Params**: `status` (queue|cooking|ready), `section` (cold|hot|drinks)
**Response**: Ordered list of kitchen items

### GET `/kitchen/queue/{order_item_id}`
Get detailed order item for kitchen

### PUT `/kitchen/queue/{order_item_id}/start`
Mark item as being cooked
```json
{
  "estimated_time": 15
}
```

### PUT `/kitchen/queue/{order_item_id}/complete`
Mark item as ready
**Real-time**: Notifies staff & customer

### GET `/kitchen/stats`
Get KPI stats (avg cooking time, items completed)
**Query Params**: `date`, `shift`

---

## Module 4: Menu Management

### GET `/menu/categories`
Get all menu categories
**Response**: Category tree

### POST `/menu/categories`
Create new category (admin only)
```json
{
  "name": "Main Dishes",
  "description": "...",
  "image_url": "...",
  "display_order": 1
}
```

### PUT `/menu/categories/{category_id}`
Update category

### DELETE `/menu/categories/{category_id}`
Delete category

### GET `/menu/foods`
Get all menu items with filters
**Query Params**: `category_id`, `status` (available|unavailable), `page`, `limit`

### POST `/menu/foods`
Create new dish (admin only)
```json
{
  "name": "Pho Bo",
  "category_id": 1,
  "description": "Vietnamese beef noodle soup",
  "price": 50000,
  "image_url": "...",
  "recipe_id": 5,
  "is_available": true,
  "preparation_time": 10,
  "spicy_level": 2,
  "calories": 350
}
```

### PUT `/menu/foods/{food_id}`
Update dish details

### DELETE `/menu/foods/{food_id}`
Soft delete dish

### GET `/menu/recipes`
Get all recipes (admin/chef only)

### POST `/menu/recipes`
Create recipe definition
```json
{
  "name": "Pho Bo Recipe",
  "food_id": 1,
  "yield_quantity": 1,
  "yield_unit": "bowl",
  "ingredients": [
    {"ingredient_id": 1, "quantity": 500, "unit": "g"},
    {"ingredient_id": 2, "quantity": 200, "unit": "ml"}
  ]
}
```

### PUT `/menu/recipes/{recipe_id}`
Update recipe

### DELETE `/menu/recipes/{recipe_id}`
Delete recipe

---

## Module 4: Inventory Management

### GET `/inventory/ingredients`
Get all ingredients with stock levels
**Query Params**: `category`, `status` (in_stock|low|out)

### POST `/inventory/ingredients`
Create new ingredient (admin only)
```json
{
  "name": "Beef",
  "category": "Meat",
  "unit": "kg",
  "min_quantity": 10,
  "max_quantity": 100,
  "current_quantity": 45,
  "unit_cost": 150000
}
```

### PUT `/inventory/ingredients/{ingredient_id}`
Update ingredient

### POST `/inventory/ingredients/{ingredient_id}/adjust`
Adjust stock manually
```json
{
  "quantity": 10,
  "type": "in|out",
  "reason": "daily_consumption|damage|supplier"
}
```

### GET `/inventory/logs`
Get inventory transaction logs
**Query Params**: `date_from`, `date_to`, `type`, `ingredient_id`

### GET `/inventory/alerts`
Get low stock alerts
**Response**: List of ingredients below minimum

### GET `/inventory/reports`
Get inventory reports
**Query Params**: `date`, `report_type` (stock_value|turnover|waste)

---

## Module 5: Human Resources

### GET `/employees`
Get all employees with filters
**Query Params**: `department_id`, `status` (active|inactive), `page`, `limit`

### POST `/employees`
Create new employee (admin only)
```json
{
  "first_name": "Nguyen",
  "last_name": "Van A",
  "email": "a@restaurant.com",
  "phone": "0901234567",
  "department_id": 1,
  "position": "Chef",
  "hire_date": "2026-01-01",
  "salary": 15000000,
  "role": "chef|staff|manager|admin"
}
```

### GET `/employees/{employee_id}`
Get employee details

### PUT `/employees/{employee_id}`
Update employee info

### DELETE `/employees/{employee_id}`
Soft delete employee

### POST `/employees/{employee_id}/roles`
Assign roles to employee
```json
{
  "roles": ["chef", "manager"]
}
```

### GET `/employees/{employee_id}/kpi`
Get employee KPI/performance metrics
**Query Params**: `month`, `year`

### GET `/departments`
Get all departments

### POST `/departments`
Create department

### PUT `/departments/{department_id}`
Update department

---

## Module 6: Analytics & Reports

### GET `/analytics/revenue`
Revenue report
**Query Params**: `date_from`, `date_to`, `group_by` (day|week|month)
**Response**: Revenue trend data

### GET `/analytics/best-sellers`
Get best-selling dishes
**Query Params**: `date_from`, `date_to`, `limit`
**Response**: Foods with sales count & revenue

### GET `/analytics/orders`
Order statistics
**Query Params**: `date_from`, `date_to`, `status`
**Response**: Order count, avg items per order

### GET `/analytics/kpi`
Employee KPI analytics
**Query Params**: `department_id`, `date_from`, `date_to`
**Response**: Performance metrics by employee

### GET `/analytics/inventory-usage`
Inventory consumption report
**Query Params**: `date_from`, `date_to`

### GET `/analytics/dashboard`
Comprehensive dashboard data
**Response**: Cards with key metrics (daily revenue, total orders, low stock items, pending orders)

---

## Module 7: Payment & Billing

### POST `/payments/orders/{order_id}/invoice`
Generate pro-forma invoice (before payment)
**Response**: Invoice with items, subtotal, tax, total

### POST `/payments/checkout`
Process payment
```json
{
  "order_id": 1,
  "payment_method": "cash|qr_code|card",
  "amount": 500000,
  "discount_code": "SUMMER2026",
  "split_by": 2
}
```
**Response**: Payment confirmation with transaction_id

### POST `/payments/qr-code`
Generate dynamic QR code for payment
```json
{
  "order_id": 1,
  "amount": 500000,
  "method": "vietqr|momo|vnpay"
}
```
**Response**: `{qr_code_image: "...", qr_string: "..."}`

### POST `/payments/verify`
Verify payment from gateway
```json
{
  "transaction_id": "...",
  "method": "momo|vnpay",
  "signature": "..."
}
```

### GET `/payments/invoices`
Get payment/invoice history
**Query Params**: `date_from`, `date_to`, `table_id`

### POST `/payments/coupons`
Create coupon (admin only)
```json
{
  "code": "SUMMER2026",
  "discount_type": "percent|fixed",
  "discount_value": 10,
  "start_date": "2026-06-01",
  "end_date": "2026-08-31",
  "max_uses": 100,
  "min_order_value": 100000
}
```

### GET `/payments/coupons/validate`
Validate coupon code
**Query Params**: `code`, `order_total`

---

## Realtime Endpoints (WebSocket)

### `/ws/operations/{staff_id}`
Listen to:
- Table status changes
- Order ready for pickup
- New table reservation

### `/ws/kitchen/{section}`
Listen to:
- New orders in queue
- Status updates from kitchen
- Items moved to different sections

### `/ws/customer/{table_id}`
Listen to:
- Order status updates (cooking, ready)
- Estimated time updates

---

## Error Responses

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": "error message"
  }
}
```

**Common Status Codes**:
- `200`: Success
- `201`: Created
- `204`: No Content
- `400`: Bad Request (validation error)
- `401`: Unauthorized (authentication required)
- `403`: Forbidden (authorization failed)
- `404`: Not Found
- `409`: Conflict (e.g., table already occupied)
- `422`: Unprocessable Entity (validation error)
- `429`: Too Many Requests (rate limit)
- `500`: Internal Server Error

