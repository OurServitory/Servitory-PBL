BEGIN;

ALTER TABLE reports
    ADD COLUMN IF NOT EXISTS completion_evidence_data BYTEA,
    ADD COLUMN IF NOT EXISTS completion_evidence_mime VARCHAR(32);

ALTER TABLE reports
    DROP CONSTRAINT IF EXISTS reports_completion_evidence_check;

ALTER TABLE reports
    ADD CONSTRAINT reports_completion_evidence_check
    CHECK (
        (completion_evidence_data IS NULL AND completion_evidence_mime IS NULL)
        OR (
            completion_evidence_data IS NOT NULL
            AND completion_evidence_mime IS NOT NULL
            AND completion_evidence_mime IN ('image/jpeg', 'image/png', 'image/webp')
        )
    );

COMMIT;
