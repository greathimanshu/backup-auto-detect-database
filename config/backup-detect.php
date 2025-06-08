<?php

return [
    'drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),
    'mongodump_path' => env('MONGODUMP_PATH', 'mongodump'),
    'backup_replace' => env('BACKUP_REPLACE', true),
    
];
