-- =============================================
-- SMART WATER GUARDIAN - COMPLETE DATABASE SCHEMA
-- Firebase + MySQL Dual Database
-- =============================================

-- Create database
CREATE DATABASE IF NOT EXISTS smart_water_guardian;
USE smart_water_guardian;

-- =============================================
-- USERS TABLE (Syncs with Firebase)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    meter_number VARCHAR(50),
    role ENUM('consumer', 'municipal_admin', 'system_admin') DEFAULT 'consumer',
    is_active BOOLEAN DEFAULT TRUE,
    is_approved TINYINT(1) DEFAULT 0,
    approval_date DATETIME NULL,
    approved_by VARCHAR(255) NULL,
    last_login DATETIME,
    firebase_synced BOOLEAN DEFAULT FALSE,
    firebase_key VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_meter_number (meter_number),
    INDEX idx_approval_status (is_approved, is_active),
    INDEX idx_firebase_synced (firebase_synced)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PROPERTIES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) NOT NULL,
    property_name VARCHAR(255) NOT NULL,
    address TEXT,
    meter_id VARCHAR(50) UNIQUE NOT NULL,
    property_type ENUM('residential', 'commercial', 'municipal') DEFAULT 'residential',
    is_active BOOLEAN DEFAULT TRUE,
    firebase_synced BOOLEAN DEFAULT FALSE,
    firebase_key VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_meter_id (meter_id),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (firebase_uid) REFERENCES users(firebase_uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DEVICES (Smart Meters) TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id VARCHAR(50) UNIQUE NOT NULL,
    serial_number VARCHAR(100) UNIQUE,
    model VARCHAR(50) DEFAULT 'ESP32-YF-S201',
    property_id INT,
    firmware_version VARCHAR(20) DEFAULT '1.0.0',
    status ENUM('online', 'offline', 'maintenance', 'error') DEFAULT 'offline',
    battery_level INT DEFAULT 100,
    signal_strength INT DEFAULT 0,
    last_communication DATETIME,
    firebase_synced BOOLEAN DEFAULT FALSE,
    firebase_key VARCHAR(255),
    installed_at DATETIME,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_meter_id (meter_id),
    INDEX idx_status (status),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- WATER READINGS TABLE (From Smart Meters)
-- =============================================
CREATE TABLE IF NOT EXISTS water_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id VARCHAR(50) NOT NULL,
    flow_rate DECIMAL(10,2) DEFAULT 0,
    volume DECIMAL(10,2) DEFAULT 0,
    pressure DECIMAL(10,2),
    temperature DECIMAL(5,2),
    reading_time DATETIME NOT NULL,
    firebase_key VARCHAR(255),
    firebase_synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_meter_id (meter_id),
    INDEX idx_reading_time (reading_time),
    INDEX idx_meter_time (meter_id, reading_time),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (meter_id) REFERENCES devices(meter_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DAILY USAGE SUMMARY TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS daily_usage_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    total_volume DECIMAL(10,2) DEFAULT 0,
    avg_flow_rate DECIMAL(10,2) DEFAULT 0,
    peak_flow_rate DECIMAL(10,2) DEFAULT 0,
    reading_count INT DEFAULT 0,
    firebase_synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_meter_date (meter_id, date),
    INDEX idx_meter_id (meter_id),
    INDEX idx_date (date),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (meter_id) REFERENCES devices(meter_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ALERTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) NOT NULL,
    property_id INT,
    alert_type ENUM('leak', 'high_usage', 'low_battery', 'device_offline', 'threshold_exceeded', 'system', 'message') NOT NULL,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'warning',
    title VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_acknowledged BOOLEAN DEFAULT FALSE,
    firebase_key VARCHAR(255),
    firebase_synced BOOLEAN DEFAULT FALSE,
    acknowledged_at DATETIME,
    resolved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_alert_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_is_read (is_read),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (firebase_uid) REFERENCES users(firebase_uid) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ALERT THRESHOLDS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS alert_thresholds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) NOT NULL,
    property_id INT,
    threshold_type ENUM('daily_limit', 'hourly_limit', 'leak_duration', 'flow_rate', 'pressure') NOT NULL,
    threshold_value DECIMAL(10,2) NOT NULL,
    notification_methods JSON,
    is_active BOOLEAN DEFAULT TRUE,
    firebase_key VARCHAR(255),
    firebase_synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_threshold_type (threshold_type),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (firebase_uid) REFERENCES users(firebase_uid) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TARIFFS TABLE (For Municipal Billing)
-- =============================================
CREATE TABLE IF NOT EXISTS tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    municipality VARCHAR(255) NOT NULL,
    tier_name VARCHAR(100) NOT NULL,
    min_volume DECIMAL(10,2) DEFAULT 0,
    max_volume DECIMAL(10,2),
    rate_per_kl DECIMAL(10,2) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE,
    is_active BOOLEAN DEFAULT TRUE,
    firebase_synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_municipality (municipality),
    INDEX idx_effective_date (effective_from, effective_to),
    INDEX idx_firebase_synced (firebase_synced)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- BILLING TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) NOT NULL,
    property_id INT,
    billing_month DATE NOT NULL,
    total_volume DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    tariff_applied JSON,
    is_paid BOOLEAN DEFAULT FALSE,
    paid_at DATETIME,
    invoice_number VARCHAR(50) UNIQUE,
    payment_method VARCHAR(50),
    firebase_key VARCHAR(255),
    firebase_synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_billing_month (billing_month),
    INDEX idx_is_paid (is_paid),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (firebase_uid) REFERENCES users(firebase_uid) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- AUDIT LOGS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255),
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100),
    entity_id VARCHAR(255),
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    firebase_key VARCHAR(255),
    firebase_synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (firebase_uid) REFERENCES users(firebase_uid) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- USER FEEDBACK / REVIEWS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) NOT NULL,
    property_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    is_public BOOLEAN DEFAULT TRUE,
    is_approved BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    firebase_key VARCHAR(255),
    firebase_synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_rating (rating),
    INDEX idx_is_public (is_public),
    INDEX idx_is_approved (is_approved),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (firebase_uid) REFERENCES users(firebase_uid) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- MESSAGES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) NOT NULL,
    from_uid VARCHAR(255) NOT NULL,
    from_name VARCHAR(255),
    from_email VARCHAR(255),
    subject VARCHAR(255),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    is_reply BOOLEAN DEFAULT FALSE,
    original_message_id VARCHAR(255),
    firebase_key VARCHAR(255),
    firebase_synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_from_uid (from_uid),
    INDEX idx_is_read (is_read),
    INDEX idx_firebase_synced (firebase_synced),
    FOREIGN KEY (firebase_uid) REFERENCES users(firebase_uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SYNC LOG TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(255) NOT NULL,
    firebase_key VARCHAR(255),
    mysql_id INT,
    sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
    error_message TEXT,
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    synced_at DATETIME,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- EMAIL NOTIFICATIONS TABLE (New)
-- =============================================
CREATE TABLE IF NOT EXISTS email_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255),
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT,
    type VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending',
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PDF REPORTS TABLE (New)
-- =============================================
CREATE TABLE IF NOT EXISTS pdf_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) NOT NULL,
    month VARCHAR(20),
    year INT,
    file_path VARCHAR(500),
    file_size INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_month_year (month, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- INSERT SAMPLE DATA
-- =============================================

-- Insert default tariffs
INSERT INTO tariffs (municipality, tier_name, min_volume, max_volume, rate_per_kl, effective_from) VALUES
('City of Johannesburg', 'Tier 1', 0, 6000, 18.50, '2026-01-01'),
('City of Johannesburg', 'Tier 2', 6001, 20000, 25.00, '2026-01-01'),
('City of Johannesburg', 'Tier 3', 20001, 40000, 35.00, '2026-01-01'),
('City of Johannesburg', 'Tier 4', 40001, NULL, 45.00, '2026-01-01');

-- Insert sample admin user (for testing)
INSERT INTO users (firebase_uid, email, first_name, last_name, phone, address, role, is_active, is_approved, firebase_synced) VALUES
('test_user_123', 'admin@smartwater.com', 'Admin', 'User', '0821234567', '123 Main Street, Johannesburg', 'system_admin', 1, 1, 1);

-- Insert sample consumer user (pending approval)
INSERT INTO users (firebase_uid, email, first_name, last_name, phone, address, role, is_active, is_approved, firebase_synced) VALUES
('consumer_uid_456', 'consumer@test.com', 'Sipho', 'Zulu', '0829876543', '123 Vilakazi Street, Soweto', 'consumer', 1, 0, 1);

-- Insert sample property
INSERT INTO properties (firebase_uid, property_name, address, meter_id, property_type, firebase_synced) VALUES
('test_user_123', 'Main Residence', '123 Main Street, Johannesburg', 'MTR-2026-001', 'residential', 1);

-- Insert sample device
INSERT INTO devices (meter_id, serial_number, model, property_id, status, battery_level, last_communication, firebase_synced) VALUES
('MTR-2026-001', 'SN-2026-001', 'ESP32-YF-S201', 1, 'online', 85, NOW(), 1);

-- Insert sample water reading
INSERT INTO water_readings (meter_id, flow_rate, volume, reading_time, firebase_synced) VALUES
('MTR-2026-001', 12.5, 350.2, NOW(), 1);

-- Insert sample alert thresholds
INSERT INTO alert_thresholds (firebase_uid, property_id, threshold_type, threshold_value, notification_methods, firebase_synced) VALUES
('test_user_123', 1, 'daily_limit', 1000, '["push", "email"]', 1),
('test_user_123', 1, 'leak_duration', 2, '["push", "sms"]', 1),
('test_user_123', 1, 'flow_rate', 20, '["push", "email"]', 1);

-- Insert sample alert
INSERT INTO alerts (firebase_uid, property_id, alert_type, severity, title, message, firebase_synced) VALUES
('test_user_123', 1, 'system', 'info', 'System Ready', 'Smart Water Guardian system is operational', 1);

-- Insert sample message
INSERT INTO messages (firebase_uid, from_uid, from_name, from_email, subject, message, firebase_synced) VALUES
('consumer_uid_456', 'test_user_123', 'Admin', 'admin@smartwater.com', 'Welcome to Smart Water Guardian', 'Welcome! Start monitoring your water usage today.', 1);

-- Insert sample email notification
INSERT INTO email_notifications (firebase_uid, email, subject, message, type, status, sent_at) VALUES
('consumer_uid_456', 'consumer@test.com', 'Account Approved', 'Your account has been approved!', 'account_approved', 'sent', NOW());

-- Insert sample PDF report
INSERT INTO pdf_reports (firebase_uid, month, year, file_path, file_size) VALUES
('test_user_123', '2026-01', 2026, '/reports/Water_Report_2026-01-15.pdf', 102400);

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

-- Additional indexes for better query performance
CREATE INDEX idx_water_readings_meter_time ON water_readings(meter_id, reading_time DESC);
CREATE INDEX idx_alerts_user_read ON alerts(firebase_uid, is_read);
CREATE INDEX idx_daily_usage_summary_meter_date ON daily_usage_summary(meter_id, date DESC);
CREATE INDEX idx_audit_logs_user_time ON audit_logs(firebase_uid, created_at DESC);
CREATE INDEX idx_billing_user_month ON billing(firebase_uid, billing_month DESC);
CREATE INDEX idx_messages_user_read ON messages(firebase_uid, is_read);
CREATE INDEX idx_sync_log_status ON sync_log(sync_status, created_at);
CREATE INDEX idx_email_notifications_status ON email_notifications(status, created_at);
CREATE INDEX idx_pdf_reports_user_month ON pdf_reports(firebase_uid, month, year);

-- =============================================
-- TRIGGERS
-- =============================================

-- Drop existing triggers
DROP TRIGGER IF EXISTS update_device_communication;
DROP TRIGGER IF EXISTS update_daily_usage_summary;

-- Trigger to update device last_communication on new reading
DELIMITER //
CREATE TRIGGER update_device_communication
AFTER INSERT ON water_readings
FOR EACH ROW
BEGIN
    UPDATE devices 
    SET last_communication = NEW.reading_time,
        status = 'online',
        firebase_synced = 0
    WHERE meter_id = NEW.meter_id;
END//
DELIMITER ;

-- Trigger to update daily usage summary
DELIMITER //
CREATE TRIGGER update_daily_usage_summary
AFTER INSERT ON water_readings
FOR EACH ROW
BEGIN
    INSERT INTO daily_usage_summary (meter_id, date, total_volume, avg_flow_rate, peak_flow_rate, reading_count, firebase_synced)
    VALUES (
        NEW.meter_id,
        DATE(NEW.reading_time),
        NEW.volume,
        NEW.flow_rate,
        NEW.flow_rate,
        1,
        0
    )
    ON DUPLICATE KEY UPDATE
        total_volume = total_volume + NEW.volume,
        avg_flow_rate = (avg_flow_rate * reading_count + NEW.flow_rate) / (reading_count + 1),
        peak_flow_rate = GREATEST(peak_flow_rate, NEW.flow_rate),
        reading_count = reading_count + 1,
        firebase_synced = 0,
        updated_at = CURRENT_TIMESTAMP;
END//
DELIMITER ;

-- =============================================
-- STORED PROCEDURES
-- =============================================

DROP PROCEDURE IF EXISTS GetUserFullData;
DROP PROCEDURE IF EXISTS GetMonthlyBilling;
DROP PROCEDURE IF EXISTS GetDashboardStats;
DROP PROCEDURE IF EXISTS GetPendingApprovals;
DROP PROCEDURE IF EXISTS ApproveUser;
DROP PROCEDURE IF EXISTS RejectUser;

-- Get user with all related data
DELIMITER //
CREATE PROCEDURE GetUserFullData(IN p_firebase_uid VARCHAR(255))
BEGIN
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM properties WHERE firebase_uid = p_firebase_uid) as property_count,
        (SELECT COUNT(*) FROM devices d JOIN properties p ON d.property_id = p.id WHERE p.firebase_uid = p_firebase_uid) as device_count,
        (SELECT COUNT(*) FROM alerts WHERE firebase_uid = p_firebase_uid AND is_read = 0) as unread_alerts,
        (SELECT COUNT(*) FROM messages WHERE firebase_uid = p_firebase_uid AND is_read = 0) as unread_messages,
        (SELECT SUM(total_amount) FROM billing WHERE firebase_uid = p_firebase_uid AND is_paid = 0) as outstanding_bills
    FROM users u
    WHERE u.firebase_uid = p_firebase_uid;
END//
DELIMITER ;

-- Get monthly billing summary
DELIMITER //
CREATE PROCEDURE GetMonthlyBilling(IN p_firebase_uid VARCHAR(255), IN p_month DATE)
BEGIN
    SELECT 
        p.property_name,
        p.meter_id,
        SUM(wr.volume) as total_volume,
        COUNT(wr.id) as reading_count
    FROM users u
    JOIN properties p ON u.firebase_uid = p.firebase_uid
    JOIN water_readings wr ON p.meter_id = wr.meter_id
    WHERE u.firebase_uid = p_firebase_uid
        AND YEAR(wr.reading_time) = YEAR(p_month)
        AND MONTH(wr.reading_time) = MONTH(p_month)
    GROUP BY p.id;
END//
DELIMITER ;

-- Get dashboard stats
DELIMITER //
CREATE PROCEDURE GetDashboardStats(IN p_firebase_uid VARCHAR(255))
BEGIN
    DECLARE total_users INT;
    DECLARE total_properties INT;
    DECLARE total_devices INT;
    DECLARE online_devices INT;
    DECLARE unread_alerts INT;
    DECLARE total_volume_today DECIMAL(10,2);
    
    SELECT COUNT(*) INTO total_users FROM users;
    SELECT COUNT(*) INTO total_properties FROM properties WHERE firebase_uid = p_firebase_uid;
    SELECT COUNT(*) INTO total_devices FROM devices d JOIN properties p ON d.property_id = p.id WHERE p.firebase_uid = p_firebase_uid;
    SELECT COUNT(*) INTO online_devices FROM devices d JOIN properties p ON d.property_id = p.id WHERE p.firebase_uid = p_firebase_uid AND d.status = 'online';
    SELECT COUNT(*) INTO unread_alerts FROM alerts WHERE firebase_uid = p_firebase_uid AND is_read = 0;
    SELECT IFNULL(SUM(volume), 0) INTO total_volume_today FROM water_readings wr 
        JOIN properties p ON wr.meter_id = p.meter_id 
        WHERE p.firebase_uid = p_firebase_uid AND DATE(wr.reading_time) = CURDATE();
    
    SELECT 
        total_users,
        total_properties,
        total_devices,
        online_devices,
        unread_alerts,
        total_volume_today;
END//
DELIMITER ;

-- Get pending approvals
DELIMITER //
CREATE PROCEDURE GetPendingApprovals()
BEGIN
    SELECT 
        id, firebase_uid, email, first_name, last_name, phone, address, role, created_at
    FROM users 
    WHERE is_approved = 0 AND is_active = 1
    ORDER BY created_at DESC;
END//
DELIMITER ;

-- Approve user
DELIMITER //
CREATE PROCEDURE ApproveUser(IN p_firebase_uid VARCHAR(255), IN p_approved_by VARCHAR(255))
BEGIN
    UPDATE users 
    SET is_approved = 1, approval_date = NOW(), approved_by = p_approved_by
    WHERE firebase_uid = p_firebase_uid AND is_active = 1;
END//
DELIMITER ;

-- Reject user
DELIMITER //
CREATE PROCEDURE RejectUser(IN p_firebase_uid VARCHAR(255))
BEGIN
    UPDATE users SET is_active = 0, is_approved = 0 WHERE firebase_uid = p_firebase_uid;
END//
DELIMITER ;

-- =============================================
-- VIEWS
-- =============================================

-- Drop existing views
DROP VIEW IF EXISTS v_user_dashboard_summary;
DROP VIEW IF EXISTS v_admin_dashboard_stats;
DROP VIEW IF EXISTS v_billing_summary;
DROP VIEW IF EXISTS v_pending_approvals;

-- View for user dashboard summary
CREATE VIEW v_user_dashboard_summary AS
SELECT 
    u.firebase_uid,
    u.first_name,
    u.last_name,
    p.id as property_id,
    p.property_name,
    p.meter_id,
    d.status as device_status,
    d.battery_level,
    d.last_communication,
    wr.flow_rate as current_flow,
    wr.volume as current_volume,
    wr.reading_time as last_reading_time,
    (SELECT COUNT(*) FROM alerts a WHERE a.firebase_uid = u.firebase_uid AND a.is_read = 0) as unread_alerts,
    (SELECT COUNT(*) FROM messages m WHERE m.firebase_uid = u.firebase_uid AND m.is_read = 0) as unread_messages
FROM users u
LEFT JOIN properties p ON u.firebase_uid = p.firebase_uid
LEFT JOIN devices d ON p.meter_id = d.meter_id
LEFT JOIN water_readings wr ON p.meter_id = wr.meter_id
WHERE wr.id IN (
    SELECT MAX(id) FROM water_readings GROUP BY meter_id
)
OR wr.id IS NULL;

-- View for admin dashboard stats
CREATE VIEW v_admin_dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'consumer') as total_consumers,
    (SELECT COUNT(*) FROM users WHERE role = 'system_admin') as total_admins,
    (SELECT COUNT(*) FROM properties) as total_properties,
    (SELECT COUNT(*) FROM devices) as total_devices,
    (SELECT COUNT(*) FROM devices WHERE status = 'online') as online_devices,
    (SELECT COUNT(*) FROM alerts WHERE is_read = 0) as unread_alerts,
    (SELECT COUNT(*) FROM water_readings WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as readings_24h,
    (SELECT IFNULL(SUM(volume), 0) FROM water_readings WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as volume_24h,
    (SELECT IFNULL(SUM(total_amount), 0) FROM billing WHERE is_paid = 0) as outstanding_bills,
    (SELECT IFNULL(SUM(total_amount), 0) FROM billing WHERE is_paid = 1 AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as revenue_30d;

-- View for billing summary
CREATE VIEW v_billing_summary AS
SELECT 
    firebase_uid,
    COUNT(*) as total_bills,
    SUM(CASE WHEN is_paid = 0 THEN total_amount ELSE 0 END) as outstanding,
    SUM(CASE WHEN is_paid = 1 THEN total_amount ELSE 0 END) as paid,
    SUM(total_amount) as total
FROM billing
GROUP BY firebase_uid;

-- View for pending approvals
CREATE VIEW v_pending_approvals AS
SELECT 
    id, firebase_uid, email, first_name, last_name, phone, address, role, created_at
FROM users 
WHERE is_approved = 0 AND is_active = 1
ORDER BY created_at DESC;

-- =============================================
-- FUNCTIONS
-- =============================================

-- Function to check if user is synced
DELIMITER //
CREATE FUNCTION IsUserSynced(p_firebase_uid VARCHAR(255))
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE synced BOOLEAN;
    SELECT firebase_synced INTO synced FROM users WHERE firebase_uid = p_firebase_uid;
    RETURN IFNULL(synced, FALSE);
END//
DELIMITER ;

-- Function to get total usage for a user
DELIMITER //
CREATE FUNCTION GetUserTotalUsage(p_firebase_uid VARCHAR(255), p_days INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE total DECIMAL(10,2);
    SELECT IFNULL(SUM(wr.volume), 0) INTO total
    FROM water_readings wr
    JOIN properties p ON wr.meter_id = p.meter_id
    WHERE p.firebase_uid = p_firebase_uid
        AND wr.reading_time >= DATE_SUB(NOW(), INTERVAL p_days DAY);
    RETURN total;
END//
DELIMITER ;

-- Function to check if user is approved
DELIMITER //
CREATE FUNCTION IsUserApproved(p_firebase_uid VARCHAR(255))
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE approved BOOLEAN;
    SELECT is_approved INTO approved FROM users WHERE firebase_uid = p_firebase_uid AND is_active = 1;
    RETURN IFNULL(approved, FALSE);
END//
DELIMITER ;

-- =============================================
-- COMMIT CHANGES
-- =============================================

COMMIT;