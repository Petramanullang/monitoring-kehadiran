<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Dashboard Admin HR", "Kelola master data, user, pegawai, jadwal, dan shift.") ?>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  <?php
    $cards = [
      ['Users', '/admin/users.php', 'Buat akun login'],
      ['Employees', '/admin/employees.php', 'Data pegawai + atasan'],
      ['Schedule', '/admin/schedules.php', 'Jadwal harian'],
      ['Shift', '/admin/shifts.php', 'Jam masuk/keluar'],
      ['Master', '/admin/master.php?t=departments', 'Departments, position, dll'],
    ];
    foreach ($cards as [$title,$href,$desc]):
  ?>
    <a href="<?= BASE_URL . $href ?>"
       class="bg-white border-2 border-black p-5 shadow-[8px_8px_0_0_#000] hover:bg-yellow-100 transition">
      <div class="font-black text-black"><?= htmlspecialchars($title) ?></div>
      <div class="text-sm text-black/70 mt-2"><?= htmlspecialchars($desc) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
