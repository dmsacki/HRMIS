# Mkombozi HRMIS

**Mkombozi HRMIS** is a Human Resource Management Information System for task allocation, performance evaluation, and employee management. It is designed for organizations to streamline HR processes, manage tasks, appraisals, and generate insightful reports.

## Features

- **User Authentication**: Secure login/logout for all users.
- **Role-Based Access Control**: Admin, Manager, and Employee roles with different permissions.
- **Task Management**:
  - Assign tasks to employees.
  - Track task status (Pending, In Progress, Completed, Overdue).
  - Employees can update status and add worklogs.
- **Performance Appraisals**:
  - Create yearly appraisal cycles.
  - Managers and employees can add appraisal criteria and scores.
  - Automatic calculation of final scores.
- **Yearly Agreements & Goals**:
  - Employees create yearly agreements and set goals.
  - Managers/Admins can approve or reject agreements.
- **Departments & Roles**: Manage departments and user roles.
- **Reporting**:
  - Export data to CSV.
  - View audit logs, appraisal scores, task summaries, and feedback.
- **Email Notifications**: Email alerts for task assignments and status updates (SMTP/PHPMailer support).
- **Responsive UI**: Modern, mobile-friendly interface using Bootstrap 5.

## Installation

1. **Clone the repository**  
   ```sh
   git clone https://github.com/your-org/mkombozi-hrmis.git
   ```

2. **Set up the database**
   - Create a MySQL database (default: `mkombozi_hrmis`).
   - Import the provided SQL schema (not included here).

3. **Configure the application**
   - Edit `config/database.php` with your database credentials.
   - Edit `config/mail.php` with your SMTP settings for email notifications.

4. **Install dependencies (optional, for PHPMailer)**
   - If you want to use SMTP, install PHPMailer via Composer:
     ```sh
     composer require phpmailer/phpmailer
     ```

5. **Seed the admin user**
   - Run `seed_admin.php` in your browser or CLI to create the default admin account:
     ```
     Email: admin@mkombozi.tz
     Password: Admin@123
     ```

6. **Start the server**
   - Place the project in your web server's root (e.g., `htdocs` for XAMPP).
   - Access via `http://localhost/HRMIS/`.

## Default Roles

- **Admin**: Full access to all features.
- **Manager**: Can manage tasks, appraisals, and agreements.
- **Employee**: Can view and update their own tasks, appraisals, and agreements.

## Folder Structure

```
HRMIS/
├── assets/
│   ├── css/
│   └── js/
├── config/
├── includes/
├── index.php
├── dashboard.php
├── ...
```

## Security

- Passwords are hashed using BCRYPT.
- Role checks and session validation on all pages.
- Prepared statements for all database queries.

## License

This project is for learning purpose.

---

*Developed by Doreen*
