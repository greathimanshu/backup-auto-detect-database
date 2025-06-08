# 📦 BackupAutoDetectDatabase

Automatically backup **MySQL** or **MongoDB** databases and upload them to **Google Drive**, as a reusable Laravel package.

> 👑 Package by: [greatHimanshu](https://github.com/greatHimanshu)

---

## 🚀 Features

- 🔐 Secure upload to Google Drive using a **Service Account**
- 💾 Auto-detect MySQL or MongoDB and perform dump
- ☁️ Upload `.sql` or `.archive` file to Drive
- 🧹 Auto-delete local files after upload
- 🔄 Ready for Laravel scheduler integration
- 🧩 Easy to install and use

---

## 📥 Installation

### 1. Require the package via Composer

```bash
composer require greathimansh/backup-auto-detect-database
```

---

## ⚙️ Laravel Usage

### 1. Publish config (optional)

```bash
php artisan vendor:publish --tag=backup-auto-detect-config
```

This will publish a config file: `config/backup-auto-detect.php`

### 2. Add Service Account JSON file

Place your Google Drive service account key file here:

```
storage/app/google/service-account.json
```

---

## 🛠 Configuration (`.env`)

Add these entries:

```env
GOOGLE_DRIVE_FOLDER_ID=your_drive_folder_id_here        # ✅ Required — Google Drive folder ID
DB_BACKUP_TYPE=mysql                                     # ✅ Required — mysql or mongodb

MYSQLDUMP_PATH=your_mysql_dump_path_here                 # 🔁 Optional — full path if mysqldump is not in system PATH
MONGODUMP_PATH=your_mongodb_dump_path_here               # 🔁 Optional — full path if mongodump is not in system PATH

BACKUP_REPLACE=true                                      # 🔁 Optional — if true, will delete old backup before uploading new

```

---

## ▶️ Run the Command

Use the built-in Artisan command:

```bash
php artisan backup:auto-upload
```

Example output:

```
Starting database backup...
Detected: MySQL
Dumping all databases...
Uploading to Google Drive...
✅ Upload complete. Local file deleted.
```

---

## 🔁 Automate with Laravel Scheduler

In `app/Console/Kernel.php`:

```php
$schedule->command('backup:auto-upload')->dailyAt('02:00');
```

Run scheduler every minute via cron (Linux):

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, use Task Scheduler.

---

## 🧩 Supported Versions

- PHP 8.1+
- Laravel 9, 10, 11, 12
- MySQL via `mysqldump`
- MongoDB via `mongodump`

Ensure `mysqldump` or `mongodump` is available in system `PATH` or provide full path in the config.

---

## 📂 Where does it store backups?

- Files are uploaded to your **Google Drive folder**
- Filename format: `all-databases-backup.sql` or `.archive` (MongoDB)

---

## 🧑‍💻 Contributing

PRs are welcome. Make sure your code is clean, PSR-4 compliant, and properly documented.

---

## 👨‍🎓 Author

**greatHimanshu**  
🔗 [GitHub](https://github.com/greatHimanshu)

---

## 🛡 License

This package is licensed under the [MIT License](LICENSE).
