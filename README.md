# Tes Online Fullstack Developer 2026

Repository ini berisi penyelesaian tugas Fullstack Developer Test yang terdiri dari dua bagian: **Logic Test** dan **Simple Task Management API**.

---

## 🧩 Bagian 1: Logic Test

Implementasi logika untuk penggabungan array, pengurutan, dan pencarian data yang hilang sesuai spesifikasi soal.

- **Lokasi File**: [`LogicTest.php`](LogicTest.php)
- **Cara Menjalankan**:
  Buka terminal di direktori project, lalu jalankan:
  ```bash
  php LogicTest.php
  ```
- **Deskripsi**: Script ini akan menampilkan output langkah demi langkah dari proses merge sort, pencarian missing integer, hingga insertion kembali ke array utama.

---

## 🚀 Bagian 2: Simple Task Management API

Studi kasus pembuatan REST API untuk manajemen tugas. Project ini dibangun menggunakan **Mazu Framework**, sebuah lightweight engine buatan sendiri yang dirancang khusus untuk performa tinggi dan kemudahan pengembangan aplikasi modern berbasis SPA (Single Page Application).

### 🛠️ Tech Stack & Engine
- **Engine**: [Mazu Framework](https://github.com/your-username/mazu-framework) (Custom Built)
- **Backend**: PHP 8.1+ (Native-based architecture)
- **Frontend**: Native CSS & JavaScript (SPA logic)
- **Database**: MySQL / SQLite
- **Dependency Manager**: Composer

### Requirements
- PHP 8.1+
- MySQL
- Composer

### Cara Install

1.  **Clone Repository**
    ```bash
    git clone <repository-url>
    cd fullstack-test-eplc
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Konfigurasi Database**
    - Copy `.env.example` ke `.env` (jika ada) atau buat file `.env` baru.
    - Atur koneksi database di `app/Services/ConfigService.php` atau `.env`.
    
    *Catatan: Project ini menggunakan konfigurasi hardcoded di `app/Services/ConfigService.php` jika .env belum disetup.*

4.  **Migrasi Database**
    Jalankan perintah migrasi untuk membuat tabel:
    ```bash
    php mazu migrate
    ```
    *(Pastikan database sudah dibuat di MySQL sebelum menjalankan command ini)*

## Cara Run

Jalankan built-in server Mazu:
```bash
php mazu serve
```
Server akan berjalan di `http://localhost:8000`.

## Dokumentasi API

### 1. Login
- **URL**: `POST /api/login`
- **Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password"
  }
  ```
- **Response**:
  ```json
  {
    "token": "secret-token-123",
    "user": { ... }
  }
  ```

### 2. Get List Task
- **URL**: `GET /api/tasks`
- **Header**: `Authorization: Bearer secret-token-123`
- **Params**: `page`, `limit`, `search`, `sort`, `order`
- **Response**: JSON List of tasks.

### 3. Get Detail Task
- **URL**: `GET /api/tasks/{id}`
- **Header**: `Authorization: Bearer secret-token-123`
- **Response**: Detail task object or 404.

### 4. Create Task
- **URL**: `POST /api/tasks`
- **Header**: `Authorization: Bearer secret-token-123`
- **Body**:
  ```json
  {
    "title": "Judul Task",
    "description": "Deskripsi...",
    "status": "pending" 
  }
  ```
- **Response**: 201 Created.

### 5. Update Task
- **URL**: `PUT /api/tasks/{id}`
- **Header**: `Authorization: Bearer secret-token-123`
- **Body**: (Partial Update)
  ```json
  {
    "status": "done"
  }
  ```
- **Response**: 200 OK.

### 6. Delete Task
- **URL**: `DELETE /api/tasks/{id}`
- **Header**: `Authorization: Bearer secret-token-123`
- **Response**: 200 OK.

---

## 🌟 Fitur Bonus (Tercapai)

- **Pagination**: Tersedia di endpoint `GET /api/tasks` menggunakan parameter `page` dan `limit`.
- **Soft Delete**: Data yang dihapus tidak hilang dari database, hanya ditandai dengan kolom `deleted_at`.
