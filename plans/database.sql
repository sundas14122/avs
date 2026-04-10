-- Database Schema for Automated Vulnerability Scanner
-- Run this in phpMyAdmin (DigitalOcean managed DB or self-hosted MySQL).

-- Create database
-- CREATE DATABASE avs_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullName VARCHAR(120) DEFAULT NULL,
    bio TEXT DEFAULT '',
    subscription_status VARCHAR(20) DEFAULT 'free',
    scans_remaining INT DEFAULT 0,
    expiry_date DATETIME DEFAULT NULL,
    scan_count INT DEFAULT 0,
    tfa_enabled TINYINT(1) DEFAULT 0,
    tfa_secret VARCHAR(64) DEFAULT NULL,
    tfa_recovery_codes TEXT DEFAULT NULL,
    email_notifications TINYINT(1) DEFAULT 1,
    security_alerts TINYINT(1) DEFAULT 1,
    theme VARCHAR(16) DEFAULT 'dark',
    reset_token_hash VARCHAR(64) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User sessions table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payment history table
CREATE TABLE IF NOT EXISTS payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    trx_id VARCHAR(100),
    proof_image VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Primary scan history table used by the PHP app
CREATE TABLE IF NOT EXISTS django_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_url VARCHAR(255) NOT NULL,
    scan_type VARCHAR(50),
    result_data JSON,
    status VARCHAR(20) DEFAULT 'pending',
    task_id VARCHAR(80) DEFAULT NULL,
    created_at DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_django_scans_user_created (user_id, created_at),
    INDEX idx_django_scans_task (task_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Optional legacy table
CREATE TABLE IF NOT EXISTS scan_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target VARCHAR(255) NOT NULL,
    scan_type VARCHAR(50),
    results JSON,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index for faster queries
CREATE INDEX idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions_userid ON user_sessions(user_id);
CREATE INDEX idx_payment_userid ON payment_history(user_id);
CREATE INDEX idx_scan_userid ON scan_results(user_id);

-- Safe alter statements for existing deployments (run once; ignore duplicate-column errors)
ALTER TABLE users ADD COLUMN IF NOT EXISTS fullName VARCHAR(120) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS scans_remaining INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS expiry_date DATETIME DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS scan_count INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS tfa_enabled TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS tfa_secret VARCHAR(64) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS tfa_recovery_codes TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_notifications TINYINT(1) DEFAULT 1;
ALTER TABLE users ADD COLUMN IF NOT EXISTS security_alerts TINYINT(1) DEFAULT 1;
ALTER TABLE users ADD COLUMN IF NOT EXISTS theme VARCHAR(16) DEFAULT 'dark';
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_hash VARCHAR(64) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expires_at DATETIME DEFAULT NULL;
