<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['pegawai']);
$id_employee = $_SESSION['user']['id_employee'];

$stmt = $pdo->prepare("SELECT * FROM attendance WHERE id_employee=? ORDER BY tanggal DESC LIMIT 200");
$stmt->execute([$id_employee]);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Riwayat Absensi", "Catatan berisi IZIN/SAKIT/CUTI jika diset oleh approval.") ?>

<?php
if (!$rows) {
  echo ui_empty("Belum ada riwayat.", "Absensi dulu atau minta jadwal dari Admin HR.");
} else {
  echo ui_table_start(['Tanggal','Check-in','Check-out','Catatan']);
  foreach($rows as $r){
    echo ui_row_start();
    echo ui_td(htmlspecialchars($r['tanggal']), true);
    echo ui_td(htmlspecialchars($r['check_in'] ?? '-'), true);
    echo ui_td(htmlspecialchars($r['check_out'] ?? '-'), true);

    $note = $r['note'] ?? '';
    $badge = $note ? ui_badge(htmlspecialchars($note), 'info') : '-';
    echo ui_td($badge, true);

    echo ui_row_end();
  }
  echo ui_table_end();
}
?>

<div class="mt-6">
  <a class="<?= ui_btn_class('ghost') ?>" href="<?= BASE_URL ?>/employee/dashboard.php">Kembali</a>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
