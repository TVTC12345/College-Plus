<?php
session_start();

$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "project";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
  die("❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// ✅ جلب القسم من الجلسة
$dept = $_SESSION['dept'] ?? '';
if (empty($dept)) {
  die("<script>alert('⚠️ لم يتم تحديد القسم.'); window.location.href='../HTML/login.html';</script>");
}

/* 🗑️ حذف منهج */
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);

  $fileQuery = $conn->prepare("SELECT file_path FROM courses WHERE id = ? AND dept = ?");
  $fileQuery->bind_param("is", $id, $dept);
  $fileQuery->execute();
  $fileResult = $fileQuery->get_result();
  if ($file = $fileResult->fetch_assoc()) {
    $filePath = "../uploads/" . $file['file_path'];
    if (file_exists($filePath)) unlink($filePath);
  }
  $fileQuery->close();

  $delete = $conn->prepare("DELETE FROM courses WHERE id = ? AND dept = ?");
  $delete->bind_param("is", $id, $dept);
  $delete->execute();

  echo "<script>alert('🗑️ تم حذف المنهج بنجاح'); window.location.href='head_view_courses.php';</script>";
  exit;
}

/* ✏️ تعديل منهج */
if (isset($_POST['update_course'])) {
  $id = intval($_POST['id']);
  $title = $_POST['title'];
  $level = $_POST['level'];

  if (!empty($_FILES['file']['name'])) {
    $targetDir = "../uploads/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    $allowedTypes = ['application/pdf'];
    if (!in_array($_FILES['file']['type'], $allowedTypes)) {
      echo "<script>alert('❌ يُسمح فقط بملفات PDF.'); window.history.back();</script>";
      exit;
    }

    $fileName = uniqid() . "_" . basename($_FILES["file"]["name"]);
    $targetFile = $targetDir . $fileName;
    move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile);

    // حذف الملف القديم
    $oldFile = $conn->prepare("SELECT file_path FROM courses WHERE id = ? AND dept = ?");
    $oldFile->bind_param("is", $id, $dept);
    $oldFile->execute();
    $oldResult = $oldFile->get_result();
    if ($old = $oldResult->fetch_assoc()) {
      $oldPath = "../uploads/" . $old['file_path'];
      if (file_exists($oldPath)) unlink($oldPath);
    }
    $oldFile->close();

    $update = $conn->prepare("UPDATE courses SET title=?, level=?, file_path=? WHERE id=? AND dept=?");
    $update->bind_param("sssis", $title, $level, $fileName, $id, $dept);
  } else {
    $update = $conn->prepare("UPDATE courses SET title=?, level=? WHERE id=? AND dept=?");
    $update->bind_param("ssis", $title, $level, $id, $dept);
  }

  $update->execute();
  echo "<script>alert('✅ تم تحديث بيانات المنهج بنجاح'); window.location.href='head_view_courses.php';</script>";
  exit;
}

/* ✅ جلب المناهج الخاصة بالقسم فقط */
$result = $conn->prepare("SELECT * FROM courses WHERE dept = ? ORDER BY id DESC");
$result->bind_param("s", $dept);
$result->execute();
$data = $result->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>📘 مناهج قسم <?= htmlspecialchars($dept) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: "Tajawal", sans-serif;
      background: linear-gradient(135deg, #ff9800, #ffc107);
      color: white;
      text-align: center;
      margin: 0;
      padding: 0;
      min-height: 100vh;
    }
    h1 { padding: 25px 0; font-size: 30px; }
    table {
      width: 90%;
      margin: 20px auto;
      border-collapse: collapse;
      background: rgba(255,255,255,0.1);
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
    th, td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.2); }
    th { background: rgba(255,255,255,0.2); font-size: 18px; }
    tr:hover { background: rgba(255,255,255,0.15); }
    a.btn {
      text-decoration: none;
      padding: 8px 15px;
      border-radius: 8px;
      font-weight: bold;
      transition: 0.3s;
      margin: 2px;
      display: inline-block;
    }
    .view { background: #22bace; color: white; }
    .download { background: #4caf50; color: white; }
    .edit { background: #ffc107; color: black; }
    .delete { background: #e53935; color: white; }
    .back {
      display: inline-block;
      margin: 25px 0;
      background: white;
      color: #ff9800;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
    }
    form {
      background: rgba(0,0,0,0.3);
      padding: 20px;
      border-radius: 12px;
      width: 70%;
      margin: 20px auto;
      display: none;
    }
    input, select { padding: 8px; border-radius: 6px; border: none; margin: 5px; width: 200px; }
  </style>
</head>
<body>

  <h1>📘 مناهج قسم <?= htmlspecialchars($dept) ?></h1>

  <table>
    <tr>
      <th>رقم</th>
      <th>اسم المنهج</th>
      <th>المستوى</th>
      <th>الملف</th>
      <th>خيارات</th>
    </tr>

    <?php if ($data->num_rows > 0): ?>
      <?php while ($r = $data->fetch_assoc()): $path = "../uploads/" . $r['file_path']; ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= htmlspecialchars($r['level']) ?></td>
        <td>
          <a class="btn view" href="<?= $path ?>" target="_blank">👁️ عرض</a>
          <a class="btn download" href="<?= $path ?>" download>⬇️ تنزيل</a>
        </td>
        <td>
          <a class="btn edit" href="#" onclick="editCourse(<?= $r['id'] ?>,'<?= htmlspecialchars($r['title']) ?>','<?= htmlspecialchars($r['level']) ?>')">✏️ تعديل</a>
          <a class="btn delete" href="?delete=<?= $r['id'] ?>" onclick="return confirm('هل أنت متأكد من الحذف؟');">🗑️ حذف</a>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="5">لا توجد مناهج مضافة بعد لهذا القسم</td></tr>
    <?php endif; ?>
  </table>

  <form id="editForm" method="POST" enctype="multipart/form-data">
    <h3>✏️ تعديل بيانات المنهج</h3>
    <input type="hidden" name="id" id="course_id">
    <input type="text" name="title" id="course_title" placeholder="اسم المنهج" required>
    <select name="level" id="course_level" required>
      <option value="1">المستوى 1</option>
      <option value="2">المستوى 2</option>
      <option value="3">المستوى 3</option>
      <option value="4">المستوى 4</option>
    </select>
    <input type="file" name="file" accept=".pdf">
    <button type="submit" name="update_course">💾 حفظ التعديل</button>
  </form>

  <a href="../HTML/dashboard.html" class="back">🔙 العودة للوحة رئيس القسم</a>

  <script>
    function editCourse(id, title, level) {
      const form = document.getElementById("editForm");
      form.style.display = "block";
      document.getElementById("course_id").value = id;
      document.getElementById("course_title").value = title;
      document.getElementById("course_level").value = level;
      window.scrollTo({ top: form.offsetTop, behavior: 'smooth' });
    }
  </script>

</body>
</html>

<?php $conn->close(); ?>
