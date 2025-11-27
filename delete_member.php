<?php
// delete_member.php
session_start();
// if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { die("غير مسموح بالدخول"); }

require_once 'dp.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("معرّف غير صالح.");
}

$id = (int) $_GET['id'];

// (اختياري) جلب الصورة لحذفها من السيرفر
$stmt = $conn->prepare("SELECT image_path FROM org_structure WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if ($member) {
    // حذف السجل
    $stmt2 = $conn->prepare("DELETE FROM org_structure WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();

    // حذف الصورة من السيرفر (اختياري)
    if (!empty($member['image_path'])) {
        $filePath = '../' . $member['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

header("Location: manage_members.php");
exit;
