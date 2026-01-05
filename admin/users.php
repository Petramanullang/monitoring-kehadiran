<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/flash.php';

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';

$roles = $pdo->query("SELECT * FROM role ORDER BY id_role")->fetchAll();
$stats = $pdo->query("SELECT * FROM status ORDER BY id_status")->fetchAll();

function is_protected_admin_username(string $username): bool {
  return strtolower($username) === 'admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // CREATE
  if ($action === 'create') {
    $username  = trim($_POST['username'] ?? '');
    $password  = (string)($_POST['password'] ?? '');
    $id_role   = (int)($_POST['id_role'] ?? 0);
    $id_status = (int)($_POST['id_status'] ?? 0);

    if ($username === '' || $password === '' || !$id_role || !$id_status) {
      set_flash('danger','Input tidak lengkap.');
      header("Location: users.php"); exit;
    }

    try {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $pdo->prepare("
        INSERT INTO `user` (username, password, id_role, id_status)
        VALUES (?,?,?,?)
      ")->execute([$username, $hash, $id_role, $id_status]);

      set_flash('success','User ditambah.');
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') {
        set_flash('danger','Gagal tambah user: username sudah dipakai.');
      } else {
        set_flash('danger','Gagal tambah user: '.$e->getMessage());
      }
    }

    header("Location: users.php"); exit;
  }

  // UPDATE
  if ($action === 'update') {
    $id_user   = (int)($_POST['id_user'] ?? 0);
    $id_role   = (int)($_POST['id_role'] ?? 0);
    $id_status = (int)($_POST['id_status'] ?? 0);
    $new_pass  = (string)($_POST['new_password'] ?? '');

    if (!$id_user || !$id_role || !$id_status) {
      set_flash('danger','Input tidak lengkap.');
      header("Location: users.php"); exit;
    }

    try {
      if ($new_pass !== '') {
        $hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE `user` SET id_role=?, id_status=?, password=? WHERE id_user=?")
            ->execute([$id_role, $id_status, $hash, $id_user]);
      } else {
        $pdo->prepare("UPDATE `user` SET id_role=?, id_status=? WHERE id_user=?")
            ->execute([$id_role, $id_status, $id_user]);
      }
      set_flash('success','User diupdate.');
    } catch (Exception $e) {
      set_flash('danger','Gagal update user: '.$e->getMessage());
    }

    header("Location: users.php"); exit;
  }

  // DELETE (tapi blok username=admin, dan blok kalau sudah jadi employee)
  if ($action === 'delete') {
    $id_user = (int)($_POST['id_user'] ?? 0);
    if (!$id_user) {
      set_flash('danger','ID tidak valid.');
      header("Location: users.php"); exit;
    }

    // ambil username
    $st = $pdo->prepare("SELECT username FROM `user` WHERE id_user=? LIMIT 1");
    $st->execute([$id_user]);
    $u = $st->fetch();
    if (!$u) {
      set_flash('danger','User tidak ditemukan.');
      header("Location: users.php"); exit;
    }

    $username = (string)$u['username'];
    if (is_protected_admin_username($username)) {
      set_flash('danger','Akun admin tidak boleh dihapus.');
      header("Location: users.php"); exit;
    }

    // kalau sudah dipakai employee, blok (biar nggak bikin FK error)
    $c = $pdo->prepare("SELECT COUNT(*) c FROM employee WHERE id_user=?");
    $c->execute([$id_user]);
    if ((int)$c->fetch()['c'] > 0) {
      set_flash('danger','Tidak bisa hapus user: user ini sudah terhubung ke employee. Hapus employee-nya dulu.');
      header("Location: users.php"); exit;
    }

    try {
      $pdo->prepare("DELETE FROM `user` WHERE id_user=?")->execute([$id_user]);
      set_flash('success','User dihapus.');
    } catch (Exception $e) {
      set_flash('danger','Gagal hapus user: '.$e->getMessage());
    }

    header("Location: users.php"); exit;
  }
}

// FETCH
$rows = $pdo->query("
  SELECT u.id_user, u.username, u.id_role, u.id_status, u.created_at,
         r.role_name, s.status_name
  FROM `user` u
  JOIN role r ON r.id_role = u.id_role
  JOIN status s ON s.id_status = u.id_status
  ORDER BY u.id_user DESC
")->fetchAll();

echo ui_page_start("Users", "Tambah, edit, dan hapus user (admin terkunci).");
?>

<?= ui_card_start("Tambah User") ?>
<form method="post" class="grid md:grid-cols-5 gap-3 items-end">
  <input type="hidden" name="action" value="create">

  <div class="md:col-span-2">
    <label class="text-sm font-black">Username</label>
    <input name="username" class="<?= ui_input_class() ?>" placeholder="contoh: petra" required>
  </div>

  <div>
    <label class="text-sm font-black">Password</label>
    <input type="password" name="password" class="<?= ui_input_class() ?>" placeholder="buat sekali" required>
  </div>

  <div>
    <label class="text-sm font-black">Role</label>
    <select name="id_role" class="<?= ui_select_class() ?>" required>
      <option value="">Pilih</option>
      <?php foreach($roles as $r): ?>
        <option value="<?= (int)$r['id_role'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="text-sm font-black">Status</label>
    <select name="id_status" class="<?= ui_select_class() ?>" required>
      <?php foreach($stats as $s): ?>
        <option value="<?= (int)$s['id_status'] ?>"><?= htmlspecialchars($s['status_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="md:col-span-5">
    <button class="<?= ui_btn_class('alt') ?>">Tambah</button>
  </div>
</form>
<?= ui_card_end() ?>

<div class="mt-5">
<?php
if (!$rows) {
  echo ui_empty("Belum ada user.", "Tambah user dari form di atas.");
} else {
  // ✅ tanpa kolom ID
  echo ui_table_start(['Username','Role','Status','Dibuat','Aksi']);
  foreach($rows as $u){
    $username = (string)$u['username'];
    $isAdminProtected = is_protected_admin_username($username);

    echo ui_row_start();
    echo ui_td("<div class='font-black'>".htmlspecialchars($username)."</div>");
    echo ui_td(ui_badge(htmlspecialchars($u['role_name']), 'neutral'), true);

    $tone = (strtolower($u['status_name']) === 'aktif') ? 'ok' : 'warn';
    echo ui_td(ui_badge(htmlspecialchars($u['status_name']), $tone), true);

    echo ui_td(htmlspecialchars($u['created_at']));

    $idUser = (int)$u['id_user'];

    $form = "<div class='grid gap-2 min-w-[280px]'>
      <form method='post' class='grid gap-2'>
        <input type='hidden' name='action' value='update'>
        <input type='hidden' name='id_user' value='{$idUser}'>

        <div class='grid grid-cols-2 gap-2'>
          <select name='id_role' class='".ui_select_class()."' required>";
            foreach($roles as $r){
              $sel = ((int)$u['id_role'] === (int)$r['id_role']) ? 'selected' : '';
              $form .= "<option value='".(int)$r['id_role']."' $sel>".htmlspecialchars($r['role_name'])."</option>";
            }
    $form .= "</select>
          <select name='id_status' class='".ui_select_class()."' required>";
            foreach($stats as $s){
              $sel = ((int)$u['id_status'] === (int)$s['id_status']) ? 'selected' : '';
              $form .= "<option value='".(int)$s['id_status']."' $sel>".htmlspecialchars($s['status_name'])."</option>";
            }
    $form .= "</select>
        </div>

        <input type='password' name='new_password' class='".ui_input_class()."' placeholder='Reset password (opsional)'>
        <button class='".ui_btn_class('solid')."'>Save</button>
      </form>";

    if ($isAdminProtected) {
      $form .= "<div class='text-xs text-black/60 font-black'>Akun admin terkunci (tidak bisa dihapus).</div>";
    } else {
      $form .= "<form method='post'
        onsubmit=\"return confirm('Hapus user ini? (Jika user sudah jadi employee, penghapusan akan ditolak)')\">
        <input type='hidden' name='action' value='delete'>
        <input type='hidden' name='id_user' value='{$idUser}'>
        <button class='".ui_btn_class('danger')."'>Hapus</button>
      </form>";
    }

    $form .= "</div>";

    echo ui_td($form);
    echo ui_row_end();
  }
  echo ui_table_end();
}
?>
</div>

<div class="mt-6">
  <a class="<?= ui_btn_class('ghost') ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Kembali</a>
</div>
