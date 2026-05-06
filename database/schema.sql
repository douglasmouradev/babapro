CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,
    photo_path VARCHAR(255) NULL,
    global_role ENUM('owner_saas','user') NOT NULL DEFAULT 'user',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE babas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(20) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_babas_code (code),
    KEY idx_babas_owner (owner_user_id),
    CONSTRAINT fk_babas_owner_user FOREIGN KEY (owner_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE baba_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    baba_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('baba_admin','member') NOT NULL DEFAULT 'member',
    status ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
    payment_status ENUM('adimplente','inadimplente') NOT NULL DEFAULT 'adimplente',
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_baba_membership (baba_id, user_id),
    KEY idx_baba_members_user (user_id),
    CONSTRAINT fk_baba_members_baba FOREIGN KEY (baba_id) REFERENCES babas(id),
    CONSTRAINT fk_baba_members_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE players (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    baba_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    nickname VARCHAR(80) NOT NULL,
    preferred_position ENUM('goleiro','zagueiro','lateral','volante','meia','atacante') NOT NULL,
    overall TINYINT UNSIGNED NOT NULL DEFAULT 50,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_players_baba (baba_id),
    KEY idx_players_user (user_id),
    CONSTRAINT fk_players_baba FOREIGN KEY (baba_id) REFERENCES babas(id),
    CONSTRAINT fk_players_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    baba_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    starts_at DATETIME NOT NULL,
    location VARCHAR(180) NULL,
    status ENUM('scheduled','finished','cancelled') NOT NULL DEFAULT 'scheduled',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_matches_baba (baba_id),
    KEY idx_matches_start (starts_at),
    CONSTRAINT fk_matches_baba FOREIGN KEY (baba_id) REFERENCES babas(id),
    CONSTRAINT fk_matches_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE match_attendances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    player_id BIGINT UNSIGNED NOT NULL,
    status ENUM('confirmed','out','pending') NOT NULL DEFAULT 'pending',
    note VARCHAR(255) NULL,
    confirmed_at DATETIME NULL,
    UNIQUE KEY uq_attendance_match_player (match_id, player_id),
    KEY idx_attendance_player (player_id),
    CONSTRAINT fk_attendance_match FOREIGN KEY (match_id) REFERENCES matches(id),
    CONSTRAINT fk_attendance_player FOREIGN KEY (player_id) REFERENCES players(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_draws (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    baba_id BIGINT UNSIGNED NOT NULL,
    match_id BIGINT UNSIGNED NULL,
    generated_by BIGINT UNSIGNED NOT NULL,
    teams_count TINYINT UNSIGNED NOT NULL,
    algorithm_version VARCHAR(40) NOT NULL DEFAULT 'v1-balance-overall-position',
    payload_json JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_team_draws_baba (baba_id),
    KEY idx_team_draws_match (match_id),
    CONSTRAINT fk_team_draws_baba FOREIGN KEY (baba_id) REFERENCES babas(id),
    CONSTRAINT fk_team_draws_match FOREIGN KEY (match_id) REFERENCES matches(id),
    CONSTRAINT fk_team_draws_generated_by FOREIGN KEY (generated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
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

CREATE TABLE audit_logs (
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
