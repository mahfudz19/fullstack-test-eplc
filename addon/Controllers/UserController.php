<?php

namespace Addon\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\RedirectResponse;
use Addon\Models\UserModel;

class UserController
{
  private UserModel $model;

  public function __construct(UserModel $model)
  {
    $this->model = $model;
  }

  public function index(Request $request, Response $response): View
  {
    $items = $this->model->all();

    return $response->renderPage(['items' => $items]);
  }

  public function create(Request $request, Response $response): View
  {
    return $response->renderPage([]);
  }

  public function store(Request $request, Response $response): RedirectResponse
  {
    $data = $request->getBody();
    $this->model->create($data);

    return $response->redirect('/');
  }

  public function edit(Request $request, Response $response): View
  {
    $id = $request->param('id');
    $item = $this->model->find($id);

    return $response->renderPage(['item' => $item]);
  }

  public function update(Request $request, Response $response): RedirectResponse
  {
    $id = $request->param('id');
    $data = $request->getBody();
    $this->model->updateById($id, $data);

    return $response->redirect('/');
  }

  public function destroy(Request $request, Response $response): RedirectResponse
  {
    $id = $request->param('id');
    $this->model->deleteById($id);

    return $response->redirect('/');
  }
}
