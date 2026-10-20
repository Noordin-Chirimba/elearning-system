# E-Learning System

A PHP and MySQL e-learning platform built as a portfolio project. The repository contains student and instructor account flows, course content, assignments, quizzes, grading, messaging, and payment-integration scaffolding.

## Technology

- PHP
- MySQL / MariaDB
- Composer-managed PHP packages
- HTML, CSS, and JavaScript
- Stripe Checkout integration scaffolding
- PHPMailer SMTP email delivery

## Local setup

1. Install PHP, MySQL/MariaDB, and Composer.
2. Clone this repository and install dependencies with `composer install`.
3. Create a local database named `e-learningsystem`.
4. Review `e-learningsystem.sql` before importing it. It is a development schema/sample dump, not production data.
5. Configure database, SMTP, and Stripe settings in your server environment. See `.env.example` for the variable names expected by the code. The application does not automatically load a `.env` file; configure variables in Apache/PHP-FPM, your local environment, or your deployment platform.
6. Configure `APP_BASE_URL` for your local URL. Payment integration requires valid Stripe keys and a matching webhook signing secret.
7. Run the application through a PHP-capable web server.

## Security notes

- Never commit real passwords, SMTP app passwords, API keys, webhook signing secrets, or production database credentials.
- Rotate any credential that has previously been committed publicly; deleting it from the current branch does not erase Git history.
- Use test-mode payment credentials during development.
- Treat uploaded documents and sample database records as potentially sensitive; only use files and records you have permission to publish.

## Project status

This is a learning/portfolio project. Review and test authentication, authorization, upload handling, payment flows, and database configuration before using it with real users or deploying it publicly.
