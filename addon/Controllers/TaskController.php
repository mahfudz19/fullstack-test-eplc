<?php

namespace Addon\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use Addon\Models\TaskModel;

class TaskController
{
  private TaskModel $model;

  public function __construct(TaskModel $model)
  {
    $this->model = $model;
  }

  public function index(Request $request, Response $response): View
  {
    $limit = 5;
    $items = $this->model->get($limit, 0);
    $total = $this->model->count();

    return $response->renderPage(
      [
        'items' => $items,
        'total' => $total,
        'initialLimit' => $limit
      ],
      ['meta' => ['title' => 'My Tasks']]
    );
  }

  public function apiIndex(Request $request, Response $response)
  {
    $limit = (int) ($request->get('limit') ?? 5);
    $page = (int) ($request->get('page') ?? 1);
    $offset = ($page - 1) * $limit;
    $search = $request->get('search') ?? '';
    $sortBy = $request->get('sort') ?? 'created_at';
    $sortOrder = $request->get('order') ?? 'DESC';

    $items = $this->model->get($limit, $offset, $search, $sortBy, $sortOrder);
    $total = $this->model->count($search);

    return $response->json([
      'items' => $items,
      'total' => $total,
      'page' => $page,
      'limit' => $limit
    ]);
  }

  public function create(Request $request, Response $response): View
  {
    return $response->renderPage([], ['meta' => ['title' => 'Create Task']]);
  }

  public function store(Request $request, Response $response)
  {
    $data = $request->post();

    // Simple validation
    if (empty($data['title'])) {
      if ($request->isSpaRequest() || $request->wantsJson()) {
        return $response->json(['message' => 'Title is required'], 400);
      }
      // In a real app, pass errors back to view
      return $response->redirect('/tasks/create');
    }

    $this->model->create($data);

    if ($request->isSpaRequest()) {
      return $response->json([
        'redirect' => '/tasks',
        'message' => 'Task created successfully'
      ]);
    }

    return $response->redirect('/tasks');
  }

  public function edit(Request $request, Response $response): View
  {
    $id = $request->param('id');
    $item = $this->model->find($id);

    return $response->renderPage(['item' => $item], ['meta' => ['title' => 'Edit Task']]);
  }

  public function update(Request $request, Response $response)
  {
    $id = $request->param('id');
    $data = $request->getBody();

    if (empty($data)) {
      $data = $request->post();
    }

    if (empty($data['title'])) {
      if ($request->isSpaRequest() || $request->wantsJson()) {
        return $response->json(['message' => 'Title is required'], 400);
      }
      return $response->redirect('/tasks');
    }

    $this->model->updateById($id, $data);

    if ($request->isSpaRequest()) {
      return $response->json([
        'redirect' => '/tasks',
        'message' => 'Task updated successfully'
      ]);
    }

    return $response->redirect('/tasks');
  }

  public function destroy(Request $request, Response $response)
  {
    $id = $request->param('id');
    $this->model->deleteById($id);

    if ($request->isSpaRequest()) {
      return $response->json([
        'redirect' => '/tasks',
        'message' => 'Task deleted successfully'
      ]);
    }

    return $response->redirect('/tasks');
  }

  // --- Strict API Implementation ---

  public function apiShow(Request $request, Response $response)
  {
    $id = $request->param('id');
    $item = $this->model->find($id);

    if (!$item) {
      return $response->json(['message' => 'Task not found'], 404);
    }

    return $response->json($item);
  }

  public function apiStore(Request $request, Response $response)
  {
    $data = $request->post();

    // Validation
    if (empty($data['title'])) {
      return $response->json(['message' => 'Title is required'], 400);
    }

    if (isset($data['status']) && !in_array($data['status'], ['pending', 'done'])) {
      return $response->json(['message' => 'Status must be pending or done'], 400);
    }

    // Default status
    if (!isset($data['status'])) {
      $data['status'] = 'pending';
    }

    if (!$this->model->create($data)) {
      return $response->json(['message' => 'Failed to create task'], 500);
    }

    return $response->json(['message' => 'Task created successfully'], 201);
  }

  public function apiUpdate(Request $request, Response $response)
  {
    $id = $request->param('id');
    $existing = $this->model->find($id);

    if (!$existing) {
      return $response->json(['message' => 'Task not found'], 404);
    }

    $data = $request->post(); // Assuming JSON body is parsed into post() or use getBody()

    // Partial validation if fields exist
    if (isset($data['title']) && empty($data['title'])) {
      return $response->json(['message' => 'Title cannot be empty'], 400);
    }

    if (isset($data['status']) && !in_array($data['status'], ['pending', 'done'])) {
      return $response->json(['message' => 'Status must be pending or done'], 400);
    }

    $this->model->updateById($id, $data);

    return $response->json(['message' => 'Task updated successfully']);
  }

  public function apiDestroy(Request $request, Response $response)
  {
    $id = $request->param('id');
    $existing = $this->model->find($id);

    if (!$existing) {
      return $response->json(['message' => 'Task not found'], 404);
    }

    $this->model->deleteById($id);

    return $response->json(['message' => 'Task deleted successfully']);
  }
}
