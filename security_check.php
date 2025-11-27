<?php
// security_check.php
session_start();

// (اختياري) تأكد أن المستخدم حارس
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'security') { die('غير مصرح لك'); }

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "project";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

$successMsg = "";
$errorMsg   = "";

// ① معالجة إرسال المخالفة من الحارس
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_violation'])) {

    $entry_id    = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : 0;
    $academic_id = $_POST['academic_id'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if ($entry_id <= 0 || $description === '') {
        $errorMsg = "❌ يرجى كتابة وصف المخالفة.";
        $academic_id = $academic_id; // يبقى كما هو
    } else {

        // حفظ المخالفة في جدول violations
        $sqlV = "INSERT INTO violations (entry_id, description) VALUES (?, ?)";
        $stmtV = $conn->prepare($sqlV);
        $stmtV->bind_param("is", $entry_id, $description);
        $stmtV->execute();
        $stmtV->close();

        // تحديث حالة الطلب إلى مرفوض (اختياري حسب منطقك)
        $sqlU = "UPDATE college_entries SET status = 'rejected' WHERE id = ?";
        $stmtU = $conn->prepare($sqlU);
        $stmtU->bind_param("i", $entry_id);
        $stmtU->execute();
        $stmtU->close();

        $successMsg = "✅ تم تسجيل المخالفة وتحديث حالة الدخول إلى (مرفوض).";
    }

} 

// ② جلب بيانات الطالب حسب الرقم الأكاديمي
$academic_id = $_GET['academic_id'] ?? ($academic_id ?? '');
$entry = null;

if (!empty($academic_id)) {
    $sql = "SELECT id, full_name, academic_id, phone, in_datetime, out_datetime, supervisor_name,
                   status, decided_by, decided_at
            FROM college_entries
            WHERE academic_id = ?
            ORDER BY id DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $academic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $entry = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تحقق الحارس من حالة الدخول</title>
  <style>
    body {
      font-family: "Tajawal", Arial, sans-serif;
      background-color: #f4f6f8;
      margin: 0;
      padding: 20px;
      text-align: right;
    }
    h1 {
      text-align: center;
      color: #0d47a1;
    }
    form {
      text-align: center;
      margin-bottom: 20px;
    }
    input[type="text"] {
      padding: 8px;
      width: 200px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-family: inherit;
    }
    button {
      padding: 8px 14px;
      border-radius: 6px;
      border: none;
      background-color: #22bace;
      color: #fff;
      cursor: pointer;
      margin-right: 5px;
    }
    .card {
      max-width: 500px;
      margin: 0 auto;
      background: #fff;
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 15px;
    }
    .status {
      font-weight: bold;
      padding: 6px 10px;
      border-radius: 6px;
      display: inline-block;
      margin-top: 5px;
    }
    .approved {
      background-color: #c8e6c9;
      color: #2e7d32;
    }
    .rejected {
      background-color: #ffcdd2;
      color: #c62828;
    }
    .pending {
      background-color: #fff9c4;
      color: #f57f17;
    }
    .msg {
      max-width: 500px;
      margin: 10px auto;
      padding: 10px;
      border-radius: 8px;
      font-size: 14px;
    }
    .success {
      background-color: #e8f5e9;
      color: #2e7d32;
    }
    .error {
      background-color: #ffebee;
      color: #c62828;
    }
    textarea {
      width: 100%;
      min-height: 80px;
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-family: inherit;
      resize: vertical;
    }
  </style>
</head>
<body>

<h1>نظام التحقق للحارس</h1>

<form method="get" action="">
  <label>أدخل الرقم الأكاديمي:</label>
  <input type="text" name="academic_id" value="<?= htmlspecialchars($academic_id) ?>" required>
  <button type="submit">بحث</button>
</form>

<?php if ($successMsg): ?>
  <div class="msg success"><?= $successMsg ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
  <div class="msg error"><?= $errorMsg ?></div>
<?php endif; ?>

<?php if ($academic_id && !$entry): ?>
  <p style="text-align:center; color:#c62828;">لا يوجد طلب دخول لهذا الرقم.</p>
<?php endif; ?>

<?php if ($entry): ?>
  <div class="card">
    <p><b>الاسم:</b> <?= htmlspecialchars($entry['full_name']) ?></p>
    <p><b>الرقم الأكاديمي:</b> <?= htmlspecialchars($entry['academic_id']) ?></p>
    <p><b>الجوال:</b> <?= htmlspecialchars($entry['phone']) ?></p>
    <p><b>وقت الدخول:</b> <?= htmlspecialchars($entry['in_datetime']) ?></p>
    <p><b>وقت الخروج:</b> <?= htmlspecialchars($entry['out_datetime']) ?></p>
    <p><b>المشرف:</b> <?= htmlspecialchars($entry['supervisor_name']) ?></p>

    <?php
      $status = $entry['status'];
      $class  = $status;
      $labelMap = [
        'approved' => '✅ مسموح بالدخول',
        'rejected' => '❌ مرفوض الدخول',
        'pending'  => '⏳ بانتظار موافقة الإدارة'
      ];
      $label = isset($labelMap[$status]) ? $labelMap[$status] : $status;
    ?>
    <p>
      <span class="status <?= $class ?>"><?= $label ?></span>
    </p>

    <?php if (!empty($entry['decided_by'])): ?>
      <p><b>القرار بواسطة:</b> <?= htmlspecialchars($entry['decided_by']) ?></p>
      <p><b>تاريخ القرار:</b> <?= htmlspecialchars($entry['decided_at']) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($status === 'approved'): ?>
    <!-- نموذج إبلاغ مخالفة يظهر فقط إذا كان مسموح بالدخول -->
    <div class="card">
      <h3>إبلاغ مخالفة على هذا المتدرب</h3>
      <form method="post" action="">
        <input type="hidden" name="report_violation" value="1">
        <input type="hidden" name="entry_id" value="<?= (int)$entry['id'] ?>">
        <input type="hidden" name="academic_id" value="<?= htmlspecialchars($entry['academic_id']) ?>">
        <label for="desc">تفاصيل المخالفة:</label><br>
        <textarea id="desc" name="description" required placeholder="اكتب تفاصيل المخالفة هنا..."></textarea><br><br>
        <button type="submit">إبلاغ مخالفة</button>
      </form>
    </div>
  <?php endif; ?>

<?php endif; ?>

</body>
</html>
