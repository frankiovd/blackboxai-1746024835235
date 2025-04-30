# Gym Management System

A comprehensive web application for managing gym activities, including workout plans, diet plans, and progress tracking. Built with PHP, MySQL, and modern frontend technologies.

## Features

### For Trainers
- Create and manage workout plans for clients
- Create and manage diet plans for clients
- Track client progress
- View client statistics and measurements
- Dashboard with overview of all clients

### For Clients
- View assigned workout plans
- View assigned diet plans
- Track progress (weight, body fat, measurements)
- Upload progress photos
- View progress history with charts

## Technologies Used

- PHP 7.4+
- MySQL 5.7+
- Tailwind CSS
- Chart.js for data visualization
- Font Awesome icons
- Google Fonts

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/gym-management-system.git
cd gym-management-system
```

2. Create a MySQL database:
```sql
CREATE DATABASE gym_management;
```

3. Import the database schema:
```bash
mysql -u your_username -p gym_management < schema.sql
```

4. Configure database connection:
Edit `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'gym_management');
```

5. Set up the uploads directory:
```bash
mkdir uploads
mkdir uploads/progress
chmod 777 uploads/progress
```

6. Configure your web server:
Point your web server's document root to the project directory.

## Directory Structure

```
gym-management/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   └── validation.php
├── php/
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── logout.php
│   ├── trainers/
│   │   ├── dashboard.php
│   │   ├── create_workout.php
│   │   └── create_diet.php
│   └── clients/
│       ├── dashboard.php
│       ├── add_progress.php
│       └── view_progress.php
├── uploads/
│   └── progress/
├── index.php
└── README.md
```

## Security Features

- Password hashing using PHP's password_hash()
- CSRF protection
- Input validation and sanitization
- Prepared statements for database queries
- Role-based access control
- Secure session management
- File upload validation

## Usage

1. Register as either a trainer or client
2. Log in with your credentials
3. Access your role-specific dashboard

### For Trainers:
- Create workout plans for clients
- Create diet plans for clients
- Monitor client progress

### For Clients:
- View assigned plans
- Track your progress
- Upload progress photos
- View progress history and charts

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Tailwind CSS for the UI components
- Chart.js for data visualization
- Font Awesome for icons
