<?php
header('Content-Type: application/json; charset=utf-8');

$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "project";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error]);
    exit;
}

// تأكد أن الطلب تم عبر POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "❌ يجب استخدام طريقة POST لرفع الملفات."]);
    exit;
}

// استلام البيانات
$title = $_POST['title'] ?? '';
$dept  = $_POST['dept'] ?? '';
$level = $_POST['level'] ?? '';

if (empty($title) || empty($dept) || empty($level) || empty($_FILES['file']['name'])) {
    echo json_encode(["error" => "⚠️ يرجى تعبئة جميع الحقول واختيار ملف."]);
    exit;
}

// مسار حفظ الملفات
$targetDir = "../uploads/";
if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

// توليد اسم فريد للملف
$fileName = uniqid() . "_" . basename($_FILES["file"]["name"]);
$targetFile = $targetDir . $fileName;

// رفع الملف
if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    $stmt = $conn->prepare("INSERT INTO courses (title, dept, level, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $dept, $level, $fileName);
    $stmt->execute();
    echo json_encode(["success" => "✅ تم رفع الملف بنجاح."]);
    $stmt->close();
} else {
    echo json_encode(["error" => "❌ فشل رفع الملف."]);
}

$conn->close();
?>
