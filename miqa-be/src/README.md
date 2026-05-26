# MIQA Learning Backend

<p align="center">
  <strong>A Comprehensive Educational Platform API</strong>
</p>

<p align="center">
  An advanced Laravel 12 backend system for managing educational institutions, classrooms, subjects, exams, and student assessments with role-based access control.
</p>

---

## 📑 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
    - [Quick Start](#quick-start)
    - [Development Setup](#development-setup)
    - [Production Setup](#production-setup)
    - [Database Configuration](#database-configuration)
    - [Environment Variables](#environment-variables)
- [Project Structure](#project-structure)
- [Usage](#usage)
    - [Running the Server](#running-the-server)
    - [Running Tests](#running-tests)
    - [Development Commands](#development-commands)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Authentication](#authentication)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

MIQA Learning Backend is a robust, scalable API built with **Laravel 12** and **PHP 8.2+** designed for educational institutions. It provides comprehensive management of:

- **Users & Roles**: Multi-role system (Manager, Teacher, Student) with fine-grained permissions
- **Educational Content**: Topics, Subjects, and ClassRooms management
- **Exam System**: Complete exam creation, management, and evaluation workflow
- **Student Assessment**: Detailed tracking of exam attempts and performance
- **Analytics**: Comprehensive statistics and performance tracking
- **File Management**: Secure upload and management of educational documents

---

## Features

✨ **Core Features**

- 🔐 **JWT/Token-Based Authentication** via Laravel Sanctum
- 👥 **Role-Based Access Control** using Spatie Laravel Permission
- 📚 **Subject Management** with teacher assignments and topics
- 🏫 **Classroom Management** with student enrollments
- 📝 **Exam System** with dynamic question types
- 📊 **Performance Analytics** and statistics generation
- 📄 **Document Management** for rapport uploads
- 🔍 **Advanced Search** capabilities across entities
- 🛡️ **Input Validation** and error handling
- ⚡ **High Performance** with caching and optimization

---

## Tech Stack

| Component           | Technology                            |
| ------------------- | ------------------------------------- |
| **Framework**       | Laravel 12.x                          |
| **PHP Version**     | 8.2+                                  |
| **Database**        | SQLite (default) / MySQL / PostgreSQL |
| **Authentication**  | Laravel Sanctum                       |
| **Authorization**   | Spatie Laravel Permission             |
| **Package Manager** | Composer                              |
| **Build Tool**      | Vite                                  |
| **Testing**         | PHPUnit                               |

### Dependencies

- `laravel/framework`: ^12.0
- `laravel/sanctum`: ^4.0
- `spatie/laravel-permission`: ^6.21
- `laravel/tinker`: ^2.10.1

---

## System Requirements

### Minimum Requirements

- **PHP**: 8.2.0 or higher
- **Composer**: 2.0 or higher
- **Node.js**: 18.0 or higher (for frontend assets)
- **SQLite**: 3.0+ (default database)

### Recommended Requirements

- **PHP**: 8.3 or higher
- **OS**: Linux/macOS (or WSL2 on Windows)
- **RAM**: 4GB minimum
- **Database**: MySQL 8.0+ or PostgreSQL 13+ for production

### Supported Databases

- SQLite (Default, suitable for development)
- MySQL 5.7+
- PostgreSQL 10+
- Microsoft SQL Server

---

## Installation

### Quick Start

Get the application running in 5 minutes:

```bash
# 1. Clone the repository
git clone <repository-url>
cd miqa-learning-be/src

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Run migrations
php artisan migrate

# 6. Start the server
php artisan serve
```

The API will be available at `http://localhost:8000/api`

---

### Development Setup

For local development with all features:

#### 1. Prerequisites Installation

**On macOS (using Homebrew):**

```bash
brew install php@8.2 composer node
```

**On Ubuntu/Debian:**

```bash
sudo apt-get update
sudo apt-get install php8.2 php8.2-curl php8.2-sqlite3 php8.2-xml php8.2-mbstring
sudo apt-get install composer nodejs npm
```

**On Windows:**

- Download and install PHP 8.2 from [php.net](https://www.php.net/downloads)
- Install Composer from [getcomposer.org](https://getcomposer.org/download)
- Install Node.js from [nodejs.org](https://nodejs.org)

#### 2. Clone Repository

```bash
git clone <repository-url>
cd miqa-learning-be/src
```

#### 3. Install PHP Dependencies

```bash
composer install
```

#### 4. Install JavaScript Dependencies

```bash
npm install
```

#### 5. Create Environment File

```bash
cp .env.example .env
```

#### 6. Generate Application Key

```bash
php artisan key:generate
```

Output:

```
Application key set to base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

#### 7. Configure Database

The default `.env` is configured for SQLite. For development, this usually works out of the box.

To verify SQLite is ready:

```bash
touch database/database.sqlite
```

#### 8. Run Database Migrations

```bash
php artisan migrate
```

Output:

```
Running migrations...
  2024_01_15_100000_create_users_table ..................... 10ms DONE
  2024_01_15_100001_create_posts_table ..................... 15ms DONE
  ...
```

#### 9. Create Application Roles and Permissions

```bash
php artisan tinker
>>> DB::table('roles')->insert([
    ['name' => 'admin', 'guard_name' => 'api'],
    ['name' => 'manager', 'guard_name' => 'api'],
    ['name' => 'teacher', 'guard_name' => 'api'],
    ['name' => 'student', 'guard_name' => 'api'],
]);
>>> exit
```

#### 10. (Optional) Seed Sample Data

```bash
php artisan db:seed
```

#### 11. Build Frontend Assets

```bash
npm run build
```

For development with hot reload:

```bash
npm run dev
```

#### 12. Start Development Server

```bash
# Using Laravel Artisan
php artisan serve

# Or using the dev script (runs server, queue, logs, and Vite together)
composer run dev
```

The API will be available at:

- **API**: `http://localhost:8000/api`
- **Documentation**: `http://localhost:8000/docs` (if available)

---

### Production Setup

#### 1. Deployment Preparation

```bash
# Clone repository
git clone <repository-url>
cd miqa-learning-be/src

# Install dependencies (without dev dependencies)
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm install

# Build production assets
npm run build
```

#### 2. Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` with production settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=miqa_learning
DB_USERNAME=app_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### 3. Generate Security Keys

```bash
php artisan key:generate
php artisan storage:link
```

#### 4. Database Setup

```bash
# Run migrations on production database
php artisan migrate --force

# Seed essential data
php artisan db:seed --force
```

#### 5. Set Proper Permissions

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

#### 6. Deploy with Docker (Recommended)

See `docker/Dockerfile` for containerization setup.

```bash
docker build -t miqa-learning-be .
docker run -p 8000:8000 miqa-learning-be
```

#### 7. Configure Web Server

**Nginx Configuration:**

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /path/to/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Apache Configuration:**

Ensure `.htaccess` is properly configured (included in Laravel by default).

#### 8. SSL/HTTPS Setup

Using Let's Encrypt with Certbot:

```bash
sudo certbot certonly --webroot -w /path/to/public -d api.yourdomain.com
# Update Nginx/Apache config with certificate paths
```

---

### Database Configuration

#### SQLite (Default - Development)

No additional setup needed. Database file is at `database/database.sqlite`.

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

#### MySQL

1. Create database and user:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE miqa_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'miqa_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON miqa_learning.* TO 'miqa_user'@'localhost';
FLUSH PRIVILEGES;
```

2. Update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=miqa_learning
DB_USERNAME=miqa_user
DB_PASSWORD=secure_password
```

3. Run migrations:

```bash
php artisan migrate
```

#### PostgreSQL

1. Create database and user:

```bash
createdb miqa_learning
createuser miqa_user -P
```

2. Grant privileges:

```sql
ALTER ROLE miqa_user WITH CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE miqa_learning TO miqa_user;
```

3. Update `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=miqa_learning
DB_USERNAME=miqa_user
DB_PASSWORD=secure_password
```

4. Run migrations:

```bash
php artisan migrate
```

---

### Environment Variables

#### Critical Variables

```env
# Application
APP_NAME=MIQA Learning
APP_ENV=production          # development|production
APP_DEBUG=false             # true|false (never true in production)
APP_URL=http://localhost    # Your application URL
APP_KEY=base64:xxxx...      # Generated with php artisan key:generate

# Database
DB_CONNECTION=sqlite        # sqlite|mysql|pgsql|sqlsrv
DB_DATABASE=database.sqlite
DB_USERNAME=root
DB_PASSWORD=

# Cache & Session
CACHE_DRIVER=database       # database|redis|file
SESSION_DRIVER=database     # database|redis|cookie
```

#### Optional Variables

```env
# Mail
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@example.com

# Cache
CACHE_STORE=database
CACHE_PREFIX=miqa_

# Queue
QUEUE_CONNECTION=database   # database|redis|sync

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug             # emergency|alert|critical|error|warning|notice|info|debug
```

#### Full Environment Example

See `.env.example` for complete configuration.

---

## Project Structure

```
miqa-learning-be/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── SubjectController.php
│   │   │   │   ├── ClassRoomController.php
│   │   │   │   └── ... (other controllers)
│   │   │   └── Controller.php
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Subject.php
│   │   ├── ClassRoom.php
│   │   ├── Student.php
│   │   └── ... (other models)
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── SubjectRepository.php
│   │   └── ... (data access layer)
│   ├── Services/
│   │   ├── UserService.php
│   │   ├── SubjectService.php
│   │   └── ... (business logic)
│   └── Providers/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── database.sqlite
├── public/
│   └── index.php
├── resources/
│   ├── js/
│   └── css/
├── routes/
│   ├── api.php         # API routes
│   ├── web.php
│   └── console.php
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── vendor/
├── .env
├── .env.example
├── composer.json
├── package.json
└── README.md
```

### Key Directories

| Directory                  | Purpose                                        |
| -------------------------- | ---------------------------------------------- |
| `app/Models`               | Eloquent models representing database entities |
| `app/Http/Controllers/Api` | API endpoint controllers                       |
| `app/Repositories`         | Data access layer (repository pattern)         |
| `app/Services`             | Business logic and service classes             |
| `routes/api.php`           | API route definitions                          |
| `database/migrations`      | Database schema changes                        |
| `database/seeders`         | Database seeding scripts                       |
| `storage/app`              | User uploaded files                            |
| `tests`                    | Unit and feature tests                         |

---

## Usage

### Running the Server

**Development Mode:**

```bash
php artisan serve
# Runs on http://localhost:8000
```

**With Queue & Logs Monitoring:**

```bash
composer run dev
# Runs server, queue listener, and log viewer concurrently
```

**Custom Port:**

```bash
php artisan serve --port=8080 --host=0.0.0.0
```

### Running Tests

**Run All Tests:**

```bash
composer test
```

**Run Specific Test File:**

```bash
php artisan test tests/Feature/AuthTest.php
```

**Run Tests with Coverage:**

```bash
php artisan test --coverage
```

**Run Unit Tests Only:**

```bash
php artisan test tests/Unit
```

**Run Feature Tests Only:**

```bash
php artisan test tests/Feature
```

### Development Commands

**Database Operations:**

```bash
# Run migrations
php artisan migrate

# Rollback latest migration
php artisan migrate:rollback

# Reset database
php artisan migrate:reset

# Seed database
php artisan db:seed

# Refresh database (migrate:reset + migrate + seed)
php artisan migrate:refresh --seed
```

**Cache & Configuration:**

```bash
# Clear all caches
php artisan cache:clear

# Clear configuration cache
php artisan config:clear

# Cache configuration
php artisan config:cache

# Clear route cache
php artisan route:clear
```

**Code Quality:**

```bash
# Format code with Pint
php artisan pint

# Check code style
php artisan pint --test
```

**Interactive Shell:**

```bash
php artisan tinker
# Allows interactive PHP shell for testing
```

**Generate New Resources:**

```bash
# Generate model with migration
php artisan make:model ModelName -m

# Generate migration
php artisan make:migration create_table_name

# Generate controller
php artisan make:controller Api/ResourceController --api

# Generate service class
php artisan make:service ResourceService
```

---

## API Documentation

Complete API documentation is available in [API_DOCUMENTATION.md](./API_DOCUMENTATION.md).

### Quick API Examples

#### Authentication

**Login:**

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

**Response:**

```json
{
    "token": "1|abcdefghijklmnopqrstuvwxyz...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "roles": ["manager"]
    }
}
```

#### Get Current User

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Create Subject

```bash
curl -X POST http://localhost:8000/api/subjects \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Calculus",
    "tagline": "Advanced Mathematics",
    "topic_id": 1,
    "teacher_id": 2
  }'
```

#### List Classrooms

```bash
curl -X GET http://localhost:8000/api/class-rooms \
  -H "Authorization: Bearer YOUR_TOKEN"
```

More examples available in [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)

---

## Database Schema

### Core Tables

#### users

```sql
- id (Primary Key)
- name (String)
- email (String, Unique)
- email_verified_at (DateTime)
- password (String)
- photo (String, nullable)
- gender (String, nullable)
- timestamps
```

#### roles

```sql
- id (Primary Key)
- name (String, Unique)
- guard_name (String)
- timestamps
```

#### subjects

```sql
- id (Primary Key)
- name (String)
- tagline (String, nullable)
- photo (String, nullable)
- content (String, nullable)
- about (String, nullable)
- topic_id (Foreign Key → topics)
- teacher_id (Foreign Key → users)
- timestamps
```

#### class_rooms

```sql
- id (Primary Key)
- name (String)
- description (String, nullable)
- timestamps
```

#### students

```sql
- id (Primary Key)
- user_id (Foreign Key → users)
- roll_number (String, nullable)
- timestamps
```

#### subject_exams

```sql
- id (Primary Key)
- subject_id (Foreign Key → subjects)
- title (String)
- description (String, nullable)
- duration (Integer)
- total_marks (Integer)
- passing_marks (Integer)
- status (Enum: draft|active|closed)
- timestamps
```

#### exam_attempts

```sql
- id (Primary Key)
- student_id (Foreign Key → students)
- subject_exam_id (Foreign Key → subject_exams)
- start_time (DateTime)
- end_time (DateTime, nullable)
- status (Enum: started|completed)
- obtained_marks (Decimal)
- timestamps
```

For complete schema, run:

```bash
php artisan migrate --seed
```

---

## Authentication

### Token-Based Authentication

The API uses **Laravel Sanctum** for token-based authentication.

**Login Flow:**

1. User sends credentials to `/api/login`
2. Server validates credentials
3. Server returns bearer token
4. Client includes token in Authorization header for subsequent requests

**Token Storage (Frontend):**

```javascript
// After login, store token
const response = await fetch('/api/login', {...});
const data = await response.json();
localStorage.setItem('auth_token', data.token);

// Use token in requests
const token = localStorage.getItem('auth_token');
fetch('/api/user', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

**Token Expiration:**

- Tokens don't expire by default with Sanctum
- Configure expiration in `config/sanctum.php`
- Implement token refresh logic on frontend

---

## Troubleshooting

### Common Issues

#### Issue: "No application encryption key has been specified"

**Solution:**

```bash
php artisan key:generate
```

#### Issue: "Driver (...) not supported"

**Solution:** Ensure database driver is installed:

```bash
# For MySQL
php -m | grep mysqli  # or pdo_mysql

# For PostgreSQL
php -m | grep pgsql   # or pdo_pgsql

# Install if missing (Ubuntu):
sudo apt-get install php8.2-mysql
```

#### Issue: "Base table or view not found" during migration

**Solution:**

```bash
# Clear cached routes/config
php artisan route:clear
php artisan config:clear

# Re-run migrations
php artisan migrate:fresh
```

#### Issue: Permission denied on storage directory

**Solution:**

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

#### Issue: CORS errors when accessing from frontend

**Solution:** Configure CORS in `config/cors.php`:

```php
'allowed_origins' => ['http://localhost:3000', 'https://yourdomain.com'],
'allowed_methods' => ['*'],
'exposed_headers' => ['Content-Disposition'],
```

#### Issue: 500 errors in production

**Solution:**

```bash
# Check logs
tail -f storage/logs/laravel.log

# Enable debug temporarily
# In .env: APP_DEBUG=true

# Check Laravel cache
php artisan cache:clear
php artisan config:clear

# Check permissions
sudo chown -R www-data:www-data /var/www/html
```

### Debug Mode

Enable debug mode for detailed error messages (development only):

```env
APP_DEBUG=true
```

View logs in real-time:

```bash
tail -f storage/logs/laravel.log
```

Or using Laravel Pail:

```bash
php artisan pail
```

---

## Contributing

We welcome contributions! Please follow these steps:

1. **Fork the repository**

    ```bash
    git clone https://github.com/yourusername/miqa-learning-be.git
    ```

2. **Create a feature branch**

    ```bash
    git checkout -b feature/your-feature-name
    ```

3. **Make your changes**
    - Follow PSR-12 coding standards
    - Add tests for new functionality
    - Update documentation

4. **Test your changes**

    ```bash
    composer test
    php artisan pint
    ```

5. **Commit with clear messages**

    ```bash
    git commit -m "Add feature: description"
    ```

6. **Push to your fork**

    ```bash
    git push origin feature/your-feature-name
    ```

7. **Create a Pull Request**
    - Describe changes clearly
    - Reference related issues
    - Request reviewer

### Development Standards

- **Code Style**: PSR-12 with Pint
- **Testing**: PHPUnit (minimum 80% coverage)
- **Documentation**: PHPDoc comments for public methods
- **Git**: Conventional commits format

---

## License

This project is licensed under the **MIT License** - see [LICENSE](LICENSE) file for details.

---

## Support

For issues, questions, or suggestions:

- 📧 **Email**: support@example.com
- 💬 **Issues**: [GitHub Issues](https://github.com/yourusername/miqa-learning-be/issues)
- 📖 **Documentation**: [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)
- 🐛 **Bug Reports**: [Report a Bug](https://github.com/yourusername/miqa-learning-be/issues/new?template=bug_report.md)

---

## Quick Links

- [API Documentation](./API_DOCUMENTATION.md)
- [Laravel Documentation](https://laravel.com/docs)
- [Sanctum Docs](https://laravel.com/docs/sanctum)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)

---

## Version

**Current Version**: 1.2.0  
**Last Updated**: 2024-02-20  
**PHP**: 8.2+  
**Laravel**: 12.x

---

<p align="center">
  Made with ❤️ for educational excellence
</p>
