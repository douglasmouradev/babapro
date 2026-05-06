-- Adiciona foto de perfil (cadastro / upload)
ALTER TABLE users
    ADD COLUMN photo_path VARCHAR(255) NULL AFTER pin_hash;
