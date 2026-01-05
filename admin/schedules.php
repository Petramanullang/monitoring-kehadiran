<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/flash.php';

$employees = $pdo->query("SELECT id_employee, nama FROM employee ORDER BY nama")->fetchAll();
$shifts    = $pdo->query("SELECT * FROM shift ORDER BY id_shift")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $id_employee = (int)$_POST['id_employee'];
    $tanggal = $_POST['tanggal'] ?? '';
    $id_shift = (int)$_POST['id_shift'];

    if (!$id_employee || !$tanggal || !$id_shift) {
      set_flash('danger','Input tidak lengkap.');
      header("Location: schedules.php"); exit;
    }

    $chk = $pdo->prepare("SELECT id_schedule FROM schedule WHERE id_employee=? AND tanggal=? LIMIT 1");
    $chk->execute([$id_employee, $tanggal]);
    $exist = $chk->fetch();

    if ($exist) {
      $pdo->prepare("UPDATE schedule SET id_shift=? WHERE id_schedule=?")
          ->execute([$id_shift, (int)$exist['id_schedule']]);
      set_flash('success','Jadwal diupdate.');
    } else {
      $pdo->prepare("INSERT INTO schedule (id_employee, tanggal, id_shift) VALUES (?,?,?)")
          ->execute([$id_employee, $tanggal, $id_shift]);
      set_flash('success','Jadwal ditambah.');
    }

    header("Location: schedules.php?date=".$tanggal); exit;
  }

  if ($action === 'delete') {
    $id_schedule = (int)$_POST['id_schedule'];
    $pdo->prepare("DELETE FROM schedule WHERE id_schedule=?")->execute([$id_schedule]);
    set_flash('success','Jadwal dihapus.');
    header("Location: schedules.php"); exit;
  }
}

$filter_date = $_GET['date'] ?? date('Y-m-d');
$stmt = $pdo->prepare("
  SELECT s.id_schedule, s.tanggal, e.nama, sh.nama_shift, sh.jam_masuk, sh.jam_keluar
  FROM schedule s
  JOIN employee e ON e.id_employee = s.id_employee
  JOIN shift sh ON sh.id_shift = s.id_shift
  WHERE s.tanggal = ?
  ORDER BY e.nama
");
$stmt->execute([$filter_date]);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Schedule", "Atur jadwal shift per pegawai untuk tanggal tertentu.") ?>

<?= ui_card_start("Tambah / Update Jadwal", "
  <form method='get' class='flex gap-2'>
    <input type='date' name='date' class='".ui_input_class()."' value='".htmlspecialchars($filter_date, ENT_QUOTES)."'>
    <button class='".ui_btn_class('ghost')."'>Filter</button>
  </form>
") ?>
  <form method="post" class="grid md:grid-cols-4 gap-3">
    <input type="hidden" name="action" value="create">
    <select name="id_employee" class="<?= ui_select_class() ?>" required>
      <option value="">Employee</option>
      <?php foreach($employees as $e): ?>
        <option value="<?= (int)$e['id_employee'] ?>"><?= htmlspecialchars($e['nama']) ?></option>
      <?php endforeach; ?>
    </select>

    <input type="date" name="tanggal" class="<?= ui_input_class() ?>" value="<?= htmlspecialchars($filter_date) ?>" required>

    <select name="id_shift" class="<?= ui_select_class() ?>" required>
      <option value="">Shift</option>
      <?php foreach($shifts as $sh): ?>
        <option value="<?= (int)$sh['id_shift'] ?>">
          <?= htmlspecialchars($sh['nama_shift'].' ('.$sh['jam_masuk'].'-'.$sh['jam_keluar'].')') ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="<?= ui_btn_class('alt') ?>">Simpan</button>
  </form>
<?= ui_card_end() ?>

<div class="mt-5">
<?php
if (!$rows) {
  echo ui_empty("Belum ada jadwal di tanggal $filter_date.", "Tambah jadwal di form atas.");
} else {
  echo ui_table_start(['Tanggal','Nama','Shift','Jam','Aksi']);
  foreach($rows as $r){
    echo ui_row_start();
    echo ui_td(htmlspecialchars($r['tanggal']), true);
    echo ui_td("<div class='font-black'>".htmlspecialchars($r['nama'])."</div>");
    echo ui_td(htmlspecialchars($r['nama_shift']));
    echo ui_td(htmlspecialchars($r['jam_masuk'].' - '.$r['jam_keluar']), true);

    $del = "
      <form method='post' onsubmit=\"return confirm('Hapus jadwal ini?')\">
        <input type='hidden' name='action' value='delete'>
        <input type='hidden' name='id_schedule' value='".(int)$r['id_schedule']."'>
        <button class='".ui_btn_class('danger')."'>Hapus</button>
      </form>
    ";
    echo ui_td($del, true);
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
