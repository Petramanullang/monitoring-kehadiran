<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/flash.php';

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';

// ================== MASTER DATA ==================
$users = $pdo->query("
  SELECT u.id_user, u.username, r.role_name
  FROM `user` u
  JOIN role r ON r.id_role = u.id_role
  ORDER BY u.id_user DESC
")->fetchAll();

$positions = $pdo->query("SELECT * FROM position ORDER BY id_position")->fetchAll();
$depts     = $pdo->query("SELECT * FROM departments ORDER BY id_department")->fetchAll();
$stats     = $pdo->query("SELECT * FROM status ORDER BY id_status")->fetchAll();

// Atasan hanya employee yang role user-nya 'atasan'
$supervisors = $pdo->query("
  SELECT e.id_employee, e.nama
  FROM employee e
  JOIN `user` u ON u.id_user = e.id_user
  JOIN role r ON r.id_role = u.id_role
  WHERE r.role_name = 'atasan'
  ORDER BY e.nama
")->fetchAll();

function is_protected_admin_username(string $username): bool {
  return strtolower($username) === 'admin';
}

// ================== ACTION HANDLERS ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // -------- CREATE --------
  if ($action === 'create') {
    $id_user        = (int)($_POST['id_user'] ?? 0);
    $nama           = trim($_POST['nama'] ?? '');
    $nip            = trim($_POST['nip'] ?? '');
    $id_position    = (int)($_POST['id_position'] ?? 0);
    $id_departments = (int)($_POST['id_departments'] ?? 0);
    $atasan_id      = ($_POST['atasan_id'] ?? '') !== '' ? (int)$_POST['atasan_id'] : null;
    $id_status      = (int)($_POST['id_status'] ?? 0);

    if (!$id_user || $nama === '' || !$id_position || !$id_departments || !$id_status) {
      set_flash('danger','Input tidak lengkap.');
      header("Location: employees.php"); exit;
    }

    try {
      $pdo->prepare("
        INSERT INTO employee (id_user, nama, nip, id_position, id_departments, atasan_id, id_status)
        VALUES (?,?,?,?,?,?,?)
      ")->execute([
        $id_user,
        $nama,
        ($nip === '' ? null : $nip),
        $id_position,
        $id_departments,
        $atasan_id,
        $id_status
      ]);

      set_flash('success','Employee ditambah.');
    } catch (PDOException $e) {
      // Pesan lebih manusiawi untuk duplicate NIP / user
      if ($e->getCode() === '23000') {
        set_flash('danger','Gagal tambah employee: kemungkinan User sudah dipakai employee lain atau NIP sudah digunakan.');
      } else {
        set_flash('danger','Gagal tambah employee: '.$e->getMessage());
      }
    }

    header("Location: employees.php"); exit;
  }

  // -------- UPDATE --------
  if ($action === 'update') {
    $id_employee    = (int)($_POST['id_employee'] ?? 0);
    $nama           = trim($_POST['nama'] ?? '');
    $nip            = trim($_POST['nip'] ?? '');
    $id_position    = (int)($_POST['id_position'] ?? 0);
    $id_departments = (int)($_POST['id_departments'] ?? 0);
    $atasan_id      = ($_POST['atasan_id'] ?? '') !== '' ? (int)$_POST['atasan_id'] : null;
    $id_status      = (int)($_POST['id_status'] ?? 0);

    if (!$id_employee || $nama === '' || !$id_position || !$id_departments || !$id_status) {
      set_flash('danger','Input tidak lengkap.');
      header("Location: employees.php"); exit;
    }
    if ($atasan_id !== null && $atasan_id === $id_employee) {
      set_flash('danger','Atasan tidak boleh dirinya sendiri.');
      header("Location: employees.php"); exit;
    }

    try {
      $pdo->prepare("
        UPDATE employee
        SET nama=?, nip=?, id_position=?, id_departments=?, atasan_id=?, id_status=?
        WHERE id_employee=?
      ")->execute([
        $nama,
        ($nip === '' ? null : $nip),
        $id_position,
        $id_departments,
        $atasan_id,
        $id_status,
        $id_employee
      ]);

      set_flash('success','Employee diupdate.');
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') {
        set_flash('danger','Gagal update employee: NIP sudah dipakai pegawai lain.');
      } else {
        set_flash('danger','Gagal update employee: '.$e->getMessage());
      }
    }

    header("Location: employees.php"); exit;
  }

  // -------- DELETE (HAPUS PAKSA, tapi diblok kalau username=admin) --------
  if ($action === 'delete') {
    $id_employee = (int)($_POST['id_employee'] ?? 0);
    if (!$id_employee) {
      set_flash('danger','ID tidak valid.');
      header("Location: employees.php"); exit;
    }

    // Cek username terkait employee (buat blok admin)
    $st = $pdo->prepare("
      SELECT u.username, e.id_user
      FROM employee e
      JOIN `user` u ON u.id_user = e.id_user
      WHERE e.id_employee = ?
      LIMIT 1
    ");
    $st->execute([$id_employee]);
    $info = $st->fetch();

    if (!$info) {
      set_flash('danger','Employee tidak ditemukan.');
      header("Location: employees.php"); exit;
    }

    $username = (string)$info['username'];
    $id_user  = (int)$info['id_user'];

    if (is_protected_admin_username($username)) {
      set_flash('danger','Akun admin tidak boleh dihapus.');
      header("Location: employees.php"); exit;
    }

    try {
      $pdo->beginTransaction();

      // Putuskan bawahan (fk_emp_atasan)
      $pdo->prepare("UPDATE employee SET atasan_id=NULL WHERE atasan_id=?")
          ->execute([$id_employee]);

      // Hapus approval yang dia lakukan
      $pdo->prepare("DELETE FROM leave_aproval WHERE approved_by=?")
          ->execute([$id_employee]);

      // Hapus approval yang terkait pengajuan milik dia
      $pdo->prepare("
        DELETE la
        FROM leave_aproval la
        JOIN leave_request lr ON lr.id_leave = la.id_leave
        WHERE lr.id_employee = ?
      ")->execute([$id_employee]);

      // Hapus pengajuan milik dia
      $pdo->prepare("DELETE FROM leave_request WHERE id_employee=?")
          ->execute([$id_employee]);

      // Hapus jadwal & absensi
      $pdo->prepare("DELETE FROM schedule WHERE id_employee=?")
          ->execute([$id_employee]);

      $pdo->prepare("DELETE FROM attendance WHERE id_employee=?")
          ->execute([$id_employee]);

      // Hapus employee
      $pdo->prepare("DELETE FROM employee WHERE id_employee=?")
          ->execute([$id_employee]);

      // Hapus user login-nya juga
      $pdo->prepare("DELETE FROM `user` WHERE id_user=?")
          ->execute([$id_user]);

      $pdo->commit();
      set_flash('success','Employee dihapus (beserta semua data terkait).');
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      set_flash('danger','Gagal hapus: '.$e->getMessage());
    }

    header("Location: employees.php"); exit;
  }
}

// ================== FETCH DATA ==================
$rows = $pdo->query("
  SELECT e.*,
         u.username,
         r.role_name,
         p.nama_position,
         d.nama_department,
         s.status_name,
         sup.nama AS nama_atasan
  FROM employee e
  JOIN `user` u ON u.id_user = e.id_user
  JOIN role r ON r.id_role = u.id_role
  JOIN position p ON p.id_position = e.id_position
  JOIN departments d ON d.id_department = e.id_departments
  JOIN status s ON s.id_status = e.id_status
  LEFT JOIN employee sup ON sup.id_employee = e.atasan_id
  ORDER BY e.id_employee DESC
")->fetchAll();

echo ui_page_start("Employees", "Tambah, edit, dan hapus employee (admin terkunci).");
?>

<?= ui_card_start("Tambah Employee") ?>
<form method="post" class="grid md:grid-cols-8 gap-3 items-end">
  <input type="hidden" name="action" value="create">

  <div class="md:col-span-2">
    <label class="text-sm font-black">User</label>
    <select name="id_user" class="<?= ui_select_class() ?>" required>
      <option value="">Pilih user</option>
      <?php foreach($users as $u): ?>
        <option value="<?= (int)$u['id_user'] ?>">
          <?= htmlspecialchars($u['username'].' ('.$u['role_name'].')') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="md:col-span-2">
    <label class="text-sm font-black">Nama</label>
    <input name="nama" class="<?= ui_input_class() ?>" placeholder="Nama" required>
  </div>

  <div>
    <label class="text-sm font-black">NIP</label>
    <input name="nip" class="<?= ui_input_class() ?>" placeholder="opsional">
  </div>

  <div>
    <label class="text-sm font-black">Dept</label>
    <select name="id_departments" class="<?= ui_select_class() ?>" required>
      <option value="">Pilih</option>
      <?php foreach($depts as $d): ?>
        <option value="<?= (int)$d['id_department'] ?>"><?= htmlspecialchars($d['nama_department']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="text-sm font-black">Posisi</label>
    <select name="id_position" class="<?= ui_select_class() ?>" required>
      <option value="">Pilih</option>
      <?php foreach($positions as $p): ?>
        <option value="<?= (int)$p['id_position'] ?>"><?= htmlspecialchars($p['nama_position']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="text-sm font-black">Atasan</label>
    <select name="atasan_id" class="<?= ui_select_class() ?>">
      <option value="">Tanpa atasan</option>
      <?php foreach($supervisors as $sv): ?>
        <option value="<?= (int)$sv['id_employee'] ?>"><?= htmlspecialchars($sv['nama']) ?></option>
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

  <div class="md:col-span-8">
    <button class="<?= ui_btn_class('alt') ?>">Tambah</button>
  </div>
</form>
<?= ui_card_end() ?>

<div class="mt-5">
<?php
if (!$rows) {
  echo ui_empty("Belum ada employee.", "Buat user dulu, lalu buat employee.");
} else {
  // ✅ tanpa kolom ID
  echo ui_table_start(['User','Role','Nama','Dept','Posisi','Atasan','Status','Aksi']);
  foreach($rows as $e){
    $username = (string)$e['username'];
    $isAdminProtected = is_protected_admin_username($username);

    echo ui_row_start();
    echo ui_td(htmlspecialchars($username));
    echo ui_td(ui_badge(htmlspecialchars($e['role_name']), 'neutral'), true);

    $namaCell = "<div class='font-black'>".htmlspecialchars($e['nama'])."</div>";
    $namaCell .= "<div class='text-xs text-black/70'>".htmlspecialchars($e['nip'] ?? '')."</div>";
    echo ui_td($namaCell);

    echo ui_td(htmlspecialchars($e['nama_department']));
    echo ui_td(htmlspecialchars($e['nama_position']));
    echo ui_td(htmlspecialchars($e['nama_atasan'] ?? '-'));

    $tone = (strtolower($e['status_name']) === 'aktif') ? 'ok' : 'warn';
    echo ui_td(ui_badge(htmlspecialchars($e['status_name']), $tone), true);

    // Aksi: Update + (Hapus kecuali admin)
    $idEmp = (int)$e['id_employee'];
    $form  = "<div class='grid gap-2 min-w-[280px]'>";

    $form .= "<form method='post' class='grid gap-2'>
      <input type='hidden' name='action' value='update'>
      <input type='hidden' name='id_employee' value='{$idEmp}'>

      <input name='nama' class='".ui_input_class()."' value='".htmlspecialchars($e['nama'], ENT_QUOTES)."' required>
      <input name='nip' class='".ui_input_class()."' value='".htmlspecialchars($e['nip'] ?? '', ENT_QUOTES)."' placeholder='NIP (opsional)'>

      <div class='grid grid-cols-2 gap-2'>
        <select name='id_departments' class='".ui_select_class()."' required>";
          foreach($depts as $d){
            $sel = ((int)$e['id_departments'] === (int)$d['id_department']) ? 'selected' : '';
            $form .= "<option value='".(int)$d['id_department']."' $sel>".htmlspecialchars($d['nama_department'])."</option>";
          }
    $form .= "</select>
        <select name='id_position' class='".ui_select_class()."' required>";
          foreach($positions as $p){
            $sel = ((int)$e['id_position'] === (int)$p['id_position']) ? 'selected' : '';
            $form .= "<option value='".(int)$p['id_position']."' $sel>".htmlspecialchars($p['nama_position'])."</option>";
          }
    $form .= "</select>
      </div>

      <div class='grid grid-cols-2 gap-2'>
        <select name='atasan_id' class='".ui_select_class()."'>
          <option value=''>Tanpa atasan</option>";
          foreach($supervisors as $sv){
            if ((int)$sv['id_employee'] === $idEmp) continue;
            $sel = ((int)($e['atasan_id'] ?? 0) === (int)$sv['id_employee']) ? 'selected' : '';
            $form .= "<option value='".(int)$sv['id_employee']."' $sel>".htmlspecialchars($sv['nama'])."</option>";
          }
    $form .= "</select>
        <select name='id_status' class='".ui_select_class()."' required>";
          foreach($stats as $s){
            $sel = ((int)$e['id_status'] === (int)$s['id_status']) ? 'selected' : '';
            $form .= "<option value='".(int)$s['id_status']."' $sel>".htmlspecialchars($s['status_name'])."</option>";
          }
    $form .= "</select>
      </div>

      <button class='".ui_btn_class('solid')."'>Save</button>
    </form>";

    if ($isAdminProtected) {
      $form .= "<div class='text-xs text-black/60 font-black'>Akun admin terkunci (tidak bisa dihapus).</div>";
    } else {
      $form .= "<form method='post'
        onsubmit=\"return confirm('Hapus employee ini? Semua data terkait (jadwal, absensi, izin, approval, akun login) akan ikut hilang dan tidak bisa dibatalkan!')\">
        <input type='hidden' name='action' value='delete'>
        <input type='hidden' name='id_employee' value='{$idEmp}'>
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
