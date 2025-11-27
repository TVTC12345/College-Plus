<?php
// الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// استعلام جلب الطلبات من الجدول
$sql = "SELECT * FROM students ORDER BY student_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>📋 عرض الطلبات</title>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f5f6f8;
  direction: rtl;
  text-align: center;
  margin: 0;
  padding: 20px;
}

h1 {
  color: #222;
}

table {
  width: 95%;
  margin: 20px auto;
  border-collapse: collapse;
  background: white;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

th, td {
  padding: 12px;
  border: 1px solid #ddd;
  text-align: center;
}

th {
  background-color: #22baced7;
  color: white;
}

tr:nth-child(even) {
  background-color: #f9f9f9;
}

.btn {
  background-color: #22baced7;
  color: white;
  padding: 6px 12px;
  border-radius: 5px;
  text-decoration: none;
  border: none;
  cursor: pointer;
}
.btn:hover {
  background-color: #1a97a8;
}
.back-btn {
  display: inline-block;
  margin-top: 20px;
  background-color: #333;
  color: white;
  padding: 10px 15px;
  border-radius: 5px;
  text-decoration: none;
}
.back-btn:hover {
  background-color: #000;
}
</style>
</head>
<body>

<h1>📋 قائمة الطلبات</h1>

<?php
if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr>
            <th>الرقم</th>
            <th>الاسم</th>
            <th>رقم الجوال</th>
            <th>البريد الإلكتروني</th>
            <th>الرقم التدريبي</th>
            <th>نوع الخدمة</th>
            <th>القسم</th>
            <th>ملاحظات</th>
            <th>إجراء</th>
          </tr>";

    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['student_id']}</td>
                <td>{$row['student_name']}</td>
                <td>{$row['student_phone']}</td>
                <td>{$row['student_email']}</td>
                <td>{$row['student_id']}</td>
                <td>{$row['student_servies']}</td>
                <td>{$row['student_dep']}</td>
                <td>{$row['student_comment']}</td>
                <td>
                    <a href=\"admin_chat_student.php?student_id={$row['student_id']}\" class=\"btn\">مراسلة</a>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>🚫 لا توجد طلبات حالياً.</p>";
}

$conn->close();
?>

<a href="../HTML/applicants.html" class="back-btn">🔙 العودة</a>

</body>
</html>
