<?php

use Addon\Controllers\AuthController;
use Addon\Controllers\TaskController;


// Root Route (Gateway)
$router->get('/', [AuthController::class, 'index']);

// 1. Login (Public)
$router->get('/login', [AuthController::class, 'page']);
$router->post('/api/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']); // Fix 404 Logout

// Protected Routes
$router->group(['middleware' => ['auth']], function ($router) {
  $router->get('/dashboard', [AuthController::class, 'dashboard']);

  // Task Management
  $router->get('/tasks', [TaskController::class, 'index']);
  $router->get('/tasks/create', [TaskController::class, 'create']);
  $router->get('/tasks/:id/edit', [TaskController::class, 'edit']);
  $router->post('/tasks', [TaskController::class, 'store']);
  $router->put('/tasks/:id', [TaskController::class, 'update']);
  $router->delete('/tasks/:id', [TaskController::class, 'destroy']);

  // Dedicated API Routes (Strict Requirement)
  $router->get('/api/tasks', [TaskController::class, 'apiIndex']);
  $router->get('/api/tasks/:id', [TaskController::class, 'apiShow']);
  $router->post('/api/tasks', [TaskController::class, 'apiStore']);
  $router->put('/api/tasks/:id', [TaskController::class, 'apiUpdate']);
  $router->delete('/api/tasks/:id', [TaskController::class, 'apiDestroy']);
});
