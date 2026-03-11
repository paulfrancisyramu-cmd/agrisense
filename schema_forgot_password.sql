-- Forgot Password Schema - Your Current Setup
-- Run this SQL if any tables are missing from your existing setup

-- You already have these columns on users table:
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255);
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255);
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expire TIMESTAMP;

-- You already have this table:
-- CREATE TABLE IF NOT EXISTS password_reset_tokens (
--     id SERIAL PRIMARY KEY,
--     user_id INTEGER NOT NULL,
--     token VARCHAR(64) UNIQUE NOT NULL,
--     expires_at TIMESTAMP NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- Add missing indexes if not exists:
CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_token ON password_reset_tokens(token);
CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_user_id ON password_reset_tokens(user_id);

