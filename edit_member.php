<?php
// edit_member.php
session_start();
// if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { die("غير مسموح بالدخول"); }

require_once 'dp.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("معرّف غير صالح.");
}

$id = (int) $_GET['id'];
$message = "";

// جلب بيانات العضو
$stmt = $conn->prepare("SELECT * FROM org_structure WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if (!$member) {
    die("العضو غير موجود.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name       = $_POST['name'] ?? '';
    $email      = $_POST['email'] ?? '';
    $job_title  = $_POST['job_title'] ?? '';
    $department = $_POST['department'] ?? '';
    $sort_order = $_POST['sort_order'] ?? 0;
    $old_image  = $_POST['old_image'] ?? null;

    $image_path = $old_image;

    // لو تم رفع صورة جديدة
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_path = 'uploads/' . $imageName;
        }
    }

    $stmt2 = $conn->prepare("UPDATE org_structure SET name=?, email=?, job_title=?, department=?, image_path=?, sort_order=? WHERE id=?");
    $stmt2->bind_param("ssssssi", $name, $email, $job_title, $department, $image_path, $sort_order, $id);

    if ($stmt2->execute()) {
        header("Location: manage_members.php");
        exit;
    } else {
        $message = "حدث خطأ أثناء التعديل.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل عضو</title>
</head>
<body>

<h2>تعديل عضو في الهيكل الإداري</h2>

<?php if (!empty($message)) echo "<p>$message</p>"; ?>

<form action="" method="post" enctype="multipart/form-data">
    <label>الاسم:</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($member['name']) ?>" required><br><br>

    <label>البريد الإلكتروني:</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($member['email']) ?>"><br><br>

    <label>المسمى الوظيفي:</label><br>
    <input type="text" name="job_title" value="<?= htmlspecialchars($member['job_title']) ?>" required><br><br>

    <label>القسم (اختياري):</label><br>
    <input type="text" name="department" value="<?= htmlspecialchars($member['department']) ?>"><br><br>

    <label>ترتيب الظهور (رقم):</label><br>
    <input type="number" name="sort_order" value="<?= htmlspecialchars($member['sort_order']) ?>"><br><br>

    <label>الصورة الحالية:</label><br>
    <?php if (!empty($member['image_path'])): ?>
        <img src="../<?= htmlspecialchars($member['image_path']) ?>" alt="" width="80"><br>
    <?php else: ?>
        لا توجد صورة<br>
    <?php endif; ?>
    <input type="hidden" name="old_image" value="<?= htmlspecialchars($member['image_path']) ?>">

    <label>تغيير الصورة (اختياري):</label><br>
    <input type="file" name="image" accept="image/*"><br><br>

    <button type="submit">حفظ التعديلات</button>
    <a href="manage_members.php">إلغاء</a>
</form>

</body>
</html>
