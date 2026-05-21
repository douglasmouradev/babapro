-- Foto e mensagem de boas-vindas por grupo (baba)
ALTER TABLE babas
    ADD COLUMN photo_path VARCHAR(255) NULL AFTER code,
    ADD COLUMN welcome_message TEXT NULL AFTER photo_path;
