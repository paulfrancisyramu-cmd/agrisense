-- Remove email and verification-related columns from users table since they are no longer needed
ALTER TABLE users DROP COLUMN IF EXISTS email;
ALTER TABLE users DROP COLUMN IF EXISTS is_verified;
ALTER TABLE users DROP COLUMN IF EXISTS verification_token;

-- Note: reset_token and reset_token_expire are kept for password resets
