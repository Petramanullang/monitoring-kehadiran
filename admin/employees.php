<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/flash.php';

$users = $pdo->query("
  SELECT u.id_user, u.username, r.role_name
  FROM `user` u
  JOIN role r ON r.id_role=u.id_role
  ORDER BY u.id_user DESC
")->fetchAll();

$positions = $pdo->query("SELECT * FROM position ORDER BY id_position")->fetchAll();
$depts     = $pdo->query("SELECT * FROM departments ORDER BY id_department")->fetchAll();
$stats     = $pdo->query("SELECT * FROM status ORDER BY id_status")->fetchAll();

$employeesForSupervisor = $pdo->query("SELECT id_employee, nama FROM employee ORDER BY nama")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $id_user = (int)$_POST['id_user'];
    $nama = trim($_POST['nama'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $id_position = (int)$_POST['id_position'];
    $id_departments = (int)$_POST['id_departments'];
    $atasan_id = $_POST['atasan_id'] !== '' ? (int)$_POST['atasan_id'] : null;
    $id_status = (int)$_POST['id_status'];

    if (!$id_user || $nama==='' || !$id_position || !$id_departments || !$id_status) {
      set_flash('danger','Input tidak lengkap.');
      header("Location: employees.php"); exit;
    }

    try {
      $pdo->prepare("
        INSERT INTO employee (id_user, nama, nip, id_position, id_departments, atasan_id, id_status)
        VALUES (?,?,?,?,?,?,?)
      ")->execute([$id_user, $nama, ($nip===''?null:$nip), $id_position, $id_departments, $atasan_id, $id_status]);

      set_flash('success','Employee ditambah.');
    } catch (Exception $e) {
      set_flash('danger','Gagal tambah employee: '.$e->getMessage());
    }
    header("Location: employees.php"); exit;
  }

  if ($action === 'update') {
    $id_employee = (int)$_POST['id_employee'];
    $nama = trim($_POST['nama'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $id_position = (int)$_POST['id_position'];
    $id_departments = (int)$_POST['id_departments'];
    $atasan_id = $_POST['atasan_id'] !== '' ? (int)$_POST['atasan_id'] : null;
    $id_status = (int)$_POST['id_status'];

    if (!$id_employee || $nama==='' || !$id_position || !$id_departments || !$id_status) {
      set_flash('danger','Input tidak lengkap.');
      header("Location: employees.php"); exit;
    }
    if ($atasan_id !== null && $atasan_id === $id_employee) {
      set_flash('danger','Atasan tidak boleh dirinya sendiri.');
      header("Location: employees.php"); exit;
    }

    $pdo->prepare("
      UPDATE employee
      SET nama=?, nip=?, id_position=?, id_departments=?, atasan_id=?, id_status=?
      WHERE id_employee=?
    ")->execute([$nama, ($nip===''?null:$nip), $id_position, $id_departments, $atasan_id, $id_status, $id_employee]);

    set_flash('success','Employee diupdate.');
    header("Location: employees.php"); exit;
  }

  if ($action === 'delete') {
    $id_employee = (int)$_POST['id_employee'];

    $c1 = $pdo->prepare("SELECT COUNT(*) c FROM schedule WHERE id_employee=?"); $c1->execute([$id_employee]);
    $c2 = $pdo->prepare("SELECT COUNT(*) c FROM attendance WHERE id_employee=?"); $c2->execute([$id_employee]);
    $c3 = $pdo->prepare("SELECT COUNT(*) c FROM leave_request WHERE id_employee=?"); $c3->execute([$id_employee]);

    if ((int)$c1->fetch()['c']>0 || (int)$c2->fetch()['c']>0 || (int)$c3->fetch()['c']>0) {
      set_flash('danger','Tidak bisa hapus: employee sudah punya data transaksi.');
      header("Location: employees.php"); exit;
    }

    $pdo->prepare("DELETE FROM employee WHERE id_employee=?")->execute([$id_employee]);
    set_flash('success','Employee dihapus.');
    header("Location: employees.php"); exit;
  }
}

$rows = $pdo->query("
  SELECT e.*, u.username, p.nama_position, d.nama_department, s.status_name, sup.nama AS nama_atasan
  FROM employee e
  JOIN `user` u ON u.id_user = e.id_user
  JOIN position p ON p.id_position = e.id_position
  JOIN departments d ON d.id_department = e.id_departments
  JOIN status s ON s.id_status = e.id_status
  LEFT JOIN employee sup ON sup.id_employee = e.atasan_id
  ORDER BY e.id_employee DESC
")->fetchAll();

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Employees", "Data pegawai + set atasan untuk approval izin") ?>

<?= ui_card_start("Tambah Employee") ?>
  <form method="post" class="grid md:grid-cols-7 gap-3">
    <input type="hidden" name="action" value="create">

    <select name="id_user" class="<?= ui_select_class() ?>" required>
      <option value="">User</option>
      <?php foreach($users as $u): ?>
        <option value="<?= (int)$u['id_user'] ?>"><?= htmlspecialchars($u['username'].' ('.$u['role_name'].')') ?></option>
      <?php endforeach; ?>
    </select>

    <input name="nama" class="<?= ui_input_class() ?>" placeholder="Nama" required>
    <input name="nip" class="<?= ui_input_class() ?>" placeholder="NIP (opsional)">

    <select name="id_departments" class="<?= ui_select_class() ?>" required>
      <option value="">Dept</option>
      <?php foreach($depts as $d): ?>
        <option value="<?= (int)$d['id_department'] ?>"><?= htmlspecialchars($d['nama_department']) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="id_position" class="<?= ui_select_class() ?>" required>
      <option value="">Posisi</option>
      <?php foreach($positions as $p): ?>
        <option value="<?= (int)$p['id_position'] ?>"><?= htmlspecialchars($p['nama_position']) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="atasan_id" class="<?= ui_select_class() ?>">
      <option value="">Atasan</option>
      <?php foreach($employeesForSupervisor as $es): ?>
        <option value="<?= (int)$es['id_employee'] ?>"><?= htmlspecialchars($es['nama']) ?></option>
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
  echo ui_empty("Belum ada employee.", "Buat user dulu, lalu buat employee.");
} else {
  echo ui_table_start(['ID','User','Nama','Dept','Posisi','Atasan','Status','Aksi']);
  foreach($rows as $e){
    echo ui_row_start();
    echo ui_td((int)$e['id_employee'], true);
    echo ui_td(htmlspecialchars($e['username']));
    echo ui_td("<div class='font-black'>".htmlspecialchars($e['nama'])."</div><div class='text-xs text-black/70'>".htmlspecialchars($e['nip'] ?? '')."</div>");
    echo ui_td(htmlspecialchars($e['nama_department']));
    echo ui_td(htmlspecialchars($e['nama_position']));
    echo ui_td(htmlspecialchars($e['nama_atasan'] ?? '-'));
    $tone = (strtolower($e['status_name'])==='aktif')?'ok':'warn';
    echo ui_td(ui_badge(htmlspecialchars($e['status_name']), $tone), true);

    // update form
    $form = "
      <form method='post' class='grid gap-2'>
        <input type='hidden' name='action' value='update'>
        <input type='hidden' name='id_employee' value='".(int)$e['id_employee']."'>
        <input name='nama' class='".ui_input_class()."' value='".htmlspecialchars($e['nama'], ENT_QUOTES)."' required>
        <input name='nip' class='".ui_input_class()."' value='".htmlspecialchars($e['nip'] ?? '', ENT_QUOTES)."' placeholder='NIP (opsional)'>

        <div class='grid md:grid-cols-2 gap-2'>
          <select name='id_departments' class='".ui_select_class()."' required>
    ";
    foreach($depts as $d){
      $sel = ((int)$e['id_departments']==(int)$d['id_department'])?'selected':'';
      $form .= "<option value='".(int)$d['id_department']."' $sel>".htmlspecialchars($d['nama_department'])."</option>";
    }
    $form .= "</select><select name='id_position' class='".ui_select_class()."' required>";
    foreach($positions as $p){
      $sel = ((int)$e['id_position']==(int)$p['id_position'])?'selected':'';
      $form .= "<option value='".(int)$p['id_position']."' $sel>".htmlspecialchars($p['nama_position'])."</option>";
    }
    $form .= "</select></div>";

    $form .= "<div class='grid md:grid-cols-2 gap-2'><select name='atasan_id' class='".ui_select_class()."'><option value=''>Tanpa atasan</option>";
    foreach($employeesForSupervisor as $es){
      $sel = ((int)($e['atasan_id'] ?? 0)==(int)$es['id_employee'])?'selected':'';
      $form .= "<option value='".(int)$es['id_employee']."' $sel>".htmlspecialchars($es['nama'])."</option>";
    }
    $form .= "</select><select name='id_status' class='".ui_select_class()."' required>";
    foreach($stats as $s){
      $sel = ((int)$e['id_status']==(int)$s['id_status'])?'selected':'';
      $form .= "<option value='".(int)$s['id_status']."' $sel>".htmlspecialchars($s['status_name'])."</option>";
    }
    $form .= "</select></div>
      <button class='".ui_btn_class('solid')."'>Save</button>
      </form>
      <form method='post' class='mt-2' onsubmit=\"return confirm('Hapus employee ini?')\">
        <input type='hidden' name='action' value='delete'>
        <input type='hidden' name='id_employee' value='".(int)$e['id_employee']."'>
        <button class='".ui_btn_class('danger')."'>Hapus</button>
      </form>
    ";

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

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
