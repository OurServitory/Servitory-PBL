INSERT INTO users (name, username, email, password_hash, role)
VALUES (
    'Koordinator Demo',
    'koordinator01',
    'koordinator01@servitory.local',
    '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.',
    'koordinator'
)
ON CONFLICT (username) DO NOTHING;
