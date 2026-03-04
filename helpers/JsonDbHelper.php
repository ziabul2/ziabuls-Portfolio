<?php
/**
 * JSON-based database helper with exclusive file locking.
 *
 * Wraps reads and writes to users.json ensuring:
 *  - Exclusive write locks via flock() to prevent race conditions
 *  - Atomic writes via temp file + rename
 *  - Consistent schema
 */
class JsonDbHelper
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;

        // Ensure the directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        // Seed the file with an empty array if it doesn't exist
        if (!file_exists($path)) {
            file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
            chmod($path, 0640);
        }
    }

    /**
     * Read all records from the JSON file.
     *
     * @return array<int, array>
     */
    public function readAll(): array
    {
        $fp = fopen($this->path, 'r');
        if (!$fp) {
            return [];
        }

        flock($fp, LOCK_SH);
        $contents = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $data = json_decode($contents, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Find a single record matching a key/value pair.
     *
     * @param string $field
     * @param mixed  $value
     * @return array|null
     */
    public function findBy(string $field, mixed $value): ?array
    {
        foreach ($this->readAll() as $record) {
            if (isset($record[$field]) && $record[$field] === $value) {
                return $record;
            }
        }
        return null;
    }

    /**
     * Write all records to the JSON file using an exclusive lock and atomic rename.
     *
     * @param array $records
     * @return bool
     */
    public function writeAll(array $records): bool
    {
        $tmp = $this->path . '.tmp.' . getmypid();
        $json = json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Write to temp file first
        $fp = fopen($tmp, 'w');
        if (!$fp) {
            return false;
        }

        flock($fp, LOCK_EX);
        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        // Atomically replace the original file
        return rename($tmp, $this->path);
    }

    /**
     * Update a single record identified by its 'id' field.
     * If no record with that id exists, the record is inserted.
     *
     * @param array $updated
     * @return bool
     */
    public function save(array $updated): bool
    {
        $records = $this->readAll();
        $found   = false;

        foreach ($records as $i => $record) {
            if (isset($record['id']) && $record['id'] === $updated['id']) {
                $records[$i] = $updated;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $records[] = $updated;
        }

        return $this->writeAll($records);
    }

    /**
     * Returns the next auto-increment id.
     */
    public function nextId(): int
    {
        $records = $this->readAll();
        if (empty($records)) {
            return 1;
        }
        $max = max(array_column($records, 'id') ?: [0]);
        return $max + 1;
    }
}
