<?php
// news.php
include 'db_connect.php';

// هنا ممكن مستقبلاً تعتمد على نوع المستخدم (طالب/موظف) لتصفية الأخبار
// حالياً سنعرض كل الأخبار
$sql = "SELECT * FROM news ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الأخبار - الكلية التقنية بفرسان</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background:#f5f5f5; margin:0; padding:0; }
    header { background:#007bff; color:#fff; padding:15px; text-align:center; }
    .container { max-width:900px; margin:20px auto; padding:0 10px; }
    .news-item { background:#fff; margin-bottom:15px; padding:15px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.06); }
    .news-item h3 { margin-top:0; }
    .news-meta { font-size:12px; color:#666; margin-bottom:10px; }
    img.news-img { max-width:100%; border-radius:10px; margin-top:10px; }
  </style>
</head>
<body>

<header>
  <h1>📰 أخبار الكلية التقنية بفرسان</h1>
</header>

<div class="container">
  <?php if ($result && $result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
      <div class="news-item">
        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
        <div class="news-meta">
          <span>تاريخ النشر: <?php echo $row['created_at']; ?></span>
          <span> | الفئة: 
            <?php
              if ($row['audience'] === 'all') echo "الجميع";
              elseif ($row['audience'] === 'students') echo "المتدربون";
              else echo "منسوبو الكلية";
            ?>
          </span>
        </div>
        <p><?php echo nl2br(htmlspecialchars($row['body'])); ?></p>
        <?php if (!empty($row['image_path'])): ?>
          <img class="news-img" src="<?php echo $row['image_path']; ?>" alt="صورة الخبر">
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>لا توجد أخبار حالياً.</p>
  <?php endif; ?>

</div>

</body>
</html>
<?php
$conn->close();
?>
