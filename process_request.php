<?php
// process_request.php
session_start();

// (اختياري) تأكد أن المستخدم مشرف
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     die('غير مصرح لك بالدخول');
// }

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "project";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$id     = $_POST['id']     ?? null;
$action = $_POST['action'] ?? null;

if (!$id || !$action) {
    echo "<script>alert('طلب غير صالح'); window.location.href='admin_requests.php';</script>";
    exit;
}

// تحديد الحالة الجديدة
$status = null;
if ($action === 'approve') {
    $status = 'approved';
} elseif ($action === 'reject') {
    $status = 'rejected';
} else {
    echo "<script>alert('إجراء غير معروف'); window.location.href='admin_requests.php';</script>";
    exit;
}

// تحديث حالة الطلب في جدول college_entries
$sql = "UPDATE college_entries SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $id);
$okUpdate = $stmt->execute();
$stmt->close();

if (!$okUpdate) {
    $err = addslashes($conn->error);
    echo "<script>alert('❌ حدث خطأ أثناء تحديث حالة الطلب: {$err}'); window.location.href='admin_requests.php';</script>";
    $conn->close();
    exit;
}

// لو تمت الموافقة → نسجل المتقدم في جدول applicants ليستفيد منه حارس الأمن
if ($status === 'approved') {
    // نجلب بيانات الطلب من college_entries
    $sqlGet = "SELECT full_name, academic_id FROM college_entries WHERE id = ?";
    $stmtGet = $conn->prepare($sqlGet);
    $stmtGet->bind_param("i", $id);
    $stmtGet->execute();
    $result = $stmtGet->get_result();
    $entry  = $result->fetch_assoc();
    $stmtGet->close();

    if ($entry) {
        $name        = $entry['full_name'];
        $national_id = $entry['academic_id']; // نستخدم الرقم الأكاديمي كمُعرّف في جدول applicants

        // إدخال المتقدم في جدول applicants بحالة waiting (ينتظر عند الحارس)
        $sqlIns = "INSERT INTO applicants (name, national_id, status)
                   VALUES (?, ?, 'waiting')";
        $stmtIns = $conn->prepare($sqlIns);
        $stmtIns->bind_param("ss", $name, $national_id);
        $stmtIns->execute();
        $stmtIns->close();
        // لو حاب تتجنب التكرار، لاحقاً نقدر نتحقق إذا كان national_id موجود قبل الإدخال
    }
}

// رسالة نجاح ورجوع لصفحة طلبات الإدارة
echo "<script>
        alert('✅ تم تحديث حالة الطلب بنجاح');
        window.location.href = 'admin_requests.php';
      </script>";

$conn->close();
?>
