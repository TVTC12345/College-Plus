<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "project";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("<script>alert('❌ فشل الاتصال بقاعدة البيانات.'); window.history.back();</script>");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<script>alert('⚠️ يجب استخدام طريقة POST لرفع الملفات.'); window.history.back();</script>");
}

$title = $_POST['title'] ?? '';
$dept  = $_POST['dept'] ?? '';
$level = $_POST['level'] ?? '';
$desc  = $_POST['description'] ?? '';

if (empty($title) || empty($dept) || empty($level) || empty($_FILES['file']['name'])) {
    die("<script>alert('⚠️ يرجى تعبئة جميع الحقول واختيار ملف.'); window.history.back();</script>");
}

$targetDir = "../uploads/";
if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

$fileName = uniqid() . "_" . basename($_FILES["file"]["name"]);
$targetFile = $targetDir . $fileName;

// ✅ السماح فقط بـ PDF و Word
$allowed = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
if (!in_array($_FILES['file']['type'], $allowed)) {
    die("<script>alert('❌ يُسمح فقط بملفات PDF أو Word.'); window.history.back();</script>");
}

if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    $stmt = $conn->prepare("INSERT INTO courses (title, description, dept, level, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $desc, $dept, $level, $fileName);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('✅ تم إضافة المنهج بنجاح للقسم: $dept'); window.location.href='../HTML/add_course.html';</script>";
} else {
    echo "<script>alert('❌ فشل رفع الملف.'); window.history.back();</script>";
}

$conn->close();
?>
