# AgriSense - Admin Crop Management Implementation

## Tasks Completed:
- [x] 1. Create SQL schema for crops table (schema_crops.sql)
- [x] 2. Create manage_crops.php for admin to add/edit/delete crops
- [x] 3. Modify includes/crops.php to fetch from database (get_all_crops function)
- [x] 4. Update sidebar.php to include crop management link for admin
- [x] 5. Update dashboard.php to use all_crops
- [x] 6. Update recommendations.php to use all_crops
- [x] 7. Update data_logs.php to use all_crops
- [x] 8. Update api/save_data.php to use all_crops

## Files Created/Modified:
- `schema_crops.sql` - Database schema for crops table
- `manage_crops.php` - Admin crop management page
- `includes/crops.php` - Added get_all_crops() function
- `includes/sidebar.php` - Added "Manage Crops" link for admin
- `dashboard.php` - Updated to use all_crops
- `recommendations.php` - Updated to use all_crops
- `data_logs.php` - Updated to use all_crops
- `api/save_data.php` - Updated to use all_crops

## Next Step - Run SQL Schema:
Execute the following SQL in your database to create the crops table:

```sql
-- Run schema_crops.sql in your PostgreSQL database
```

Or run this SQL directly:
```sql
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
```

