
-- Add password reset columns to users table
-- Run this SQL in your PostgreSQL database (TablePlus or psql)

-- Add reset_token column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255);

-- Add reset_token_expire column if it doesn't exist  
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expire TIMESTAMP;

-- Add email column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255);

-- Remove unique constraint on email if it exists (allows NULL values)
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_key;

