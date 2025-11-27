<?php
// بدء الجلسة
session_start();

// التحقق من أن المستخدم مدير (يمكنك إلغاء التعليق بعد الانتهاء من التطوير)
// if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
//     die("غير مسموح بالدخول");
// }

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "project");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// ضبط الترميز (مهم للعربي)
$conn->set_charset("utf8");

// معالجة الإرسال
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name       = $_POST['name'];
    $email      = $_POST['email'];
    $job_title  = $_POST['job_title'];
    $department = $_POST['department']; // من القائمة المنسدلة
    $sort_order = $_POST['sort_order'];

    // رفع الصورة
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        // مجلد حفظ الصور
        $uploadDir  = 'IMG/';

        // إنشاء المجلد إذا لم يكن موجوداً
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // اسم الصورة فقط
        $imageName  = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // نحفظ اسم الملف فقط في قاعدة البيانات
            $image_path = $imageName;
        }
    }

    $stmt = $conn->prepare("INSERT INTO org_structure 
        (name, email, job_title, department, image_path, sort_order) 
        VALUES (?,?,?,?,?,?)");

    $stmt->bind_param("sssssi", $name, $email, $job_title, $department, $image_path, $sort_order);
    $stmt->execute();

    echo "<script>alert('تمت الإضافة بنجاح ✔'); window.location.href='manage_members.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إضافة عضو في الهيكل الإداري</title>

<style>
    body {
        font-family: 'Cairo', sans-serif;
        background: #f3f6fa;
        margin: 0;
        padding: 0;
    }

    h2 {
        text-align: center;
        margin-top: 30px;
        color: #1f4e79;
        font-size: 28px;
    }

    .form-container {
        width: 420px;
        margin: 30px auto;
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.1);
        border-top: 5px solid #1f4e79;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: bold;
        color: #333;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"],
    input[type="file"],
    select {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 8px;
        border: 1px solid #bbb;
        font-size: 15px;
    }

    button {
        width: 100%;
        background: #1f4e79;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-size: 17px;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 10px;
    }

    button:hover {
        background: #163d60;
    }

    .back-link {
        text-align: center;
        margin-top: 15px;
    }

    .back-link a {
        color: #1f4e79;
        text-decoration: none;
        font-size: 15px;
        font-weight: bold;
    }

    .back-link a:hover {
        text-decoration: underline;
    }

    .departments-links {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
    }

    .departments-links a {
        margin: 0 10px;
        color: #1f4e79;
        text-decoration: none;
    }

    .departments-links a:hover {
        text-decoration: underline;
    }
</style>
</head>

<body>

<h2>➕ إضافة عضو في الهيكل الإداري</h2>

<div class="form-container">
    <form action="" method="post" enctype="multipart/form-data">

        <label>الاسم:</label>
        <input type="text" name="name" required>

        <label>البريد الإلكتروني:</label>
        <input type="email" name="email">

        <label>المسمى الوظيفي:</label>
        <input type="text" name="job_title" required>

        <label>القسم (اختياري):</label>
        <select name="department">
            <option value="">بدون قسم</option>

            <!-- الأقسام الأكاديمية مع المدربين -->
            <option value="مدرب - قسم الحاسب وتقنية المعلومات">مدرب - قسم الحاسب وتقنية المعلومات</option>
            <option value="مدرب - قسم التقنية الإدارية والمالية">مدرب - قسم التقنية الإدارية والمالية</option>

            <!-- المناصب العليا -->
            <option value="العميد">العميد</option>
            <option value="الإدارة">الإدارة</option>
            <option value="الوكيل">الوكيل</option>

            <!-- رؤساء الأقسام -->
            <option value="رئيس قسم الحاسب وتقنية المعلومات">رئيس قسم الحاسب وتقنية المعلومات</option>
            <option value="رئيس قسم التقنية الإدارية والمالية">رئيس قسم التقنية الإدارية والمالية</option>
        </select>

        <label>ترتيب الظهور:</label>
        <input type="number" name="sort_order" value="0">

        <label>الصورة:</label>
        <input type="file" name="image" accept="image/*">

        <button type="submit">💾 حفظ البيانات</button>

    </form>

    <div class="back-link">
        <a href="manage_members.php">⬅ العودة إلى قائمة الأعضاء</a>
    </div>
</div>

</body>
</html>
