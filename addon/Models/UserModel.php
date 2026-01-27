<?php

namespace Addon\Models;

use App\Core\Model;

class UserModel extends Model
{
  protected ?string $connection = null; // Nama koneksi database (opsional)
  protected string $table = 'users';
  protected bool $timestamps = true;

  // Kolom timestamp (opsional untuk diubah)
  protected string $createdAtColumn = 'created_at';
  protected string $updatedAtColumn = 'updated_at';

  /**
   * Schema untuk 'php mazu migrate'
   * Tipe: id|string|int|bigint|text|datetime|date|boolean|json|decimal
   */
  protected array $schema = [
    'id'        => ['type' => 'int', 'primary' => true, 'auto_increment' => true],
    'name'      => ['type' => 'string', 'length' => 255, 'nullable' => false],
    'email'     => ['type' => 'string', 'length' => 255, 'nullable' => false, 'unique' => true],
    'password'  => ['type' => 'string', 'length' => 255, 'nullable' => false],
  ];

  protected array $seed = [];

  public function getSeed(): array
  {
    return [
      [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
      ],
      // Tambahkan user lain jika perlu
    ];
  }


  public function all(): array
  {
    $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table}");
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public function find(string|int $id): ?array
  {
    $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  public function findByEmail(string $email): ?array
  {
    $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  public function create(array $data): bool
  {
    if (empty($data)) {
      return false;
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
    $sql = "DELETE FROM {$this->table} WHERE id = :id";
    return $this->getDb()->query($sql, ['id' => $id]);
  }
}
