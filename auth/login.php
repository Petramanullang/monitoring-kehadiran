<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';

if (!empty($_SESSION['user'])) {
  header("Location: " . BASE_URL . "/index.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare("
    SELECT u.*, r.role_name
    FROM `user` u
    JOIN role r ON r.id_role = u.id_role
    WHERE u.username = ?
    LIMIT 1
  ");
  $stmt->execute([$username]);
  $u = $stmt->fetch();

  if (!$u || !password_verify($password, $u['password'])) {
    set_flash('danger', 'Username / password salah.');
  } else {
    $stmt2 = $pdo->prepare("SELECT id_employee FROM employee WHERE id_user = ? LIMIT 1");
    $stmt2->execute([$u['id_user']]);
    $emp = $stmt2->fetch();

    $_SESSION['user'] = [
      'id_user' => (int)$u['id_user'],
      'username' => $u['username'],
      'role_name' => $u['role_name'],
      'id_employee' => $emp ? (int)$emp['id_employee'] : null,
    ];
    header("Location: " . BASE_URL . "/index.php");
    exit;
  }
}

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Login", "Masuk untuk mengelola absensi dan izin pegawai.") ?>

<div class="grid md:grid-cols-2 gap-6">
  <?= ui_card_start("Akses cepat") ?>
    <div class="bg-yellow-200 border-2 border-black p-4 shadow-[6px_6px_0_0_#000]">
      <div class="font-black text-black">Admin Default</div>
      <div class="mt-2 text-sm text-black/80">
        username: <span class="font-black">admin</span><br>
        password: <span class="font-black">admin123</span>
      </div>
    </div>
    <div class="mt-4 text-sm text-black/70">
      Buat akun pegawai/atasan di menu Admin → Users, lalu hubungkan ke Employees.
    </div>
  <?= ui_card_end() ?>

  <?= ui_card_start("Masuk") ?>
    <form method="post" class="grid gap-4">
      <div>
        <label class="text-sm font-black text-black">Username</label>
        <input name="username" required class="<?= ui_input_class() ?>" placeholder="contoh: admin">
      </div>
      <div>
        <label class="text-sm font-black text-black">Password</label>
        <input name="password" type="password" required class="<?= ui_input_class() ?>" placeholder="••••••••">
      </div>
      <button class="<?= ui_btn_class('solid') ?>">Masuk</button>
    </form>
  <?= ui_card_end() ?>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
