<?php
// save_entry.php

// إعداد الاتصال بقاعدة البيانات
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "project"; // عدّل لاسم قاعدة البيانات عندك

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// استلام البيانات من النموذج
$full_name     = $_POST['full_name']    ?? '';
$academic_id   = $_POST['academic_id']  ?? '';
$phone         = $_POST['phone']        ?? '';
$in_datetime   = $_POST['in_datetime']  ?? '';
$out_datetime  = $_POST['out_datetime'] ?? '';
$supervisor    = $_POST['supervisor']   ?? '';

// تحويل datetime-local إلى صيغة MySQL (YYYY-MM-DD HH:MM:SS)
function toMysqlDateTime($dt) {
    $dt = trim($dt);
    if ($dt === '') return null;
    // يأتي من HTML مثل: 2025-11-21T16:30
    $dt = str_replace('T', ' ', $dt);
    if (strlen($dt) === 16) {
        $dt .= ':00'; // نضيف الثواني
    }
    return $dt;
}

$in_datetime  = toMysqlDateTime($in_datetime);
$out_datetime = toMysqlDateTime($out_datetime);

// تحقق من أن الحقول ليست فارغة
if (
    empty($full_name) || empty($academic_id) || empty($phone) ||
    empty($in_datetime) || empty($out_datetime) || empty($supervisor)
) {
    echo "<script>alert('❌ يرجى تعبئة جميع الحقول'); window.history.back();</script>";
    exit;
}

// تحقق من الوقت في السيرفر (من 4 مساءً إلى قبل 11 مساءً = 10:59 مساءً)
$inHour  = (int)date('H', strtotime($in_datetime));
$outHour = (int)date('H', strtotime($out_datetime));
if ($inHour < 16 || $inHour >= 23 || $outHour < 16 || $outHour >= 23) {
    echo "<script>alert('⚠️ التسجيل مسموح من 4:00 مساءً إلى 10:59 مساءً فقط'); window.history.back();</script>";
    exit;
}

// (اختياري) التحقق أن وقت الخروج بعد وقت الدخول
if (strtotime($out_datetime) <= strtotime($in_datetime)) {
    echo "<script>alert('⚠️ وقت الخروج يجب أن يكون بعد وقت الدخول'); window.history.back();</script>";
    exit;
}

// إدخال الطلب بحالة pending
$sql = "INSERT INTO college_entries 
        (full_name, academic_id, phone, in_datetime, out_datetime, supervisor_name, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("خطأ في التحضير: " . $conn->error);
}

$stmt->bind_param(
    "ssssss",
    $full_name,
    $academic_id,
    $phone,
    $in_datetime,
    $out_datetime,
    $supervisor
);

if ($stmt->execute())
	{echo "<script>
        alert('✅ تم إرسال طلب الدخول، بانتظار موافقة الإدارة');
        window.location.href = '../HTML/college_request.html';
      </script>";

} else {
    $error = addslashes($stmt->error);
    echo "<script>
            alert('❌ حدث خطأ أثناء حفظ البيانات: {$error}');
            window.history.back();
          </script>";
}

$stmt->close();
$conn->close();
?>
