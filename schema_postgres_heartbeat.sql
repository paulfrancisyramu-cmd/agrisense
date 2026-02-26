-- Add heartbeat tracking (run once in Render Postgres / TablePlus)

CREATE TABLE IF NOT EXISTS device_heartbeat (
    id INTEGER PRIMARY KEY,
    last_seen TIMESTAMP NOT NULL DEFAULT NOW()
);

INSERT INTO device_heartbeat (id, last_seen)
VALUES (1, NOW())
ON CONFLICT (id) DO NOTHING;

