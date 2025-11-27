<?php
include "dp.php";

$id       = $_POST['id'];
$title    = $_POST['title'];
$body     = $_POST['body'];
$audience = $_POST['audience'];

$new_image = null;

// هل رفع صورة جديدة؟
if (!empty($_FILES['new_image']['name'])) {

    $uploadDir = "../uploads/news/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $tmp  = $_FILES['new_image']['tmp_name'];
    $name = uniqid("news_", true) . "." . pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);

    $dest = $uploadDir . $name;
    move_uploaded_file($tmp, $dest);

    $new_image = $dest;

    // تحديث مع الصورة
    $sql  = "UPDATE news SET title=?, body=?, audience=?, image_path=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $title, $body, $audience, $new_image, $id);

} else {

    // تحديث بدون تغيير الصورة
    $sql  = "UPDATE news SET title=?, body=?, audience=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $title, $body, $audience, $id);
}

if ($stmt->execute()) {
    echo "<script>alert('✔ تم تحديث الخبر بنجاح'); window.location='manage_news.php';</script>";
} else {
    echo "<script>alert('❌ حدث خطأ أثناء التحديث');</script>";
}
