INSERT INTO users (full_name, phone, pin_hash, global_role)
VALUES
    ('Douglas (Owner)', '71997087082', '$2y$12$.Aq40Xz3ehXuLGYcVqUrJelyyXTpQJq10cp.w3F6tXj2GX4kf5bFS', 'owner_saas'),
    ('Membro Exemplo', '71911112222', '$2y$12$b7fut.fsg6i3D8oa74n0KeIgm5dIi.e5fwIfcK2Tq324rXPqzSOcm', 'user');

INSERT INTO babas (owner_user_id, name, code, welcome_message)
VALUES (
    1,
    'Baba de Quinta',
    'BABA10',
    'Fala, time! O Baba de Quinta esta no ar. Bora aquecer, confirmar presenca e fazer acontecer mais um jogo epico. Boas vendas na quadra!'
);

INSERT INTO baba_members (baba_id, user_id, role)
VALUES
    (1, 1, 'baba_admin'),
    (1, 2, 'member');

INSERT INTO players (baba_id, user_id, nickname, preferred_position, overall)
VALUES
    (1, 1, 'Douglas', 'meia', 78),
    (1, 2, 'Ney', 'atacante', 82);

INSERT INTO matches (baba_id, title, starts_at, location, status, created_by)
VALUES
    (1, 'Baba de Quinta - Noite', DATE_ADD(NOW(), INTERVAL 2 DAY), 'Arena Linha Verde', 'scheduled', 1);
