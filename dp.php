<?php
$host = "localhost";   // اسم السيرفر المحلي (عادة لا تغيّره)
$user = "root";        // اسم المستخدم الافتراضي في XAMPP أو Laragon
$pass = "";            // كلمة المرور (غالبًا فارغة في XAMPP)
$db   = "project";     // ✅ اسم قاعدة البيانات الخاصة بك

// إنشاء الاتصال
$conn = new mysqli($host, $user, $pass, $db);

// التحقق من الاتصال
if ($conn->connect_error) {
    die(json_encode(["error" => "فشل الاتصال بقاعدة البيانات: " . $conn->connect_error], JSON_UNESCAPED_UNICODE));
}

// تعيين الترميز لدعم اللغة العربية بالكامل
if (!$conn->set_charset("utf8mb4")) {
    die(json_encode(["error" => "فشل في تعيين الترميز: " . $conn->error], JSON_UNESCAPED_UNICODE));
}

// ✅ جاهز للاستخدام
// مثال بسيط للتجربة:
# echo "تم الاتصال بقاعدة البيانات project بنجاح ✅";

?>
