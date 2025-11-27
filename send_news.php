<?php
// send_news.php
include 'dp.php';  // تأكد أن dp.php يحتوي على $conn

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_news'])) {

    $title    = trim($_POST['news_title']);
    $body     = trim($_POST['news_body']);
    $audience = $_POST['audience'];

    $image_path = null;

    // ================================
    // ✔ رفع الصورة (اختياري)
    // ================================
    if (!empty($_FILES['news_image']['name']) && $_FILES['news_image']['error'] === UPLOAD_ERR_OK) {
        
        $uploadDir  = '../uploads/news/'; 
        
        // إنشاء المجلد إذا لم يكن موجوداً
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tmpName = $_FILES['news_image']['tmp_name'];
        $originalName = $_FILES['news_image']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // السماح فقط بأنواع الصور
        $allowed_ext = ["jpg", "jpeg", "png", "gif"];
        if (!in_array($ext, $allowed_ext)) {
            $message = "❌ نوع الصورة غير مسموح. فقط JPG - PNG - GIF";
            goto end;
        }

        // إنشاء اسم فريد للصورة
        $newName = uniqid("news_", true) . "." . $ext;
        $final_path = $uploadDir . $newName;

        // نقل الصورة
        if (move_uploaded_file($tmpName, $final_path)) {
            $image_path = $final_path;
        } else {
            $message = "❌ فشل رفع الصورة.";
            goto end;
        }
    }

    // ================================
    // ✔ إدخال البيانات في قاعدة البيانات
    // ================================
    $stmt = $conn->prepare("
        INSERT INTO news (title, body, audience, image_path) 
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("ssss", $title, $body, $audience, $image_path);

    if ($stmt->execute()) {
        $message = "✔ تم إرسال الخبر بنجاح!";
    } else {
        $message = "❌ خطأ أثناء حفظ الخبر: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} else {
    $message = "❌ طلب غير صحيح.";
}

end:
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>نتيجة إرسال الخبر</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background:#f5f5f5;
      text-align:center; 
      padding-top:60px;
    }
    .box {
      background:#fff;
      margin:0 auto;
      max-width:500px;
      padding:25px;
      border-radius:12px;
      box-shadow:0 4px 10px rgba(0,0,0,0.1);
    }
    a {
      display:inline-block; 
      margin-top:15px;
      text-decoration:none;
      padding:8px 15px;
      border-radius:8px;
      background:#007bff;
      color:#fff;
    }
  </style>
</head>
<body>
  <div class="box">
    <h2>📰 إرسال الأخبار</h2>
    <p><?php echo $message; ?></p>
    <a href="../HTML/send_news.html">⬅ الرجوع لصفحة إرسال الأخبار</a>
    <a href="../HTML/admin_panel.html">🏠 الرجوع للوحة التحكم</a>
  </div>
</body>
</html>
