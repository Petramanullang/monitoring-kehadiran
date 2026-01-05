<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/flash.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'create') {
    $stmt = $pdo->prepare("INSERT INTO shift (nama_shift, jam_masuk, jam_keluar) VALUES (?,?,?)");
    $stmt->execute([trim($_POST['nama_shift']), $_POST['jam_masuk'], $_POST['jam_keluar']]);
    set_flash('success','Shift ditambah.');
    header("Location: shifts.php"); exit;
  }
  if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM shift WHERE id_shift=?");
    $stmt->execute([(int)$_POST['id_shift']]);
    set_flash('success','Shift dihapus.');
    header("Location: shifts.php"); exit;
  }
}

$rows = $pdo->query("SELECT * FROM shift ORDER BY id_shift DESC")->fetchAll();

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Shift", "Atur jam masuk & jam keluar") ?>

<?= ui_card_start("Tambah Shift") ?>
  <form method="post" class="grid md:grid-cols-4 gap-3">
    <input type="hidden" name="action" value="create">
    <input name="nama_shift" class="<?= ui_input_class() ?>" placeholder="Nama shift" required>
    <input name="jam_masuk" type="time" class="<?= ui_input_class() ?>" required>
    <input name="jam_keluar" type="time" class="<?= ui_input_class() ?>" required>
    <button class="<?= ui_btn_class('alt') ?>">Tambah</button>
  </form>
<?= ui_card_end() ?>

<div class="mt-5">
<?php
  if (!$rows) {
    echo ui_empty("Belum ada shift.", "Tambah shift dulu untuk membuat jadwal.");
  } else {
    echo ui_table_start(['ID','Nama','Masuk','Keluar','Aksi']);
    foreach($rows as $r){
      echo ui_row_start();
      echo ui_td((int)$r['id_shift'], true);
      echo ui_td(htmlspecialchars($r['nama_shift']));
      echo ui_td(htmlspecialchars($r['jam_masuk']), true);
      echo ui_td(htmlspecialchars($r['jam_keluar']), true);
      $btn = "
        <form method='post' onsubmit=\"return confirm('Hapus shift?')\">
          <input type='hidden' name='action' value='delete'>
          <input type='hidden' name='id_shift' value='".(int)$r['id_shift']."'>
          <button class='".ui_btn_class('danger')."'>Hapus</button>
        </form>
      ";
      echo ui_td($btn, true);
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
