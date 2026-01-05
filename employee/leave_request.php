<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['pegawai']);
require_once __DIR__ . '/../inc/flash.php';

$id_employee = $_SESSION['user']['id_employee'];
if (!$id_employee) die("Akun belum terhubung ke employee.");

$jenis = $pdo->query("SELECT * FROM jenis ORDER BY id_jenis")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo->prepare("
    INSERT INTO leave_request (id_employee, id_jenis, tanggal_mulai, tanggal_berakhir, alasan)
    VALUES (?,?,?,?,?)
  ")->execute([
    $id_employee,
    (int)$_POST['id_jenis'],
    $_POST['tanggal_mulai'],
    $_POST['tanggal_berakhir'],
    trim($_POST['alasan'])
  ]);
  set_flash('success', 'Pengajuan terkirim.');
  header("Location: leave_request.php"); exit;
}

$stmt = $pdo->prepare("
  SELECT lr.*, j.jenis
  FROM leave_request lr
  JOIN jenis j ON j.id_jenis = lr.id_jenis
  WHERE lr.id_employee=?
  ORDER BY lr.id_leave DESC
");
$stmt->execute([$id_employee]);
$list = $stmt->fetchAll();

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Pengajuan Izin", "Ajukan izin/sakit/cuti dengan tanggal mulai dan berakhir.") ?>

<?= ui_card_start("Buat Pengajuan") ?>
  <form method="post" class="grid md:grid-cols-4 gap-3">
    <select name="id_jenis" class="<?= ui_select_class() ?>" required>
      <?php foreach($jenis as $j): ?>
        <option value="<?= (int)$j['id_jenis'] ?>"><?= htmlspecialchars($j['jenis']) ?></option>
      <?php endforeach; ?>
    </select>

    <input type="date" name="tanggal_mulai" class="<?= ui_input_class() ?>" required>
    <input type="date" name="tanggal_berakhir" class="<?= ui_input_class() ?>" required>
    <input name="alasan" class="<?= ui_input_class() ?>" placeholder="Alasan" required>

    <button class="<?= ui_btn_class('alt') ?>">Kirim</button>
    <a class="<?= ui_btn_class('ghost') ?>" href="<?= BASE_URL ?>/employee/dashboard.php">Kembali</a>
  </form>
<?= ui_card_end() ?>

<div class="mt-5">
<?php
if (!$list) {
  echo ui_empty("Belum ada pengajuan.", "Kalau urgent, kirim pengajuan dulu.");
} else {
  echo ui_table_start(['ID','Jenis','Mulai','Berakhir','Alasan','Status']);
  foreach($list as $r){
    $ap = $pdo->prepare("SELECT keputusan, approved_at FROM leave_aproval WHERE id_leave=?");
    $ap->execute([(int)$r['id_leave']]);
    $apr = $ap->fetch();

    $statusText = $apr ? $apr['keputusan'] : 'PENDING';
    $tone = $apr ? (($apr['keputusan']==='APPROVED')?'ok':'bad') : 'warn';
    $badge = ui_badge($statusText, $tone);

    echo ui_row_start();
    echo ui_td((int)$r['id_leave'], true);
    echo ui_td(htmlspecialchars($r['jenis']));
    echo ui_td(htmlspecialchars($r['tanggal_mulai']), true);
    echo ui_td(htmlspecialchars($r['tanggal_berakhir']), true);
    echo ui_td(htmlspecialchars($r['alasan']));
    echo ui_td($badge . ($apr ? "<div class='text-xs text-black/70 mt-1'>".$apr['approved_at']."</div>" : ""), true);
    echo ui_row_end();
  }
  echo ui_table_end();
}
?>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
