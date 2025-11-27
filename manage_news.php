<?php
include "dp.php";

$sql = "SELECT * FROM news ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة الأخبار</title>
<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
table { width: 100%; border-collapse: collapse; background:white; }
th, td { padding: 12px; border: 1px solid #ddd; text-align:center; }
th { background:#22bace; color:white; }
a.btn { padding:6px 12px; color:white; border-radius:6px; text-decoration:none; }
.edit { background:#28a745; }
.delete { background:#dc3545; }
</style>
</head>
<body>

<h2>📰 إدارة الأخبار</h2>

<table>
<tr>
  <th>العنوان</th>
  <th>النص</th>
  <th>الصورة</th>
  <th>تاريخ النشر</th>
  <th>تعديل</th>
  <th>حذف</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($row['title']); ?></td>
  <td><?= htmlspecialchars($row['body']); ?></td>
  <td>
    <?php if ($row['image_path']): ?>
      <img src="<?= $row['image_path']; ?>" width="70">
    <?php else: ?>
      —
    <?php endif; ?>
  </td>
  <td><?= $row['created_at']; ?></td>

  <td><a class="btn edit" href="edit_news.php?id=<?= $row['id']; ?>">تعديل</a></td>

  <td><a class="btn delete" href="delete_news.php?id=<?= $row['id']; ?>"
         onclick="return confirm('هل أنت متأكد من حذف هذا الخبر؟');">
         حذف
      </a>
  </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
