-- Padak CRM Migration v11 — Lead Generator
-- Run in phpMyAdmin SQL tab on padak_crm database

-- API key storage (one per CRM install)
CREATE TABLE IF NOT EXISTS lead_gen_settings (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    setting_key  VARCHAR(100) NOT NULL UNIQUE,
    setting_val  TEXT DEFAULT NULL,
    updated_by   INT DEFAULT NULL,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Usage tracking per search
CREATE TABLE IF NOT EXISTS lead_gen_usage (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    location     VARCHAR(200) NOT NULL,
    industry     VARCHAR(200) NOT NULL,
    result_count INT DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Generated lead results (holding area before importing to leads table)
CREATE TABLE IF NOT EXISTS lead_gen_results (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    place_id     VARCHAR(255) DEFAULT NULL,
    name         VARCHAR(255) NOT NULL,
    phone        VARCHAR(100) DEFAULT NULL,
    address      TEXT DEFAULT NULL,
    website      VARCHAR(500) DEFAULT NULL,
    rating       DECIMAL(2,1) DEFAULT NULL,
    location     VARCHAR(200) DEFAULT NULL,
    industry     VARCHAR(200) DEFAULT NULL,
    imported     TINYINT(1) DEFAULT 0,
    lead_id      INT DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed default monthly quota
INSERT IGNORE INTO lead_gen_settings (setting_key, setting_val) VALUES
('monthly_quota', '200'),
('google_api_key', '');