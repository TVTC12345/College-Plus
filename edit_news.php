<?php
include "dp.php";

$id = $_GET['id'];

$sql  = "SELECT * FROM news WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$news = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل خبر</title>

<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
form { max-width:700px; margin:auto; background:white; padding:20px; border-radius:10px; }
input, textarea, select { width:100%; padding:10px; margin-bottom:10px; }
button { padding:10px 20px; background:#28a745; color:white; border:none; border-radius:6px; cursor:pointer; }
</style>

</head>
<body>

<h2 style="text-align:center;">✏️ تعديل الخبر</h2>

<form action="update_news.php" method="POST" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?= $news['id']; ?>">

<label>عنوان الخبر</label>
<input type="text" name="title" value="<?= htmlspecialchars($news['title']); ?>" required>

<label>نص الخبر</label>
<textarea name="body" required><?= htmlspecialchars($news['body']); ?></textarea>

<label>الفئة المستهدفة</label>
<select name="audience">
  <option value="all"       <?= $news['audience']=="all"?"selected":""; ?>>الجميع</option>
  <option value="students"  <?= $news['audience']=="students"?"selected":""; ?>>المتدربون</option>
  <option value="staff"     <?= $news['audience']=="staff"?"selected":""; ?>>المنسوبون</option>
</select>

<label>الصورة الحالية</label><br>
<?php if ($news['image_path']): ?>
<img src="<?= $news['image_path']; ?>" width="120">
<?php else: ?>
<p>لا توجد صورة</p>
<?php endif; ?>

<br><br>
<label>تغيير الصورة (اختياري)</label>
<input type="file" name="new_image">

<button type="submit">💾 حفظ التعديلات</button>

</form>

</body>
</html>
