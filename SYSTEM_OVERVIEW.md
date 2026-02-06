# ResLaraVuePOS System Overview

## Architecture

### Backend (Laravel 11)
- RESTful API with Sanctum authentication
- 27+ database tables with full relationships
- Role-based access control (5 roles)
- PRA (Punjab Revenue Authority) integration
- Offline-first data sync
- Queue system for background jobs

### Frontend (Vue 3)
- Progressive Web App (PWA)
- Offline-capable with IndexedDB (Dexie.js)
- Real-time updates with polling/WebSocket
- Responsive design with Tailwind CSS
- State management with Pinia
- Multiple role-specific views

## User Roles

1. **Super Admin**
   - Manage all branches
   - Create/edit users
   - System configuration
   - Access all features

2. **Admin**
   - Manage single branch
   - View reports and analytics
   - Manage inventory and expenses
   - User management within branch

3. **Cashier**
   - Process payments
   - Manage POS sessions
   - Handle invoices
   - Apply discounts

4. **Waiter**
   - Take orders
   - Manage tables
   - Send to kitchen
   - Basic order management

5. **Kitchen**
   - View kitchen display
   - Update order status
   - Mark items ready
   - Priority management

## Key Features

### Order Management
- Dine-in, Takeaway, Delivery
- Table management
- Real-time kitchen updates
- Order modifications and voids
- Priority orders

### Payment Processing
- Multiple payment methods (Cash, Card, Mobile, Split)
- Tip handling
- Change calculation
- Receipt printing (thermal and browser)

### PRA Integration
- Automatic invoice submission
- QR code generation
- Fiscal code tracking
- Retry mechanism for failed submissions

### Offline Support
- Create orders offline
- Automatic sync when online
- Conflict resolution
- Queue management

### Inventory & Expenses
- Stock tracking
- Expense management
- Category-based organization
- Low stock alerts

### Reporting & Analytics
- Sales reports
- Staff performance
- Popular items
- Revenue analytics

## Database Schema

Main Tables:
- branches
- users
- pos_terminals
- pos_sessions
- tables
- customers
- categories
- menu_items
- menu_modifiers
- orders
- order_items
- invoices
- invoice_items
- payments
- tips
- discounts
- inventory_items
- expenses
- pra_logs
- audit_logs

## API Endpoints

### Authentication
- POST /api/auth/login
- POST /api/auth/login-pin
- POST /api/auth/logout
- GET /api/auth/user

### Menu
- GET /api/categories
- GET /api/menu-items
- GET /api/menu-modifiers

### Orders
- GET /api/orders
- POST /api/orders
- GET /api/orders/{id}
- PUT /api/orders/{id}
- PATCH /api/orders/{id}/status
- POST /api/orders/{id}/send-to-kitchen

### Invoices & Payments
- GET /api/invoices
- POST /api/orders/{id}/pay
- POST /api/payments

### POS Sessions
- GET /api/pos-sessions/active
- POST /api/pos-sessions
- POST /api/pos-sessions/{id}/close

### Admin
- GET /api/admin/dashboard
- GET /api/admin/reports

## Technology Stack

### Backend
- PHP 8.1+
- Laravel 11
- MySQL 8.0+
- Redis (optional)
- Laravel Sanctum
- Laravel Queue

### Frontend
- Vue 3 (Composition API)
- Vite 5
- Pinia (State Management)
- Vue Router 4
- Tailwind CSS 4
- Dexie.js (IndexedDB)
- Chart.js
- vite-plugin-pwa

### DevOps
- Git version control
- Composer for PHP dependencies
- npm for JS dependencies
- Artisan CLI commands

## Configuration Files

### Backend
- .env (environment variables)
- config/sanctum.php (authentication)
- config/cors.php (CORS settings)
- config/pra.php (PRA integration)
- config/database.php (database)

### Frontend
- .env (API URL, etc.)
- vite.config.js (build config, PWA)
- tailwind.config.js (styling)
