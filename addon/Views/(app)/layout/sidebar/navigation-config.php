<?php

return [
  [
    'section' => 'Main Menu',
    'roles' => ['guest', 'user', 'admin'], // Izinkan semua role yang relevan
    'menus' => [
      [
        'title' => 'Dashboard',
        'icon' => 'bi-grid-1x2-fill', // Pastikan icon set Bootstrap Icons dimuat
        'url' => getBaseUrl('/dashboard'),
        'roles' => ['guest', 'user', 'admin'],
        'description' => 'Overview of your tasks.'
      ],
      [
        'title' => 'My Tasks',
        'icon' => 'bi-list-check',
        'url' => getBaseUrl('/tasks'),
        'roles' => ['guest', 'user', 'admin'],
        'description' => 'Manage your daily tasks.'
      ]
    ]
  ]
];
