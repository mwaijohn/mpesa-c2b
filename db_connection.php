<?php
/**
 * db_connection.php - SQLite Database Connection Helper
 * 
 * This file provides a function to establish a connection to your SQLite database.
 */

function getDbConnection() {
    // Database file path - adjust as needed
    $dbFile = __DIR__ . '/database/mpesa_transactions.db';
    $dbDirectory = dirname($dbFile);
    
    // Create directory if it doesn't exist
    if (!is_dir($dbDirectory)) {
        mkdir($dbDirectory, 0755, true);
    }
    
    try {
        // Connect to SQLite database
        $conn = new SQLite3($dbFile);
        
        // Set some pragmas for better performance and security
        $conn->exec('PRAGMA journal_mode = WAL;'); // Write-Ahead Logging for better concurrency
        $conn->exec('PRAGMA synchronous = NORMAL;'); // Balance between safety and speed
        $conn->exec('PRAGMA foreign_keys = ON;'); // Enable foreign key constraints
        
        // Create tables if they don't exist
        createTables($conn);
        
        return $conn;
    } catch (Exception $e) {
        // Log the error
        $errorLog = 'logs/db_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logData = $timestamp . ' - Database connection failed: ' . $e->getMessage() . PHP_EOL;
        
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
        
        file_put_contents($errorLog, $logData, FILE_APPEND);
        
        // In production, don't expose error details
        die("Database connection failed. Please try again later.");
    }
}

/**
 * Create necessary tables if they don't exist
 */
function createTables($conn) {
    // Table for M-Pesa transactions
    $conn->exec('
        CREATE TABLE IF NOT EXISTS mpesa_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            trans_id TEXT UNIQUE NOT NULL,
            trans_time TEXT,
            trans_amount REAL NOT NULL,
            business_shortcode TEXT NOT NULL,
            bill_ref_number TEXT,
            invoice_number TEXT,
            org_account_balance TEXT,
            third_party_trans_id TEXT,
            msisdn TEXT,
            first_name TEXT,
            middle_name TEXT,
            last_name TEXT,
            transaction_type TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            processed INTEGER DEFAULT 0,
            processing_notes TEXT
        )
    ');
    
    // Create indexes for frequently queried fields
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_trans_id ON mpesa_transactions (trans_id)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_bill_ref ON mpesa_transactions (bill_ref_number)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_msisdn ON mpesa_transactions (msisdn)');
    
    // Table for customer accounts
    $conn->exec('
        CREATE TABLE IF NOT EXISTS accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            account_number TEXT UNIQUE NOT NULL,
            customer_name TEXT NOT NULL,
            phone_number TEXT,
            email TEXT,
            balance REAL DEFAULT 0.0,
            last_payment_date TEXT,
            last_payment_amount REAL,
            last_transaction_id TEXT,
            status TEXT DEFAULT "active",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Create indexes for customer account lookups
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_account ON accounts (account_number)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_phone ON accounts (phone_number)');
}