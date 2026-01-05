<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/flash.php';

$user = $_SESSION['user'] ?? null;
$flash = get_flash();

function nb_nav($href, $label){
  $active = (strpos($_SERVER['REQUEST_URI'], $href) !== false);
  $cls = $active ? "bg-yellow-300" : "bg-white hover:bg-yellow-200";
  return "<a class='$cls border-2 border-black px-3 py-2 font-black shadow-[4px_4px_0_0_#000] transition' href='".BASE_URL.$href."'>$label</a>";
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Monitoring Kehadiran</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[#f4f1ea]">
  <header class="border-b-2 border-black bg-white shadow-[0_6px_0_0_#000] mb-20">
    <div class="mx-auto max-w-6xl px-4 py-4 flex items-center justify-between gap-3">
      <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3">
        <div class="h-10 w-10 bg-cyan-300 border-2 border-black shadow-[6px_6px_0_0_#000]"></div>
        <div>
          <div class="font-black text-black leading-tight">Monitoring Kehadiran Pegawai</div>
        </div>
      </a>

      <nav class="hidden md:flex items-center gap-2">
        <?php if ($user): ?>
          <?php if ($user['role_name'] === 'admin_hr'): ?>
            <?= nb_nav('/admin/dashboard.php','Dashboard') ?>
            <?= nb_nav('/admin/users.php','Users') ?>
            <?= nb_nav('/admin/employees.php','Employees') ?>
            <?= nb_nav('/admin/schedules.php','Schedule') ?>
            <?= nb_nav('/admin/shifts.php','Shift') ?>
            <?= nb_nav('/admin/master.php?t=departments','Master') ?>
          <?php elseif ($user['role_name'] === 'pegawai'): ?>
            <?= nb_nav('/employee/dashboard.php','Dashboard') ?>
            <?= nb_nav('/employee/attendance_today.php','Absensi') ?>
            <?= nb_nav('/employee/attendance_history.php','Riwayat') ?>
            <?= nb_nav('/employee/leave_request.php','Izin') ?>
          <?php else: ?>
            <?= nb_nav('/manager/dashboard.php','Dashboard') ?>
            <?= nb_nav('/manager/leave_approvals.php','Approval') ?>
          <?php endif; ?>
        <?php endif; ?>
      </nav>

      <div class="flex items-center gap-2">
        <?php if ($user): ?>
          <div class="hidden sm:block text-right">
            <div class="font-black text-black"><?= htmlspecialchars($user['username']) ?></div>
            <div class="text-xs text-black/70"><?= htmlspecialchars($user['role_name']) ?></div>
          </div>
          <a href="<?= BASE_URL ?>/auth/logout.php"
             class="bg-rose-300 border-2 border-black px-3 py-2 font-black shadow-[4px_4px_0_0_#000] hover:bg-rose-200 transition">
            Logout
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/auth/login.php"
             class="bg-yellow-300 border-2 border-black px-3 py-2 font-black shadow-[4px_4px_0_0_#000] hover:bg-yellow-200 transition">
            Login
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-6xl px-4 py-6">
    <?php if ($flash): ?>
      <?php
        $type = $flash['type'] ?? 'info';
        $tone = ($type==='success') ? 'emerald' : (($type==='danger') ? 'rose' : (($type==='warning') ? 'yellow' : 'cyan'));
        $bg = match($tone){
          'emerald' => 'bg-emerald-200',
          'rose'    => 'bg-rose-200',
          'yellow'  => 'bg-yellow-200',
          default   => 'bg-cyan-200',
        };
      ?>
      <div class="<?= $bg ?> border-2 border-black px-4 py-3 font-black shadow-[6px_6px_0_0_#000] mb-5">
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>
