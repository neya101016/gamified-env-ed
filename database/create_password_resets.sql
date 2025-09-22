-- Add the password_resets table to the ecoedu database
USE ecoedu;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    secret_key VARCHAR(64) NOT NULL,
    otp_attempts INT DEFAULT 0,
    last_attempt TIMESTAMP NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);