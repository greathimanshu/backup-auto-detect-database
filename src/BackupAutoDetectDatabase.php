<?php

namespace GreatHimansh\BackupAutoDetectDatabase;

use Illuminate\Console\Command;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;

class BackupAutoDetectDatabase extends Command
{
    protected $signature = 'backup:auto-upload {--dry-run : Run without uploading to Google Drive}';
    protected $description = 'Automatically backup ALL MySQL or MongoDB databases and upload to Google Drive';

    protected $driveParentFolderId;
    protected $mysqlDumpPath;
    protected $mongoDumpPath;

    public function __construct()
    {
        parent::__construct();

        // Use config instead of env (best practice)
        $this->driveParentFolderId = config('backup-detect.drive_folder_id');
        $this->mysqlDumpPath = config('backup-detect.mysqldump_path', 'mysqldump');
        $this->mongoDumpPath = config('backup-detect.mongodump_path', 'mongodump');
        $this->backupReplace = config('backup-detect.backup_replace', true);
    }

    public function handle()
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.$connection");

        if (!$dbConfig) {
            $this->error("❌ Database configuration not found for: $connection");
            return 1;
        }

        if ($dbConfig['driver'] === 'mysql') {
            return $this->backupEachMySQLDatabase($dbConfig);
        } elseif ($dbConfig['driver'] === 'mongodb') {
            return $this->backupEachMongoDatabase($dbConfig);
        } else {
            $this->error("❌ Unsupported database driver: {$dbConfig['driver']}");
            return 1;
        }
    }

    protected function backupEachMySQLDatabase($db)
    {
        $this->info("🔍 Retrieving MySQL database list...");

        if (!is_executable($this->mysqlDumpPath) && strpos($this->mysqlDumpPath, 'mysqldump') !== false) {
            $this->warn("⚠️ mysqldump might not be found. Double-check path: {$this->mysqlDumpPath}");
        }

        $connection = mysqli_connect($db['host'], $db['username'], $db['password']);
        $result = mysqli_query($connection, 'SHOW DATABASES');

        while ($row = mysqli_fetch_assoc($result)) {
            $database = $row['Database'];
            if (in_array($database, ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
                continue;
            }

            $filename = "$database-backup.sql";
            $filepath = storage_path("app/{$filename}");

            $command = sprintf(
                '%s --user=%s --password=%s --host=%s %s > %s',
                escapeshellcmd($this->mysqlDumpPath),
                escapeshellarg($db['username']),
                escapeshellarg($db['password']),
                escapeshellarg($db['host']),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            $this->info("💾 Backing up MySQL database: $database");
            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                $this->uploadToDrive($filepath, $filename);
            } else {
                $this->error("❌ Failed to backup $database");
            }
        }

        mysqli_close($connection);
        return 0;
    }

    protected function backupEachMongoDatabase($db)
    {
        $this->info("🔍 Retrieving MongoDB database list...");

        if (!is_executable($this->mongoDumpPath)) {
            $this->warn("⚠️ mongodump might not be found. Double-check path: {$this->mongoDumpPath}");
        }

        $uri = "mongodb://{$db['username']}:{$db['password']}@{$db['host']}:{$db['port']}";
        $json = shell_exec("mongo --quiet --eval \"db.adminCommand('listDatabases')\" --username {$db['username']} --password {$db['password']} --host {$db['host']}");
        $databases = json_decode($json, true);

        foreach ($databases['databases'] as $database) {
            $dbName = $database['name'];
            if (in_array($dbName, ['admin', 'local', 'config'])) {
                continue;
            }

            $filename = "$dbName-backup.gz";
            $filepath = storage_path("app/{$filename}");

            $command = sprintf(
                '%s --uri=%s --db=%s --archive=%s --gzip',
                escapeshellcmd($this->mongoDumpPath),
                escapeshellarg($uri),
                escapeshellarg($dbName),
                escapeshellarg($filepath)
            );

            $this->info("💾 Backing up MongoDB database: $dbName");
            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                $this->uploadToDrive($filepath, $filename);
            } else {
                $this->error("❌ Failed to backup $dbName");
            }
        }

        return 0;
    }

    protected function uploadToDrive($filepath, $filename)
    {
        if (!file_exists($filepath)) {
            $this->error("❌ File not found: $filepath");
            return;
        }

        if ($this->option('dry-run')) {
            $this->info("🛑 [Dry Run] Would upload: $filename");
            return;
        }

        try {
            $this->info("☁️ Uploading $filename to Google Drive...");

            $client = new GoogleClient();
            $client->setAuthConfig(storage_path('app/google/service-account.json'));
            $client->addScope(GoogleDrive::DRIVE);

            $driveService = new GoogleDrive($client);

            if ($this->backupReplace) {
                $this->info("🔄 BACKUP_REPLACE is true. Checking for existing files...");

                $existingFiles = $driveService->files->listFiles([
                    'q' => sprintf("name='%s' and '%s' in parents and trashed=false", $filename, $this->driveParentFolderId),
                    'fields' => 'files(id, name)',
                ]);

                foreach ($existingFiles->getFiles() as $file) {
                    $this->info("🗑️ Deleting existing file: {$file->getName()}");
                    $driveService->files->delete($file->getId());
                }
            }
            $fileMetadata = new DriveFile([
                'name' => $filename,
                'parents' => [$this->driveParentFolderId],
            ]);

            $file = $driveService->files->create($fileMetadata, [
                'data' => file_get_contents($filepath),
                'mimeType' => 'application/octet-stream',
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]);

            $this->info("✅ Uploaded: $filename (ID: {$file->id})");

            unlink($filepath);
            $this->info("🗑️ Local backup file deleted.");
        } catch (\Exception $e) {
            $this->error("❌ Google Drive upload failed for $filename: " . $e->getMessage());
        }
    }
}
