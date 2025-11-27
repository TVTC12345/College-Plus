<?php
// security_api.php
header('Content-Type: application/json; charset=utf-8');

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "project";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "فشل الاتصال بقاعدة البيانات"]);
    exit;
}

$action = $_GET['action'] ?? '';

// دالة لتحويل status لنص عربي (من جدول applicants)
function statusText($status) {
    switch ($status) {
        case 'waiting':
            return '⏳ بانتظار الدخول';
        case 'entered':
            return '✅ تم السماح بالدخول';
        case 'violated':
            return '⚠️ عليه مخالفة مسجّلة';
        default:
            return $status;
    }
}

/**
 * action = list
 * إرجاع جميع المتقدمين (من جدول applicants)
 */
if ($action === 'list') {
    $sql = "SELECT id, name, national_id, status 
            FROM applicants
            ORDER BY id DESC";
    $result = $conn->query($sql);

    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "id"          => (int)$row['id'],
                "name"        => $row['name'],
                // نرسله باسم academic_id عشان الكود في صفحة الحارس يستخدم هذا الاسم
                "academic_id" => $row['national_id'],
                "status"      => $row['status'],
                "status_text" => statusText($row['status']),
            ];
        }
    }
    echo json_encode($data);
    $conn->close();
    exit;
}

/**
 * action = allow (موجود لو احتجته لاحقاً)
 * تحديث حالة المتقدم إلى entered
 */
if ($action === 'allow' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(["success" => false, "message" => "لا يوجد رقم متقدم"]);
        exit;
    }

    $sql = "UPDATE applicants SET status = 'entered' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode([
        "success" => $ok,
        "message" => $ok ? "تم تسجيل السماح بالدخول" : "فشل في التحديث"
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

/**
 * action = violation
 * حفظ مخالفة على متقدم معيّن
 */
if ($action === 'violation' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id']   ?? null; // applicant_id
    $desc = $_POST['desc'] ?? '';

    if (!$id || trim($desc) === '') {
        echo json_encode(["success" => false, "message" => "بيانات غير مكتملة"]);
        exit;
    }

    // إدخال في جدول المخالفات
    $sql = "INSERT INTO violations (applicant_id, description)
            VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id, $desc);
    $ok = $stmt->execute();
    $stmt->close();

    // تحديث حالة المتقدم إلى violated
    $sql2 = "UPDATE applicants SET status = 'violated' WHERE id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode([
        "success" => $ok,
        "message" => $ok ? "تم حفظ المخالفة وتحديث حالة المتقدم" : "فشل في حفظ المخالفة"
    ]);

    $conn->close();
    exit;
}

/**
 * action = report
 * إرجاع تقرير بالمخالفات (ينعرض في صفحة الحارس + صفحة الإدارة)
 */
if ($action === 'report') {
    $sql = "SELECT 
                v.id,
                a.name,
                a.national_id,
                v.description,
                v.date
            FROM violations v
            JOIN applicants a ON v.applicant_id = a.id
            ORDER BY v.date DESC";
    $result = $conn->query($sql);

    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "id"          => (int)$row['id'],
                "name"        => $row['name'],
                "academic_id" => $row['national_id'],
                "description" => $row['description'],
                "date"        => $row['date'],
            ];
        }
    }
    echo json_encode($data);
    $conn->close();
    exit;
}

// لو action غير معروف
echo json_encode(["error" => "إجراء غير معروف"]);
$conn->close();
?>
