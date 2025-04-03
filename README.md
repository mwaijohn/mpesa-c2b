# PHP MPesa C2B Integration

This project provides a PHP implementation for integrating with the MPesa API for C2B (Customer to Business) transactions. It includes scripts for authentication, URL registration, transaction simulation, and callback handling.

## Project Structure

- **auth.php**: Handles authentication with MPesa API.
- **composer.json**: Composer configuration file.
- **composer.lock**: Composer lock file.
- **config.php**: Configuration settings for the project.
- **confirmation.php**: Handles transaction confirmation callbacks.
- **daraja_c2b.php**: Main script for MPesa C2B integration.
- **db_connection.php**: Database connection setup.
- **index.php**: Entry point for the application.
- **register_urls_handler.php**: Handles URL registration with MPesa.
- **validation.php**: Handles transaction validation callbacks.
- **database/**
  - **mpesa_transactions.db**: SQLite database for storing transactions.
- **logs/**
  - **simulate.log**: Logs for transaction simulations.
  - **url_registration_failures.log**: Logs for failed URL registrations.
  - **url_registration_success.log**: Logs for successful URL registrations.
  - **url_registration.log**: General URL registration logs.
- **vendor/**: Composer dependencies.

## Installation

### Install Dependencies

Install dependencies using Composer:
```bash
composer install
```

### Configure the Project

1. Update `config.php` with your MPesa API credentials and other settings.

### Set Up the Database

Ensure the `database/mpesa_transactions.db` file exists and is writable.

### Set Up File Permissions

Ensure the `logs/` directory is writable for logging purposes.

## Usage

### 1. Register URLs

Run the `register_urls_handler.php` script to register your confirmation and validation URLs with MPesa:
```bash
php register_urls_handler.php
```

### 2. Simulate Transactions

Use the `daraja_c2b.php` script to simulate C2B transactions:
```bash
php daraja_c2b.php
```

### 3. Handle Callbacks

- **confirmation.php**: Handles confirmation callbacks from MPesa.
- **validation.php**: Handles validation callbacks from MPesa.

### 4. View Logs

Check the `logs/` directory for logs related to URL registration and transaction simulations.

## Project Dependencies

This project uses the following dependencies managed via Composer:

- **Firebase PHP-JWT**: For token generation.
- **Paragonie**: For cryptographic utilities.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Feel free to fork the repository and submit pull requests for improvements or bug fixes.

## Support

For any issues or questions, please contact the project maintainer.
