BEGIN;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_photo_data BYTEA,
    ADD COLUMN IF NOT EXISTS profile_photo_mime VARCHAR(32);

ALTER TABLE users
    DROP CONSTRAINT IF EXISTS users_profile_photo_check;

ALTER TABLE users
    ADD CONSTRAINT users_profile_photo_check
    CHECK (
        (profile_photo_data IS NULL AND profile_photo_mime IS NULL)
        OR (
            profile_photo_data IS NOT NULL
            AND profile_photo_mime IS NOT NULL
            AND profile_photo_mime IN ('image/jpeg', 'image/png', 'image/webp')
        )
    );

COMMIT;
