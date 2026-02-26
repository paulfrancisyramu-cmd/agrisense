-- Run this in your Render Postgres (agrisensedb) to create required tables.
-- In Render: open your PostgreSQL service → Connect → Shell (or use psql/TablePlus with Internal URL).

-- 1. Users (for login)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- 2. Default admin (password: admin — change after first login)
INSERT INTO users (username, password) VALUES ('admin', 'admin')
ON CONFLICT (username) DO NOTHING;

-- 3. System settings (DSS thresholds)
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    heat_threshold DOUBLE PRECISION NOT NULL DEFAULT 35.0,
    hum_threshold DOUBLE PRECISION NOT NULL DEFAULT 40.0,
    rain_threshold DOUBLE PRECISION NOT NULL DEFAULT 50.0,
    heartbeat_timeout INTEGER NOT NULL DEFAULT 60
);

-- Ensure one row exists
INSERT INTO system_settings (id, heat_threshold, hum_threshold, rain_threshold, heartbeat_timeout)
VALUES (1, 35.0, 40.0, 50.0, 60)
ON CONFLICT (id) DO NOTHING;

-- 4. Sensor data (from ESP32 / save_data.php)
CREATE TABLE IF NOT EXISTS sensor_data (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    temp DOUBLE PRECISION NOT NULL,
    hum DOUBLE PRECISION NOT NULL,
    rain_forecast DOUBLE PRECISION NOT NULL,
    recommendation TEXT NOT NULL
);
