<?php

namespace Addon\Controllers;

use Addon\Models\TaskModel;
use Addon\Models\UserModel;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;

class AuthController
{
  public function __construct(private UserModel $userModel,  private TaskModel $taskModel) {}

  public function index(Request $request, Response $response)
  {
    $limit = 5;
    $items = $this->taskModel->get($limit, 0);
    $total = $this->taskModel->count();

    return $response->renderPage(
      [
        'items' => $items,
        'total' => $total,
        'initialLimit' => $limit
      ],
      ['meta' => ['title' => 'My Tasks']]
    );
  }

  public function logout(Request $request, Response $response): void
  {
    // Best Practice: Logout harus selalu POST untuk mencegah CSRF & Prefetch logout tidak sengaja
    if ($request->getMethod() !== 'post') {
      $response->setStatusCode(405)->json(['message' => 'Method Not Allowed']);
      return;
    }

    // Server-side cleanup (jika menggunakan session/cookie di masa depan)
    // ...

    // Instruksikan Client (SPA) untuk menghapus token dan redirect
    // Kita kirim instruksi khusus yang bisa ditangkap oleh SPA handler
    $response->json([
      'action' => 'logout',
      'redirect' => getBaseUrl('/login'),
      'message' => 'Logged out successfully'
    ]);
  }

  public function page(Request $request, Response $response): View
  {
    return $response->renderPage([], ['meta' => ['title' => 'Login']]);
  }

  public function login(Request $request, Response $response)
  {
    $body = $request->post();

    $email = $body['email'] ?? '';
    $password = $body['password'] ?? '';

    if (empty($email) || empty($password)) {
      return $response->json(['message' => 'Email and password are required'], 400);
    }

    $user = $this->userModel->findByEmail($email);

    if (!$user || !password_verify($password, $user['password'])) {
      return $response->json(['message' => 'Invalid credentials'], 401);
    }

    $token = env('TOKEN', 'secret-token-123');

    return $response->json([
      'token' => $token,
      'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email']
      ]
    ]);
  }

  public function dashboard(Request $request, Response $response): View
  {
    return $response->renderPage([], ['meta' => ['title' => 'Dashboard']]);
  }
}
