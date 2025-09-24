# Product Inventory & Loaning System

A comprehensive product inventory and loaning management system built with PHP and MySQL. Designed for organizations to efficiently track products, manage user access, and handle equipment loans with an intuitive web interface.

## Features

### Product Management
- Add, edit, and delete products with detailed specifications
- Track serial numbers, categories, and ownership information
- Monitor product status (available/borrowed)
- Support for multiple product categories (Headsets, Keyboards, Mice, etc.)

### User Management
- Role-based access control (Admin/User)
- Secure user registration and authentication
- User borrowing history tracking

### Loaning System
- Simple product borrowing workflow
- Automatic due date calculation (30-day default)
- Return processing with status updates
- Borrowing history and analytics

### Dashboard & Analytics
- Admin dashboard with key metrics
- Borrowing statistics and trends
- Overdue loan tracking
- Export functionality for reporting

## Getting Started

### Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- Modern web browser

### Installation
1. Clone or download the project files
2. Place files in your XAMPP's `htdocs` directory
3. Start Apache and MySQL services in XAMPP Control Panel
4. Navigate to `http://localhost/inventory-management-system/`
5. The system will automatically create the database and tables

### Default Admin Access
- **Username:** `admin`
- **Password:** `admin123`

*Note: Change the default admin password after first login*

## System Architecture

### Frontend Technologies
- **HTML5 & CSS3** - Modern web standards
- **Bootstrap 5.3.0** - Responsive design framework
- **Font Awesome 6.0.0** - Icon library
- **JavaScript** - Interactive functionality

### Backend Technologies
- **PHP 7.4+** - Server-side logic
- **MySQL** - Database management
- **Session Management** - User authentication
- **Prepared Statements** - SQL injection protection

### Database Schema
- **`users`** - User accounts (id, name, username, password_hash, role, created_at)
- **`products`** - Product inventory (id, product_name, category, serial_number, main_owner, prototype_version, description, status, created_at)
- **`borrows`** - Loan records (id, product_id, user_id, borrow_date, return_date, actual_return_date, status)

## User Roles & Permissions

### Regular Users
- Browse product catalog with search and filtering
- View detailed product information
- Borrow available products
- Track personal borrowing history
- Return borrowed items

### Administrators
- Full product management (CRUD operations)
- User account management
- Loan processing and oversight
- System analytics and reporting
- Export data to Excel format
- Monitor overdue loans

## Product Categories

The system supports various product categories:
- Headset (PCD/MCD)
- Keyboard
- Mouse & Mouse Mat
- Speaker
- Smart Home devices
- Broadcaster equipment
- Systems & Accessories
- Controllers

## Security Features

- **Password Hashing** - Secure password storage using PHP's password_hash()
- **SQL Injection Prevention** - All database queries use prepared statements
- **XSS Protection** - User input sanitization with htmlspecialchars()
- **Session Security** - Secure session management
- **Role-based Access Control** - Restricted admin functionality

## Development

### File Structure
```
inventory-management-system/
├── admin/              # Admin-only functionality
├── auth/              # Authentication (login/register/logout)
├── products/          # Product management
│   └── borrows/       # Borrowing system
├── includes/          # Shared components (header/footer)
├── assets/           # CSS, images, and static files
├── config/           # Database and application configuration
└── index.php         # Landing page
```

### Customization
- Modify CSS variables in `assets/css/style.css` for theming
- Update product categories in relevant PHP files
- Adjust borrowing period in borrowing logic

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source. Feel free to use and modify for your organization's needs.
