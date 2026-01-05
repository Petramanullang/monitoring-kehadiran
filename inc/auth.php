<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/flash.php';

function require_login() {
  if (empty($_SESSION['user'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
  }
}

function require_role($roles) {
  require_login();
  $role = $_SESSION['user']['role_name'] ?? '';
  if (!in_array($role, (array)$roles, true)) {
    set_flash('danger', 'Akses ditolak.');
    header("Location: " . BASE_URL . "/index.php");
    exit;
  }
}
