CREATE TABLE users (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('pelapor', 'teknisi', 'koordinator')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reports (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ticket VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    location VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Menunggu Konfirmasi'
        CHECK (status IN ('Menunggu Konfirmasi', 'Diterima', 'Dijadwalkan', 'Selesai', 'Ditolak')),
    urgency VARCHAR(10) CHECK (urgency IN ('Rendah', 'Menengah', 'Tinggi')),
    confirmed_at TIMESTAMPTZ,
    reporter_id BIGINT NOT NULL REFERENCES users (id),
    technician_id BIGINT REFERENCES users (id),
    reported_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT reports_urgency_confirmation_check
        CHECK ((urgency IS NULL) = (confirmed_at IS NULL))
);

CREATE INDEX reports_reporter_id_idx ON reports (reporter_id);
CREATE INDEX reports_status_idx ON reports (status);
