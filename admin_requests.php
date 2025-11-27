<?php
// admin_requests.php
session_start();

// (اختياري) تأكد أن المستخدم له دور admin
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     die('غير مصرح لك بالدخول');
// }

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "project";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// طلبات في حالة pending
$sql = "SELECT id, full_name, academic_id, phone, in_datetime, out_datetime, supervisor_name, created_at
        FROM college_entries
        WHERE status = 'pending'
        ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>طلبات دخول الكلية - الإدارة</title>
  <style>
    body {
      font-family: "Tajawal", Arial, sans-serif;
      background-color: #f4f6f8;
      margin: 0;
      padding: 20px;
      text-align: right;
    }
    h1 {
      text-align: center;
      color: #0d47a1;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }
    th {
      background-color: #22bace;
      color: #fff;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    form.inline {
      display: inline;
    }
    button {
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-family: inherit;
    }
    .approve {
      background-color: #4caf50;
      color: #fff;
    }
    .reject {
      background-color: #f44336;
      color: #fff;
    }
    .back-btn {
      display: inline-block;
      margin-top: 20px;
      padding: 8px 14px;
      background: #22bace;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
    }
  </style>
</head>
<body>

<h1>طلبات دخول الكلية (في انتظار الموافقة)</h1>

<?php if ($result && $result->num_rows > 0): ?>
<table>
  <tr>
    <th>الاسم</th>
    <th>الرقم الأكاديمي</th>
    <th>الجوال</th>
    <th>دخول</th>
    <th>خروج</th>
    <th>المشرف</th>
    <th>الإجراءات</th>
  </tr>
  <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['full_name']) ?></td>
      <td><?= htmlspecialchars($row['academic_id']) ?></td>
      <td><?= htmlspecialchars($row['phone']) ?></td>
      <td><?= htmlspecialchars($row['in_datetime']) ?></td>
      <td><?= htmlspecialchars($row['out_datetime']) ?></td>
      <td><?= htmlspecialchars($row['supervisor_name']) ?></td>
      <td>
        <form class="inline" action="process_request.php" method="post">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <input type="hidden" name="action" value="approve">
          <button type="submit" class="approve">موافقة</button>
        </form>

        <form class="inline" action="process_request.php" method="post">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <input type="hidden" name="action" value="reject">
          <button type="submit" class="reject">رفض</button>
        </form>
      </td>
    </tr>
  <?php endwhile; ?>
</table>
<?php else: ?>
  <p>لا توجد طلبات معلّقة حالياً.</p>
<?php endif; ?>

<a href="../HTML/admin_panel.html" class="back-btn">🔙 العودة للوحة التحكم</a>

</body>
</html>
<?php
$conn->close();
?>
