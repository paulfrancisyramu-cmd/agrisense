-- Crops table for admin-created crops
CREATE TABLE IF NOT EXISTS crops (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image_url TEXT,
    ideal_temp_min FLOAT NOT NULL,
    ideal_temp_max FLOAT NOT NULL,
    ideal_hum_min FLOAT NOT NULL,
    ideal_hum_max FLOAT NOT NULL,
    seasons TEXT[] NOT NULL,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add sample admin-created crop (for testing)
INSERT INTO crops (name, image_url, ideal_temp_min, ideal_temp_max, ideal_hum_min, ideal_hum_max, seasons, created_by)
SELECT 'Rice', 'https://img.icons8.com/color/96/rice.png', 20, 30, 60, 80, ARRAY['Wet/Rainy', 'Cool Dry', 'Hot Dry'], id
FROM users WHERE username = 'admin'
ON CONFLICT DO NOTHING;

