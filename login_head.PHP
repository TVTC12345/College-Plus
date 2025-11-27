<?php
session_start(); // لبدء الجلسة

// إعداد الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "project";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// استقبال البيانات من النموذج
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// الدور المتوقع لهذا الملف
$expected_role = "head";

// التحقق من تعبئة الحقول
if (empty($username) || empty($password)) {
  die("<script>alert('⚠️ يرجى تعبئة جميع الحقول'); window.history.back();</script>");
}

// التحقق من المستخدم من جدول loginss
$sql  = "SELECT * FROM loginss WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

  $role = $row['role'];       // الدور الفعلي من قاعدة البيانات
  $dept = $row['dept'] ?? ''; // القسم إن وجد

  // منع الدخول إذا لم يكن الدور Head
  if ($role !== $expected_role) {
    echo "<script>
        alert('❌ لا يمكنك تسجيل الدخول من صفحة رئيس القسم، حسابك ليس رئيس قسم.');
        window.history.back();
    </script>";
    exit;
  }

  // تخزين بيانات المستخدم في الجلسة
  $_SESSION['username'] = $row['username'];
  $_SESSION['role']     = $role;
  $_SESSION['dept']     = $dept;

  // توجيه لوحة تحكم رئيس القسم
  header("Location: ../HTML/head_dashboard.html");
  exit;

} else {
  // في حال فشل تسجيل الدخول
  echo "<script>alert('❌ اسم المستخدم أو كلمة المرور غير صحيحة!'); window.history.back();</script>";
}

$conn->close();
?>
