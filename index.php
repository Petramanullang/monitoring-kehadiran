<?php
require_once __DIR__ . '/inc/auth.php';

if (empty($_SESSION['user'])) {
  header("Location: " . BASE_URL . "/auth/login.php");
  exit;
}

$role = $_SESSION['user']['role_name'];

if ($role === 'admin_hr') {
  header("Location: " . BASE_URL . "/admin/dashboard.php");
  exit;
}
if ($role === 'atasan') {
  header("Location: " . BASE_URL . "/manager/dashboard.php");
  exit;
}
header("Location: " . BASE_URL . "/employee/dashboard.php");
exit;
