<?php
require_once __DIR__ . '/../inc/auth.php';
require_role(['atasan']);
require_once __DIR__ . '/../inc/flash.php';

$approver_employee_id = $_SESSION['user']['id_employee'];
if (!$approver_employee_id) die("Akun atasan belum terhubung ke employee.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id_leave = (int)($_POST['id_leave'] ?? 0);

  if (($action === 'approve' || $action === 'reject') && $id_leave) {
    $decision = $action === 'approve' ? 'APPROVED' : 'REJECTED';

    $stmt = $pdo->prepare("
      SELECT lr.*, e.atasan_id, j.jenis
      FROM leave_request lr
      JOIN employee e ON e.id_employee = lr.id_employee
      JOIN jenis j ON j.id_jenis = lr.id_jenis
      WHERE lr.id_leave = ?
      LIMIT 1
    ");
    $stmt->execute([$id_leave]);
    $lr = $stmt->fetch();

    if (!$lr || (int)$lr['atasan_id'] !== (int)$approver_employee_id) {
      set_flash('danger','Bukan request bawahan kamu.');
      header("Location: leave_approvals.php"); exit;
    }

    $chk = $pdo->prepare("SELECT id_leave FROM leave_aproval WHERE id_leave=?");
    $chk->execute([$id_leave]);
    $exist = $chk->fetch();

    if ($exist) {
      $pdo->prepare("UPDATE leave_aproval SET approved_by=?, keputusan=?, approved_at=NOW() WHERE id_leave=?")
          ->execute([$approver_employee_id, $decision, $id_leave]);
    } else {
      $pdo->prepare("INSERT INTO leave_aproval (id_leave, approved_by, keputusan) VALUES (?,?,?)")
          ->execute([$id_leave, $approver_employee_id, $decision]);
    }

    // kalau approved: isi attendance.note sesuai jenis
    if ($decision === 'APPROVED') {
      $statusMap = ['IZIN'=>'IZIN','SAKIT'=>'SAKIT','CUTI'=>'CUTI'];
      $status = $statusMap[$lr['jenis']] ?? 'IZIN';

      $start = new DateTime($lr['tanggal_mulai']);
      $end = new DateTime($lr['tanggal_berakhir']);
      $end->modify('+1 day');
      $period = new DatePeriod($start, new DateInterval('P1D'), $end);

      foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');

        $c = $pdo->prepare("SELECT id_attendance FROM attendance WHERE id_employee=? AND tanggal=? LIMIT 1");
        $c->execute([(int)$lr['id_employee'], $d]);
        $a = $c->fetch();

        if ($a) {
          $pdo->prepare("UPDATE attendance SET check_in=NULL, check_out=NULL, note=? WHERE id_attendance=?")
              ->execute([$status, (int)$a['id_attendance']]);
        } else {
          $pdo->prepare("INSERT INTO attendance (id_employee, tanggal, check_in, check_out, note) VALUES (?,?,NULL,NULL,?)")
              ->execute([(int)$lr['id_employee'], $d, $status]);
        }
      }
    }

    set_flash('success', "Request #$id_leave: $decision");
    header("Location: leave_approvals.php"); exit;
  }
}

$stmt = $pdo->prepare("
  SELECT lr.id_leave, lr.tanggal_mulai, lr.tanggal_berakhir, lr.alasan,
         j.jenis,
         e.nama AS nama_pegawai,
         ap.keputusan, ap.approved_at
  FROM leave_request lr
  JOIN employee e ON e.id_employee = lr.id_employee
  JOIN jenis j ON j.id_jenis = lr.id_jenis
  LEFT JOIN leave_aproval ap ON ap.id_leave = lr.id_leave
  WHERE e.atasan_id = ?
  ORDER BY lr.id_leave DESC
");
$stmt->execute([$approver_employee_id]);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/ui.php';
?>

<?= ui_page_start("Approval Pengajuan", "Approve / reject pengajuan dari bawahan.") ?>

<?php
if (!$rows) {
  echo ui_empty("Belum ada pengajuan dari bawahan.", "Kalau sudah ada, akan muncul di sini.");
} else {
  echo ui_table_start(['ID','Pegawai','Jenis','Mulai','Berakhir','Alasan','Status','Aksi']);
  foreach($rows as $r){
    $status = $r['keputusan'] ?? 'PENDING';
    $tone = (!$r['keputusan']) ? 'warn' : (($r['keputusan']==='APPROVED')?'ok':'bad');
    $badge = ui_badge($status, $tone);

    echo ui_row_start();
    echo ui_td((int)$r['id_leave'], true);
    echo ui_td("<div class='font-black'>".htmlspecialchars($r['nama_pegawai'])."</div>");
    echo ui_td(htmlspecialchars($r['jenis']), true);
    echo ui_td(htmlspecialchars($r['tanggal_mulai']), true);
    echo ui_td(htmlspecialchars($r['tanggal_berakhir']), true);
    echo ui_td(htmlspecialchars($r['alasan']));

    $extra = $r['approved_at'] ? "<div class='text-xs text-black/70 mt-1'>".$r['approved_at']."</div>" : "";
    echo ui_td($badge.$extra, true);

    $disabled = !empty($r['keputusan']) ? "disabled" : "";
    $act = "
      <div class='flex flex-wrap gap-2'>
        <form method='post'>
          <input type='hidden' name='id_leave' value='".(int)$r['id_leave']."'>
          <input type='hidden' name='action' value='approve'>
          <button class='".ui_btn_class('alt')."' $disabled>Approve</button>
        </form>
        <form method='post'>
          <input type='hidden' name='id_leave' value='".(int)$r['id_leave']."'>
          <input type='hidden' name='action' value='reject'>
          <button class='".ui_btn_class('danger')."' $disabled>Reject</button>
        </form>
      </div>
    ";
    echo ui_td($act, true);

    echo ui_row_end();
  }
  echo ui_table_end();
}
?>

<div class="mt-6">
  <a class="<?= ui_btn_class('ghost') ?>" href="<?= BASE_URL ?>/manager/dashboard.php">Kembali</a>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
