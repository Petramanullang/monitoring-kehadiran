<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin_hr']);
require_once __DIR__ . '/../inc/flash.php';

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';

// konfigurasi entity master
$masters = [
  'departments' => [
    'title' => 'Departments',
    'table' => 'departments',
    'pk'    => 'id_department',
    'col'   => 'nama_department',
    'placeholder' => 'Nama department'
  ],
  'position' => [
    'title' => 'Position',
    'table' => 'position',
    'pk'    => 'id_position',
    'col'   => 'nama_position',
    'placeholder' => 'Nama position'
  ],
  'role' => [
    'title' => 'Role',
    'table' => 'role',
    'pk'    => 'id_role',
    'col'   => 'role_name',
    'placeholder' => 'Nama role'
  ],
  'status' => [
    'title' => 'Status',
    'table' => 'status',
    'pk'    => 'id_status',
    'col'   => 'status_name',
    'placeholder' => 'Nama status'
  ],
  'jenis' => [
    'title' => 'Jenis Izin',
    'table' => 'jenis',
    'pk'    => 'id_jenis',
    'col'   => 'jenis',
    'placeholder' => 'Contoh: IZIN / SAKIT / CUTI'
  ],
];

$t = $_GET['t'] ?? 'departments';
if (!isset($masters[$t])) $t = 'departments';

$cfg = $masters[$t];

// handle aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
      set_flash('danger', 'Nama tidak boleh kosong.');
      header("Location: master.php?t=$t"); exit;
    }

    try {
      $sql = "INSERT INTO `{$cfg['table']}` (`{$cfg['col']}`) VALUES (?)";
      $pdo->prepare($sql)->execute([$name]);
      set_flash('success', $cfg['title'].' ditambah.');
    } catch (Exception $e) {
      set_flash('danger', 'Gagal tambah: '.$e->getMessage());
    }
    header("Location: master.php?t=$t"); exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
      set_flash('danger', 'ID tidak valid.');
      header( "Location: master.php?t=$t"); exit;
    }

    try {
      $sql = "DELETE FROM `{$cfg['table']}` WHERE `{$cfg['pk']}` = ?";
      $pdo->prepare($sql)->execute([$id]);
      set_flash('success', $cfg['title'].' dihapus.');
    } catch (Exception $e) {
      set_flash('danger', 'Tidak bisa hapus: data masih dipakai (relasi).');
    }
    header("Location: master.php?t=$t"); exit;
  }
}

// fetch data
$sqlList = "SELECT `{$cfg['pk']}` AS id, `{$cfg['col']}` AS name FROM `{$cfg['table']}` ORDER BY id DESC";
$rows = $pdo->query($sqlList)->fetchAll();

echo ui_page_start("Master Data", "Kelola data dasar: departments, position, role, status, jenis izin.");
?>

<!-- Tabs -->
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

<?= ui_card_start($cfg['title'], ui_badge("TABLE: ".$cfg['table'], "neutral")) ?>
  <form method="post" class="grid md:grid-cols-3 gap-3 items-end">
    <input type="hidden" name="action" value="create">
    <div class="md:col-span-2">
      <label class="text-sm font-black text-black">Tambah <?= htmlspecialchars($cfg['title']) ?></label>
      <input name="name" class="<?= ui_input_class() ?>" placeholder="<?= htmlspecialchars($cfg['placeholder']) ?>" required>
    </div>
    <button class="<?= ui_btn_class('alt') ?>">Tambah</button>
  </form>
<?= ui_card_end() ?>

<div class="mt-5">
  <?php
    if (!$rows) {
      echo ui_empty("Belum ada data ".$cfg['title'].".", "Tambah data pertama dari form di atas.");
    } else {
      echo ui_table_start(['ID', 'Nama', 'Aksi']);
      foreach ($rows as $r) {
        echo ui_row_start();
        echo ui_td((int)$r['id'], true);
        echo ui_td("<div class='font-black'>".htmlspecialchars($r['name'])."</div>");
        $btn = "
          <form method='post' onsubmit=\"return confirm('Hapus item ini?')\">
            <input type='hidden' name='action' value='delete'>
            <input type='hidden' name='id' value='".(int)$r['id']."'>
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

<div class="mt-6 flex gap-2">
  <a class="<?= ui_btn_class('ghost') ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Kembali</a>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
