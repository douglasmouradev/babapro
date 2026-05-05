ALTER TABLE baba_members
    MODIFY COLUMN status ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active';

ALTER TABLE baba_members
    ADD COLUMN payment_status ENUM('adimplente','inadimplente') NOT NULL DEFAULT 'adimplente' AFTER status;
