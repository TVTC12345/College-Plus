<?php
// violations.php (admin_violations)

// إعداد الاتصال بقاعدة البيانات
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "project";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب المخالفات مع بيانات الطالب من جدول applicants
$sql = "SELECT 
          v.id,
          a.name,
          a.national_id,
          v.description,
          v.date
        FROM violations v
        JOIN applicants a ON v.applicant_id = a.id
        ORDER BY v.date DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>🚨 عرض المخالفات</title>
<link rel="stylesheet" href="../CSS/AD.css">
</head>
<body>

<header><h1>🚨 عرض المخالفات</h1></header>

<main class="admin-section">

  <?php if ($result && $result->num_rows > 0): ?>
    <table class="violations-table">
      <tr>
        <th>الاسم</th>
        <th>الهوية / الرقم الأكاديمي</th>
        <th>وصف المخالفة</th>
        <th>تاريخ التسجيل</th>
      </tr>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['national_id']) ?></td>
          <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
          <td><?= htmlspecialchars($row['date']) ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php else: ?>
    <p>لا توجد مخالفات مسجّلة حالياً.</p>
  <?php endif; ?>

  <a href="../HTML/admin_panel.html" class="back-btn">🔙 العودة للوحة التحكم</a>
</main>

</body>
</html>
<?php
$conn->close();
?>
