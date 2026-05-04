# Restaurant Management System - Backend Architecture

## Project Overview
Ứng dụng đặt món và quản lý quy trình nhà hàng (Restaurant Ordering & Process Management System)

**Tech Stack**: Laravel (PHP), MySQL, Firebase (Real-time), RESTful API, WebSockets

---

## Directory Structure

```
app/
├── Enums/                          # Enumeration types
│   ├── AI/                         # AI-related statuses
│   ├── Billing/                    # Payment-related enums
│   ├── Employee/                   # Employee statuses
│   ├── Kitchen/                    # Kitchen order statuses
│   ├── Order/                      # Order statuses
│   └── Table/                      # Table statuses (Empty, Occupied, Reserved)
│
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── CustomerController.php       # Module 1: Customer Experience
│   │   ├── AIController.php             # AI Voice, Recommendations
│   │   ├── OperationsController.php     # Module 2: Operations (Floor Plan, Tables)
│   │   ├── KitchenController.php        # Module 3: Kitchen Display System (KDS)
│   │   ├── MenuController.php           # Module 4: Menu Management (CMS)
│   │   ├── InventoryController.php      # Module 4: Inventory Management
│   │   ├── EmployeeController.php       # Module 5: Human Resources
│   │   ├── AnalyticsController.php      # Module 6: Analytics & Reports
│   │   ├── PaymentController.php        # Module 7: Payment & Billing
│   │   ├── OrderController.php          # Core Orders
│   │   └── TableController.php          # Table Management
│   │
│   ├── Requests/                   # Form Request Validation
│   │   ├── StoreMenuRequest.php
│   │   ├── StoreOrderRequest.php
│   │   ├── StoreEmployeeRequest.php
│   │   └── ...
│   │
│   └── Resources/                  # API Response Formatting
│       ├── MenuResource.php
│       ├── OrderResource.php
│       ├── EmployeeResource.php
│       └── ...
│
├── Models/                         # Eloquent Models
│   ├── User.php                    # Users (Staff, Admin)
│   ├── Customer.php                # Customers (for loyalty/history)
│   ├── Employee.php                # Employee details
│   ├── Department.php              # HR Departments
│   ├── Table.php                   # Tables (Floor Plan)
│   ├── Category.php                # Menu Categories
│   ├── Food.php                    # Menu Items (Dishes)
│   ├── Ingredient.php              # Inventory Items
│   ├── Recipe.php                  # Recipes (Food composition)
│   ├── RecipeItem.php              # Recipe components
│   ├── Order.php                   # Orders
│   ├── OrderItem.php               # Order details (items in order)
│   ├── Payment.php                 # Payment records
│   ├── Coupon.php                  # Discount codes
│   ├── InventoryLog.php            # Stock in/out logs
│   ├── InventoryAlert.php          # Low stock alerts
│   ├── AuditLog.php                # Transaction audit trail
│   ├── EmployeeKPI.php             # Employee performance metrics
│   ├── TableReservation.php        # Table reservations
│   ├── AILog.php                   # AI interaction logs (Voice, recommendations)
│   └── AIVoiceLog.php              # Voice interaction records
│
├── Repositories/                   # Data Access Layer
│   ├── BaseRepository.php
│   ├── IBaseRepository.php
│   ├── User/
│   ├── Customer/
│   ├── Orders/
│   │   ├── OrderRepository.php
│   │   └── OrderItemRepository.php
│   ├── Menu/
│   │   ├── FoodRepository.php
│   │   └── CategoryRepository.php
│   ├── Inventory/
│   │   ├── IngredientRepository.php
│   │   └── RecipeRepository.php
│   ├── Tables/
│   ├── Employees/
│   ├── Payments/
│   ├── AI/
│   └── Analytics/
│
├── Services/                       # Business Logic Layer
│   ├── BaseService.php
│   ├── Auth/
│   │   └── AuthService.php
│   ├── Customer/
│   │   ├── OrderService.php
│   │   ├── CartService.php
│   │   └── CustomerService.php
│   ├── AI/
│   │   ├── AIVoiceService.php      # Voice-to-text, NLP
│   │   ├── RecommendationService.php # Trending items
│   │   └── AILogService.php
│   ├── Operations/
│   │   ├── FloorPlanService.php    # Table management
│   │   ├── TableService.php
│   │   └── TableMergeService.php
│   ├── Kitchen/
│   │   ├── KDSService.php          # Kitchen Display System
│   │   ├── KitchenQueueService.php # FIFO queue
│   │   └── KitchenNotificationService.php # Real-time updates
│   ├── Menu/
│   │   ├── MenuService.php         # CMS operations
│   │   ├── CategoryService.php
│   │   └── FoodService.php
│   ├── Inventory/
│   │   ├── InventoryService.php    # Stock management
│   │   ├── RecipeService.php       # Recipe/definition
│   │   ├── InventoryDeductionService.php # Auto stock reduction
│   │   └── InventoryAlertService.php
│   ├── Employee/
│   │   ├── EmployeeService.php     # HR operations
│   │   ├── DepartmentService.php
│   │   ├── RolePermissionService.php
│   │   └── KPIService.php
│   ├── Payment/
│   │   ├── PaymentService.php      # Payment processing
│   │   ├── PaymentMethodService.php
│   │   ├── InvoiceService.php
│   │   ├── CouponService.php
│   │   └── QRCodeService.php       # Dynamic QR generation
│   ├── Billing/
│   │   ├── BillingService.php
│   │   └── BillingSplitService.php # Split/merge bills
│   ├── Analytics/
│   │   ├── RevenueService.php
│   │   ├── ReportsService.php
│   │   ├── BestSellersService.php
│   │   └── KPIAnalyticsService.php
│   └── Notification/
│       ├── NotificationService.php
│       └── RealTimeService.php     # WebSocket/Firebase
│
├── Events/                         # Application Events
│   ├── OrderCreated.php
│   ├── OrderReadyForPickup.php
│   ├── PaymentProcessed.php
│   ├── InventoryLow.php
│   ├── TableStatusChanged.php
│   └── KitchenOrderReceived.php
│
├── Listeners/                      # Event Handlers
│   ├── UpdateInventoryOnOrderCreated.php
│   ├── SendKitchenNotification.php
│   ├── UpdateTableStatus.php
│   ├── GenerateAuditLog.php
│   └── SendRealTimeUpdate.php
│
├── Jobs/                           # Queued Jobs
│   ├── ProcessInventoryDeduction.php
│   ├── GenerateReport.php
│   ├── SyncWithFirebase.php
│   ├── SendNotification.php
│   └── ProcessPayment.php
│
├── Policies/                       # Authorization Policies
│   ├── UserPolicy.php
│   ├── EmployeePolicy.php
│   ├── OrderPolicy.php
│   └── InventoryPolicy.php
│
├── Traits/                         # Reusable Traits
│   ├── ApiResponseTrait.php        # Response formatting
│   ├── AuditLogTrait.php           # Auto audit logging
│   └── TimestampTrait.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   ├── EventServiceProvider.php
│   ├── RouteServiceProvider.php
│   └── AuthServiceProvider.php

routes/
├── api.php                         # API routes (v1)
│   ├── /api/v1/auth
│   ├── /api/v1/customer/orders
│   ├── /api/v1/customer/menu
│   ├── /api/v1/customer/ai
│   ├── /api/v1/operations
│   ├── /api/v1/kitchen
│   ├── /api/v1/menu
│   ├── /api/v1/inventory
│   ├── /api/v1/employees
│   ├── /api/v1/analytics
│   ├── /api/v1/payments
│   └── /api/v1/admin
│
├── web.php                         # Web routes (Dashboard)
│   └── Admin panel routes

config/
├── restaurant.php                  # Custom config (table max seats, etc)
├── payment.php                     # Payment gateways config
├── ai.php                          # AI service config
└── notification.php                # Real-time notification config

database/
├── migrations/
│   ├── Create_users_table
│   ├── Create_employees_table
│   ├── Create_departments_table
│   ├── Create_tables_table
│   ├── Create_categories_table
│   ├── Create_foods_table
│   ├── Create_ingredients_table
│   ├── Create_recipes_table
│   ├── Create_recipe_items_table
│   ├── Create_orders_table
│   ├── Create_order_items_table
│   ├── Create_payments_table
│   ├── Create_coupons_table
│   ├── Create_inventory_logs_table
│   ├── Create_audit_logs_table
│   ├── Create_ai_voice_logs_table
│   └── ... more migrations
│
└── seeders/
    ├── DatabaseSeeder.php
    ├── UserSeeder.php
    ├── CategorySeeder.php
    ├── FoodSeeder.php
    ├── IngredientSeeder.php
    ├── RecipeSeeder.php
    ├── TableSeeder.php
    ├── EmployeeSeeder.php
    └── CouponSeeder.php
```

---

## 7 Modules Architecture

### Module 1: Customer Experience (Khách hàng)
**Files**: `CustomerController.php`, `AIController.php`, `OrderService.php`
- **Features**: AI Voice Assistant, E-Menu, Order Placement
- **Endpoints**: `/api/v1/customer/*`
- **Real-time**: Orders → Kitchen (WebSocket)

### Module 2: Operations (Vận hành)
**Files**: `OperationsController.php`, `FloorPlanService.php`, `TableService.php`
- **Features**: Floor Plan, Table Management, Table Merge/Split
- **Endpoints**: `/api/v1/operations/*`
- **Real-time**: Table status changes

### Module 3: Kitchen Display System
**Files**: `KitchenController.php`, `KDSService.php`, `KitchenQueueService.php`
- **Features**: Kitchen Queue (FIFO), Status Updates, Notifications
- **Endpoints**: `/api/v1/kitchen/*`
- **Real-time**: Order status → Ops & Customer

### Module 4: Inventory & Menu
**Files**: `MenuController.php`, `InventoryController.php`, `MenuService.php`, `InventoryService.php`
- **Features**: Menu CMS, Recipe Management, Stock Control, Low Stock Alerts
- **Endpoints**: `/api/v1/menu/*`, `/api/v1/inventory/*`

### Module 5: Human Resources
**Files**: `EmployeeController.php`, `EmployeeService.php`, `KPIService.php`
- **Features**: Employee Management, RBAC, KPI Tracking
- **Endpoints**: `/api/v1/employees/*`
- **Authorization**: RBAC policies

### Module 6: Analytics
**Files**: `AnalyticsController.php`, `RevenueService.php`, `BestSellersService.php`
- **Features**: Revenue Reports, Best-sellers, KPI Analytics
- **Endpoints**: `/api/v1/analytics/*`

### Module 7: Payment & Billing
**Files**: `PaymentController.php`, `InvoiceService.php`, `QRCodeService.php`, `BillingSplitService.php`
- **Features**: Invoicing, Multi-payment Methods, Discounts, Receipt Management
- **Endpoints**: `/api/v1/payments/*`
- **Integration**: VietQR, Momo, VNPay

---

## Data Flow & Architecture Patterns

### 1. Order Flow (Customer → Kitchen)
```
Customer Order (API) 
  ↓
OrderService (validation)
  ↓
Create Order Record (DB)
  ↓
OrderCreated Event
  ↓
- UpdateInventoryOnOrderCreated Listener (mark as reserved)
- SendKitchenNotification Listener (real-time to KDS)
  ↓
Kitchen Display System (Real-time update)
```

### 2. Payment & Inventory Flow
```
Payment Request (API)
  ↓
PaymentService (validation, gateway integration)
  ↓
Payment successful
  ↓
PaymentProcessed Event
  ↓
- InventoryDeductionService (auto reduce stock)
- UpdateTableStatus (mark as available)
- GenerateAuditLog
  ↓
Response to Frontend
```

### 3. Real-time Communication
```
WebSocket/Firebase Events:
- Table status changes (Empty → Occupied → Reserved)
- Kitchen order status (New → Cooking → Ready)
- Inventory alerts (Low stock)
- Payment confirmations
```

---

## API Response Format
All API responses follow consistent format:
```json
{
  "success": true/false,
  "message": "Operation message",
  "data": {...},
  "errors": {...},
  "meta": {
    "timestamp": "2026-04-25T10:30:00Z",
    "path": "/api/v1/...",
    "pagination": {...}
  }
}
```

---

## Key Design Patterns

1. **Repository Pattern**: Data access abstraction
2. **Service Pattern**: Business logic separation
3. **Event-Driven**: Decoupled components via events
4. **RBAC**: Role-Based Access Control for authorization
5. **Audit Logging**: All sensitive operations logged
6. **Queue Pattern**: Long-running tasks via jobs
7. **Transaction Pattern**: Atomic operations (Payment + Inventory)

---

## Security & Best Practices

- JWT Authentication for API
- Input validation via Form Requests
- Authorization via Policies
- CORS configuration
- Rate limiting
- Audit logging for sensitive operations
- Encrypted sensitive data (payments, employee info)
- HTTPS enforced

---

## Database Design Highlights

- **Transactions**: Payment + Inventory must be atomic
- **Indexes**: On frequently queried fields (orders.created_at, inventory.status)
- **Soft Deletes**: For employees, menu items (preserve history)
- **Audit Trail**: All operations logged for compliance
- **Relationships**: Polymorphic for flexibility (discounts on orders/items)
