-- Cria banco e usuario local (valores devem bater com o .env)
-- Uso: mysql -u root -p < scripts/setup_local_mysql.sql
-- Ajuste o nome do banco/senha abaixo se seu .env for diferente.

CREATE DATABASE IF NOT EXISTS babapro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'babapro'@'localhost' IDENTIFIED BY '1319112fa8';
CREATE USER IF NOT EXISTS 'babapro'@'127.0.0.1' IDENTIFIED BY '1319112fa8';

GRANT ALL PRIVILEGES ON babapro.* TO 'babapro'@'localhost';
GRANT ALL PRIVILEGES ON babapro.* TO 'babapro'@'127.0.0.1';

FLUSH PRIVILEGES;
