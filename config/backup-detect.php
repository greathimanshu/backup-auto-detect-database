<?php

return [
    'drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),
    'filename' => env('DB_BACKUP_FILENAME', 'all-databases-backup.sql'),
];
