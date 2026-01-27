<?php

namespace Addon\Controllers;

use Addon\Models\TaskModel;
use Addon\Models\UserModel;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class AuthController
{
  public function __construct(private UserModel $userModel,  private TaskModel $taskModel) {}

  public function logout(Request $request, Response $response): void
  {
    if ($request->isSpaRequest()) {
      $response->json(['redirect' => '/login', 'force_reload' => false]);
      return;
    }

    $html = <<<HTML
      <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Logging out...</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="flex items-center justify-center h-screen bg-gray-100">
            <div class="text-gray-500">
                <p>Logging out...</p>
            </div>
            <script>
                (function() {
                    // Hapus semua data sesi
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                    
                    // Redirect ke login
                    window.location.replace('/login');
                })();
            </script>
        </body>
      </html>
    HTML;

    $response->setContent($html)->send();
  }

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
