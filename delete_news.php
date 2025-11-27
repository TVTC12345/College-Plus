<?php
include "dp.php";

$id = $_GET['id'];

// جلب الصورة لحذفها من الملفات
$sql = "SELECT image_path FROM news WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$image = $stmt->get_result()->fetch_assoc()['image_path'];

// حذف الخبر
$sql2 = "DELETE FROM news WHERE id=?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $id);

if ($stmt2->execute()) {

    // حذف الصورة من السيرفر
    if ($image && file_exists($image)) {
        unlink($image);
    }

    echo "<script>alert('🗑 تم حذف الخبر بنجاح'); window.location='manage_news.php';</script>";

} else {
    echo "<script>alert('❌ فشل حذف الخبر');</script>";
}
