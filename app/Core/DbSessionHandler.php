<?php

declare(strict_types=1);

namespace App\Core;

final class DbSessionHandler implements \SessionHandlerInterface
{
    public function __construct(private \PDO $pdo, private string $table)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $stmt = $this->pdo->prepare("SELECT data FROM {$this->table} WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetchColumn();
        return $data === false ? '' : (string) $data;
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (id,data,last_activity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE data=VALUES(data),last_activity=VALUES(last_activity)");
        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE last_activity < ?");
        $stmt->execute([time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}
