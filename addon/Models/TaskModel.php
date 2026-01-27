<?php

namespace Addon\Models;

use App\Core\Model;

class TaskModel extends Model
{
  protected ?string $connection = null; // Nama koneksi database (opsional)
  protected string $table = 'tasks';
  protected bool $timestamps = true;

  // Kolom timestamp (opsional untuk diubah)
  protected string $createdAtColumn = 'created_at';
  protected string $updatedAtColumn = 'updated_at';

  /**
   * Schema untuk 'php mazu migrate'
   * Tipe: id|string|int|bigint|text|datetime|date|boolean|json|decimal
   */
  protected array $schema = [
    'id'          => ['type' => 'int', 'primary' => true, 'auto_increment' => true],
    'title'       => ['type' => 'string', 'length' => 255, 'nullable' => false],
    'description' => ['type' => 'text', 'nullable' => true],
    'status'      => ['type' => 'string', 'length' => 20, 'default' => 'pending'],
    'user_id'     => ['type' => 'int', 'nullable' => true],
    'deleted_at'  => ['type' => 'timestamp', 'nullable' => true],
  ];

  protected array $seed = [
    ['title' => 'Fix bug in login flow', 'description' => 'Resolve issues with token validation on hard refresh.', 'status' => 'done', 'user_id' => 1],
    ['title' => 'Implement Task Seeder', 'description' => 'Add dummy data for testing pagination and layout.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Optimize SPA Navigation', 'description' => 'Refactor spa.js to support global interceptors.', 'status' => 'done', 'user_id' => 1],
    ['title' => 'Database Migration', 'description' => 'Update tasks table schema to support soft deletes.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'UI/UX Design Review', 'description' => 'Review sidebar navigation accessibility.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'API Documentation', 'description' => 'Document all available endpoints for the task module.', 'status' => 'done', 'user_id' => 1],
    ['title' => 'Security Audit', 'description' => 'Check for potential XSS in view rendering.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Performance Testing', 'description' => 'Benchmark application response time under heavy load.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Refactor AuthMiddleware', 'description' => 'Use dependency injection for container access.', 'status' => 'done', 'user_id' => 1],
    ['title' => 'Setup Redis Cache', 'description' => 'Configure redis for faster session management.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Email Notification System', 'description' => 'Send alerts when tasks are nearing deadline.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Backup Database', 'description' => 'Schedule daily automated backups to cloud storage.', 'status' => 'done', 'user_id' => 1],
    ['title' => 'Client Meeting', 'description' => 'Discuss project milestones and feature requests.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Update Dependencies', 'description' => 'Upgrade composer packages to the latest versions.', 'status' => 'done', 'user_id' => 1],
    ['title' => 'Code Refactoring', 'description' => 'Improve code readability in TaskController.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Frontend Unit Tests', 'description' => 'Add tests for SPA routing and state management.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Dark Mode Support', 'description' => 'Implement theme switching in the main layout.', 'status' => 'done', 'user_id' => 1],
    ['title' => 'Multilingual Support', 'description' => 'Add translation files for Indonesian and English.', 'status' => 'pending', 'user_id' => 1],
    ['title' => 'Log Analysis', 'description' => 'Monitor error logs for recurring issues.', 'status' => 'done', 'user_id' => 1],
    ['title' => 'Final Deployment', 'description' => 'Prepare the production environment for launch.', 'status' => 'pending', 'user_id' => 1],
  ];

  public function all(): array
  {
    $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table} WHERE deleted_at IS NULL");
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public function get(int $limit = 10, int $offset = 0, string $search = '', string $sortBy = 'created_at', string $sortOrder = 'DESC'): array
  {
    $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
    $params = [];

    if (!empty($search)) {
      $sql .= " AND (title LIKE :search1 OR description LIKE :search2)";
      $params['search1'] = "%{$search}%";
      $params['search2'] = "%{$search}%";
    }

    $allowedSorts = ['id', 'title', 'status', 'created_at'];
    if (!in_array($sortBy, $allowedSorts)) {
      $sortBy = 'created_at';
    }
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    $sql .= " ORDER BY {$sortBy} {$sortOrder}";

    $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $this->getDb()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function count(string $search = ''): int
  {
    $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE deleted_at IS NULL";
    $params = [];

    if (!empty($search)) {
      $sql .= " AND (title LIKE :search1 OR description LIKE :search2)";
      $params['search1'] = "%{$search}%";
      $params['search2'] = "%{$search}%";
    }

    $stmt = $this->getDb()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
  }

  public function find(string|int $id): ?array
  {
    $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  public function create(array $data): bool
  {
    if (empty($data)) {
      return false;
    }

    if ($this->usesTimestamps()) {
      $now = date('Y-m-d H:i:s');
      $data[$this->getCreatedAtColumn()] = $now;
      $data[$this->getUpdatedAtColumn()] = $now;
    }

    $columns = array_keys($data);
    $placeholders = array_map(fn($col) => ':' . $col, $columns);

    $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

    return $this->getDb()->query($sql, $data);
  }

  public function updateById(string|int $id, array $data): bool
  {
    if (empty($data)) {
      return false;
    }

    if ($this->usesTimestamps()) {
      $data[$this->getUpdatedAtColumn()] = date('Y-m-d H:i:s');
    }

    $setParts = [];
    foreach ($data as $column => $value) {
      $setParts[] = "{$column} = :{$column}";
    }

    $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = :id";
    $data['id'] = $id;

    return $this->getDb()->query($sql, $data);
  }

  public function deleteById(string|int $id): bool
  {
    $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
    return $this->getDb()->query($sql, ['id' => $id]);
  }
}
