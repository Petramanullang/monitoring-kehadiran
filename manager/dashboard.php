<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['atasan']);
require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Dashboard Atasan", "Approve / reject pengajuan bawahan") ?>

<a href="<?= BASE_URL ?>/manager/leave_approvals.php"
   class="bg-white border-2 border-black p-5 shadow-[8px_8px_0_0_#000] hover:bg-yellow-100 transition block">
  <div class="font-black text-black">Approval Izin / Sakit / Cuti</div>
  <div class="text-sm text-black/70 mt-2">Kelola pengajuan dari bawahan kamu.</div>
</a>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
