<?php
// Stocke les sessions PHP dans la table "sessions" (nécessaire sur Vercel)
class DbSessionHandler implements SessionHandlerInterface
{
    private PDO $db;

    public function open(string $path, string $name): bool
    {
        $this->db = Database::getInstance();
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $st = $this->db->prepare('SELECT data FROM sessions WHERE id = ?');
        $st->execute([$id]);
        $donnees = $st->fetchColumn();
        return $donnees === false ? '' : (string) $donnees;
    }

    public function write(string $id, string $data): bool
    {
        $st = $this->db->prepare('REPLACE INTO sessions (id, data, last_activity) VALUES (?, ?, ?)');
        return $st->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        return $this->db->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $st = $this->db->prepare('DELETE FROM sessions WHERE last_activity < ?');
        $st->execute([time() - $max_lifetime]);
        return $st->rowCount();
    }
}
