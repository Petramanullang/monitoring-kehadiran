<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['pegawai']);
require_once __DIR__ . '/../inc/flash.php';

$id_employee = $_SESSION['user']['id_employee'];
if (!$id_employee) { die("Akun ini belum terhubung ke employee."); }

$today = date('Y-m-d');

$stmt = $pdo->prepare("
  SELECT s.id_schedule, sh.nama_shift, sh.jam_masuk, sh.jam_keluar
  FROM schedule s
  JOIN shift sh ON sh.id_shift = s.id_shift
  WHERE s.id_employee = ? AND s.tanggal = ?
  LIMIT 1
");
$stmt->execute([$id_employee, $today]);
$schedule = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  $stmtA = $pdo->prepare("SELECT * FROM attendance WHERE id_employee=? AND tanggal=? LIMIT 1");
  $stmtA->execute([$id_employee, $today]);
  $att = $stmtA->fetch();

  if (!$att) {
    $pdo->prepare("INSERT INTO attendance (id_employee, tanggal) VALUES (?,?)")
        ->execute([$id_employee, $today]);
    $stmtA->execute([$id_employee, $today]);
    $att = $stmtA->fetch();
  }

  if ($action === 'checkin' && empty($att['check_in'])) {
    $pdo->prepare("UPDATE attendance SET check_in=NOW(), note=NULL WHERE id_attendance=?")
        ->execute([(int)$att['id_attendance']]);
    set_flash('success', 'Check-in berhasil.');
    header("Location: attendance_today.php"); exit;
  }

  if ($action === 'checkout' && !empty($att['check_in']) && empty($att['check_out'])) {
    $pdo->prepare("UPDATE attendance SET check_out=NOW() WHERE id_attendance=?")
        ->execute([(int)$att['id_attendance']]);
    set_flash('success', 'Check-out berhasil.');
    header("Location: attendance_today.php"); exit;
  }

  set_flash('danger', 'Aksi tidak valid.');
  header("Location: attendance_today.php"); exit;
}

$stmtB = $pdo->prepare("SELECT * FROM attendance WHERE id_employee=? AND tanggal=? LIMIT 1");
$stmtB->execute([$id_employee, $today]);
$att = $stmtB->fetch();

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Absensi Hari Ini", "Tanggal: $today") ?>

<?php if (!$schedule): ?>
  <?= ui_empty("Belum ada jadwal hari ini.", "Minta Admin HR buat schedule untuk kamu.") ?>
<?php else: ?>
  <?= ui_card_start("Jadwal Kamu") ?>
    <div class="grid sm:grid-cols-2 gap-3">
      <div class="bg-cyan-200 border-2 border-black p-4 shadow-[6px_6px_0_0_#000]">
        <div class="text-xs text-black/70 font-black">SHIFT</div>
        <div class="text-xl font-black text-black"><?= htmlspecialchars($schedule['nama_shift']) ?></div>
      </div>
      <div class="bg-yellow-200 border-2 border-black p-4 shadow-[6px_6px_0_0_#000]">
        <div class="text-xs text-black/70 font-black">JAM</div>
        <div class="text-xl font-black text-black"><?= htmlspecialchars($schedule['jam_masuk'].' - '.$schedule['jam_keluar']) ?></div>
      </div>
    </div>
  <?= ui_card_end() ?>

  <div class="mt-5">
    <?= ui_card_start("Status Absensi") ?>
      <div class="grid md:grid-cols-2 gap-3">
        <div class="bg-white border-2 border-black p-4 shadow-[6px_6px_0_0_#000]">
          <div class="text-xs text-black/70 font-black">CHECK-IN</div>
          <div class="text-lg font-black text-black"><?= htmlspecialchars($att['check_in'] ?? '-') ?></div>
        </div>
        <div class="bg-white border-2 border-black p-4 shadow-[6px_6px_0_0_#000]">
          <div class="text-xs text-black/70 font-black">CHECK-OUT</div>
          <div class="text-lg font-black text-black"><?= htmlspecialchars($att['check_out'] ?? '-') ?></div>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-3">
        <form method="post">
          <input type="hidden" name="action" value="checkin">
          <button class="<?= ui_btn_class('alt') ?>" <?= (!empty($att['check_in'])) ? 'disabled' : '' ?>>Check-in</button>
        </form>
        <form method="post">
          <input type="hidden" name="action" value="checkout">
          <button class="<?= ui_btn_class('solid') ?>" <?= (empty($att['check_in']) || !empty($att['check_out'])) ? 'disabled' : '' ?>>Check-out</button>
        </form>
        <a class="<?= ui_btn_class('ghost') ?>" href="<?= BASE_URL ?>/employee/dashboard.php">Kembali</a>
      </div>
    <?= ui_card_end() ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
