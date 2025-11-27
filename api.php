<?php
// ترويسات JSON + دعم العربية
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

// الاتصال بقاعدة البيانات
include "db.php";

$action = $_GET['action'] ?? '';

// 🟢 عرض قائمة المتقدمين (المنتظرين)
if ($action === 'list') {
    $res = $conn->query("SELECT * FROM applicants WHERE status='waiting'");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

// 🟡 تحديث الحالة إلى "تم الدخول"
elseif ($action === 'allow') {
    $id = intval($_POST['id'] ?? 0);
    $ok = $conn->query("UPDATE applicants SET status='entered' WHERE id=$id");
    echo json_encode(["ok" => $ok], JSON_UNESCAPED_UNICODE);
}

// 🔴 تسجيل مخالفة
elseif ($action === 'violation') {
    $id = intval($_POST['id'] ?? 0);
    $desc = $conn->real_escape_string($_POST['desc'] ?? '');
    
    // حفظ المخالفة
    $ok = $conn->query("INSERT INTO violations (applicant_id, description) VALUES ($id, '$desc')");
    
    // تحديث الحالة
    $conn->query("UPDATE applicants SET status='violated' WHERE id=$id");
    
    echo json_encode(["ok" => $ok], JSON_UNESCAPED_UNICODE);
}

// 📄 تقرير المخالفات
elseif ($action === 'report') {
    $sql = "SELECT a.name, a.national_id, v.description, v.date
            FROM violations v
            JOIN applicants a ON a.id = v.applicant_id
            ORDER BY v.date DESC";
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

// ⚠️ في حال تم استدعاؤه بدون action صحيح
else {
    echo json_encode(["error" => "إجراء غير معروف"], JSON_UNESCAPED_UNICODE);
}
?>
