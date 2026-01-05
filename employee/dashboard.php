<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['pegawai']);
require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Dashboard Pegawai", "Absensi cepat + ajukan izin") ?>

<div class="grid md:grid-cols-2 gap-4">
  <a href="<?= BASE_URL ?>/employee/attendance_today.php" class="bg-white border-2 border-black p-5 shadow-[8px_8px_0_0_#000] hover:bg-yellow-100 transition">
    <div class="font-black text-black">Absensi Hari Ini</div>
    <div class="text-sm text-black/70 mt-2">Check-in & check-out sesuai jadwal.</div>
  </a>

  <a href="<?= BASE_URL ?>/employee/attendance_history.php" class="bg-white border-2 border-black p-5 shadow-[8px_8px_0_0_#000] hover:bg-cyan-100 transition">
    <div class="font-black text-black">Riwayat Absensi</div>
    <div class="text-sm text-black/70 mt-2">Lihat histori dan catatan izin/cuti.</div>
  </a>

  <a href="<?= BASE_URL ?>/employee/leave_request.php" class="bg-white border-2 border-black p-5 shadow-[8px_8px_0_0_#000] hover:bg-rose-100 transition md:col-span-2">
    <div class="font-black text-black">Ajukan Izin / Sakit / Cuti</div>
    <div class="text-sm text-black/70 mt-2">Request akan masuk ke atasan untuk approval.</div>
  </a>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
