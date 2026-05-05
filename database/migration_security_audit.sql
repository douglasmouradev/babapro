CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    baba_code VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    blocked_until DATETIME NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_phone_ip_time (phone, ip_address, attempted_at),
    KEY idx_login_attempts_blocked_until (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    baba_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    target_user_id BIGINT UNSIGNED NULL,
    details_json JSON NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_logs_baba (baba_id),
    KEY idx_audit_logs_actor (actor_user_id),
    KEY idx_audit_logs_action (action),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users(id),
    CONSTRAINT fk_audit_logs_target_user FOREIGN KEY (target_user_id) REFERENCES users(id),
    CONSTRAINT fk_audit_logs_baba FOREIGN KEY (baba_id) REFERENCES babas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
