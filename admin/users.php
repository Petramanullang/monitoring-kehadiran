<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/flash.php';

$roles  = $pdo->query("SELECT * FROM role ORDER BY id_role")->fetchAll();
$stats  = $pdo->query("SELECT * FROM status ORDER BY id_status")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $id_role  = (int)($_POST['id_role'] ?? 0);
    $id_status= (int)($_POST['id_status'] ?? 0);

    if ($username === '' || $password === '' || !$id_role || !$id_status) {
      set_flash('danger', 'Input tidak lengkap.');
      header("Location: users.php"); exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
      $pdo->prepare("INSERT INTO `user` (username, password, id_role, id_status) VALUES (?,?,?,?)")
          ->execute([$username, $hash, $id_role, $id_status]);
      set_flash('success', 'User berhasil dibuat.');
    } catch (Exception $e) {
      set_flash('danger', 'Gagal buat user: ' . $e->getMessage());
    }
    header("Location: users.php"); exit;
  }

  if ($action === 'update') {
    $id_user  = (int)($_POST['id_user'] ?? 0);
    $id_role  = (int)($_POST['id_role'] ?? 0);
    $id_status= (int)($_POST['id_status'] ?? 0);
    $password = $_POST['password'] ?? '';

    if (!$id_user || !$id_role || !$id_status) {
      set_flash('danger', 'Input tidak lengkap.');
      header("Location: users.php"); exit;
    }

    if ($password !== '') {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $pdo->prepare("UPDATE `user` SET id_role=?, id_status=?, password=? WHERE id_user=?")
          ->execute([$id_role, $id_status, $hash, $id_user]);
    } else {
      $pdo->prepare("UPDATE `user` SET id_role=?, id_status=? WHERE id_user=?")
          ->execute([$id_role, $id_status, $id_user]);
    }

    set_flash('success', 'User diupdate.');
    header("Location: users.php"); exit;
  }

  if ($action === 'delete') {
    $id_user = (int)($_POST['id_user'] ?? 0);

    $chk = $pdo->prepare("SELECT COUNT(*) c FROM employee WHERE id_user=?");
    $chk->execute([$id_user]);
    if ((int)$chk->fetch()['c'] > 0) {
      set_flash('danger', 'Tidak bisa hapus: user sudah terhubung ke employee.');
      header("Location: users.php"); exit;
    }

    $pdo->prepare("DELETE FROM `user` WHERE id_user=?")->execute([$id_user]);
    set_flash('success', 'User dihapus.');
    header("Location: users.php"); exit;
  }
}

$rows = $pdo->query("
  SELECT u.id_user, u.username, u.created_at,
         r.role_name, s.status_name,
         u.id_role, u.id_status
  FROM `user` u
  JOIN role r ON r.id_role = u.id_role
  JOIN status s ON s.id_status = u.id_status
  ORDER BY u.id_user DESC
")->fetchAll();

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Users", "Buat akun login untuk admin, pegawai, dan atasan.") ?>

<?= ui_card_start("Tambah User") ?>
  <form method="post" class="grid md:grid-cols-5 gap-3">
    <input type="hidden" name="action" value="create">
    <input name="username" class="<?= ui_input_class() ?>" placeholder="username" required>
    <input name="password" type="password" class="<?= ui_input_class() ?>" placeholder="password" required>

    <select name="id_role" class="<?= ui_select_class() ?>" required>
      <option value="">role</option>
      <?php foreach($roles as $r): ?>
        <option value="<?= (int)$r['id_role'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="id_status" class="<?= ui_select_class() ?>" required>
      <?php foreach($stats as $s): ?>
        <option value="<?= (int)$s['id_status'] ?>"><?= htmlspecialchars($s['status_name']) ?></option>
      <?php endforeach; ?>
    </select>

    <button class="<?= ui_btn_class('alt') ?>">Tambah</button>
  </form>
<?= ui_card_end() ?>

<div class="mt-5">
<?php
  if (!$rows) {
    echo ui_empty("Belum ada user.", "Tambah user pertama di atas.");
  } else {
    echo ui_table_start(['ID','Username','Role','Status','Dibuat','Aksi']);
    foreach($rows as $u){
      echo ui_row_start();
      echo ui_td((int)$u['id_user'], true);
      echo ui_td(htmlspecialchars($u['username']));
      echo ui_td(ui_badge(htmlspecialchars($u['role_name']), 'info'), true);

      $tone = (strtolower($u['status_name'])==='aktif') ? 'ok' : 'warn';
      echo ui_td(ui_badge(htmlspecialchars($u['status_name']), $tone), true);

      echo ui_td(htmlspecialchars($u['created_at']), true);

      $edit = "
        <form method='post' class='grid md:grid-cols-4 gap-2 items-center'>
          <input type='hidden' name='action' value='update'>
          <input type='hidden' name='id_user' value='".(int)$u['id_user']."'>
          <select name='id_role' class='".ui_select_class()."' required>
      ";
      foreach($roles as $r){
        $sel = ((int)$u['id_role']==(int)$r['id_role'])?'selected':'';
        $edit .= "<option value='".(int)$r['id_role']."' $sel>".htmlspecialchars($r['role_name'])."</option>";
      }
      $edit .= "</select><select name='id_status' class='".ui_select_class()."' required>";
      foreach($stats as $s){
        $sel = ((int)$u['id_status']==(int)$s['id_status'])?'selected':'';
        $edit .= "<option value='".(int)$s['id_status']."' $sel>".htmlspecialchars($s['status_name'])."</option>";
      }
      $edit .= "
          </select>
          <input name='password' type='password' class='".ui_input_class()."' placeholder='pass baru (opsional)'>
          <button class='".ui_btn_class('solid')."'>Save</button>
        </form>
        <form method='post' class='mt-2' onsubmit=\"return confirm('Hapus user ini?')\">
          <input type='hidden' name='action' value='delete'>
          <input type='hidden' name='id_user' value='".(int)$u['id_user']."'>
          <button class='".ui_btn_class('danger')."'>Hapus</button>
        </form>
      ";
      echo ui_td($edit);
      echo ui_row_end();
    }
    echo ui_table_end();
  }
?>
</div>

<div class="mt-6">
  <a class="<?= ui_btn_class('ghost') ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Kembali</a>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
