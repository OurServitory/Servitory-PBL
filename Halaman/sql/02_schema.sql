CREATE TABLE users (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    profile_photo_data BYTEA,
    profile_photo_mime VARCHAR(32),
    role VARCHAR(20) NOT NULL CHECK (role IN ('pelapor', 'teknisi', 'koordinator')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_profile_photo_check
        CHECK (
            (profile_photo_data IS NULL AND profile_photo_mime IS NULL)
            OR (
                profile_photo_data IS NOT NULL
                AND profile_photo_mime IS NOT NULL
                AND profile_photo_mime IN ('image/jpeg', 'image/png', 'image/webp')
            )
        )
);

CREATE TABLE reports (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ticket VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    location VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    evidence_data BYTEA,
    evidence_mime VARCHAR(32),
    completion_evidence_data BYTEA,
    completion_evidence_mime VARCHAR(32),
    status VARCHAR(30) NOT NULL DEFAULT 'Menunggu Konfirmasi'
        CHECK (status IN ('Menunggu Konfirmasi', 'Diterima', 'Dijadwalkan', 'Selesai', 'Ditolak')),
    urgency VARCHAR(10) CHECK (urgency IN ('Rendah', 'Menengah', 'Tinggi')),
    confirmed_at TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    reporter_id BIGINT NOT NULL REFERENCES users (id),
    technician_id BIGINT REFERENCES users (id),
    reported_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT reports_urgency_confirmation_check
        CHECK ((urgency IS NULL) = (confirmed_at IS NULL)),
    CONSTRAINT reports_evidence_check
        CHECK (
            (evidence_data IS NULL AND evidence_mime IS NULL)
            OR (
                evidence_data IS NOT NULL
                AND evidence_mime IS NOT NULL
                AND evidence_mime IN ('image/jpeg', 'image/png', 'image/webp')
            )
        ),
    CONSTRAINT reports_completion_evidence_check
        CHECK (
            (completion_evidence_data IS NULL AND completion_evidence_mime IS NULL)
            OR (
                completion_evidence_data IS NOT NULL
                AND completion_evidence_mime IS NOT NULL
                AND completion_evidence_mime IN ('image/jpeg', 'image/png', 'image/webp')
            )
        )
);

CREATE INDEX reports_reporter_id_idx ON reports (reporter_id);
CREATE INDEX reports_status_idx ON reports (status);
