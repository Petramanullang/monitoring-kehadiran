<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/flash.php';

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';

$masters = [
  'departments' => [
    'title' => 'Departments',
    'table' => 'departments',
    'pk'    => 'id_department',
    'col'   => 'nama_department',
  ],
  'position' => [
    'title' => 'Position',
    'table' => 'position',
    'pk'    => 'id_position',
    'col'   => 'nama_position',
  ],
  'role' => [
    'title' => 'Role',
    'table' => 'role',
    'pk'    => 'id_role',
    'col'   => 'role_name',
  ],
  'status' => [
    'title' => 'Status',
    'table' => 'status',
    'pk'    => 'id_status',
    'col'   => 'status_name',
  ],
  'jenis' => [
    'title' => 'Jenis Izin',
    'table' => 'jenis',
    'pk'    => 'id_jenis',
    'col'   => 'jenis',
  ],
];

$t = $_GET['t'] ?? 'departments';
if (!isset($masters[$t])) $t = 'departments';
$cfg = $masters[$t];

$sqlList = "SELECT `{$cfg['pk']}` AS id, `{$cfg['col']}` AS name FROM `{$cfg['table']}` ORDER BY id DESC";
$rows = $pdo->query($sqlList)->fetchAll();

echo ui_page_start("Master Data", "Read-only (tanpa tambah/hapus).");
?>

<div class="flex flex-wrap gap-2 mb-5">
  <?php foreach ($masters as $key => $m): ?>
    <?php
      $active = ($key === $t);
      $cls = $active ? "bg-yellow-300" : "bg-white hover:bg-yellow-200";
    ?>
    <a href="<?= BASE_URL ?>/admin/master.php?t=<?= htmlspecialchars($key) ?>"
       class="<?= $cls ?> border-2 border-black px-3 py-2 font-black shadow-[4px_4px_0_0_#000] transition">
      <?= htmlspecialchars($m['title']) ?>
    </a>
  <?php endforeach; ?>
</div>


<div class="mt-5">
<?php
if (!$rows) {
  echo ui_empty("Belum ada data ".$cfg['title'].".", "Isi datanya lewat SQL/Import.");
} else {
  // ✅ tanpa ID, tanpa Aksi
  echo ui_table_start(['Nama']);
  foreach ($rows as $r) {
    echo ui_row_start();
    echo ui_td("<div class='font-black'>".htmlspecialchars($r['name'])."</div>");
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
