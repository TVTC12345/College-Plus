<?php
// vote.php
include 'db_connect.php';

$message = "";

// عند إرسال التصويت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_option'])) {
    $option_id = (int) $_POST['vote_option'];

    // زيادة عدد الأصوات للخيار المختار
    $stmt = $conn->prepare("UPDATE vote_options SET votes_count = votes_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $option_id);

    if ($stmt->execute()) {
        $message = "✅ تم تسجيل صوتك بنجاح، شكراً لمشاركتك.";
    } else {
        $message = "❌ حدث خطأ أثناء تسجيل التصويت.";
    }
    $stmt->close();
}

// جلب آخر تصويت فعّال
$sqlVote = "SELECT * FROM votes WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1";
$voteResult = $conn->query($sqlVote);

$vote = null;
$optionsResult = null;

if ($voteResult && $voteResult->num_rows > 0) {
    $vote = $voteResult->fetch_assoc();
    $vote_id = $vote['id'];

    $sqlOptions = "SELECT * FROM vote_options WHERE vote_id = $vote_id";
    $optionsResult = $conn->query($sqlOptions);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>التصويت - الكلية التقنية بفرسان</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background:#f5f5f5; margin:0; padding:0; }
    header { background:#28a745; color:#fff; padding:15px; text-align:center; }
    .container { max-width:700px; margin:20px auto; background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    h2 { margin-top:0; }
    .message { margin-bottom:10px; color:#333; font-weight:600; }
    .options { list-style:none; padding:0; }
    .options li { margin-bottom:10px; }
    button { margin-top:10px; padding:8px 15px; border:none; border-radius:8px; background:#28a745; color:#fff; cursor:pointer; }
    .results { margin-top:20px; font-size:14px; color:#555; }
  </style>
</head>
<body>

<header>
  <h1>🗳️ التصويت</h1>
</header>

<div class="container">
  <?php if (!empty($message)): ?>
    <div class="message"><?php echo $message; ?></div>
  <?php endif; ?>

  <?php if ($vote): ?>
    <h2><?php echo htmlspecialchars($vote['question']); ?></h2>
    <form method="POST">
      <ul class="options">
        <?php if ($optionsResult && $optionsResult->num_rows > 0): ?>
          <?php while($opt = $optionsResult->fetch_assoc()): ?>
            <li>
              <label>
                <input type="radio" name="vote_option" value="<?php echo $opt['id']; ?>" required>
                <?php echo htmlspecialchars($opt['option_text']); ?>
              </label>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <li>لا توجد خيارات متاحة لهذا التصويت.</li>
        <?php endif; ?>
      </ul>
      <button type="submit">إرسال التصويت</button>
    </form>

    <!-- عرض النتائج بشكل بسيط -->
    <?php
      // إعادة جلب الخيارات مع الأصوات
      $sqlOptions2   = "SELECT * FROM vote_options WHERE vote_id = " . $vote['id'];
      $optionsResult2 = $conn->query($sqlOptions2);
      $totalVotes = 0;
      $optionsData = [];
      if ($optionsResult2 && $optionsResult2->num_rows > 0) {
          while($r = $optionsResult2->fetch_assoc()) {
              $optionsData[] = $r;
              $totalVotes += $r['votes_count'];
          }
      }
    ?>
    <div class="results">
      <h3>نتائج التصويت الحالية:</h3>
      <?php if ($totalVotes == 0): ?>
        <p>لم يتم التصويت بعد.</p>
      <?php else: ?>
        <ul>
          <?php foreach($optionsData as $r): 
            $percent = $totalVotes > 0 ? round(($r['votes_count'] / $totalVotes) * 100, 1) : 0;
          ?>
            <li>
              <?php echo htmlspecialchars($r['option_text']); ?> :
              <?php echo $r['votes_count']; ?> صوت (<?php echo $percent; ?>٪)
            </li>
          <?php endforeach; ?>
        </ul>
        <p>إجمالي الأصوات: <?php echo $totalVotes; ?></p>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <p>لا يوجد تصويت متاح حالياً.</p>
  <?php endif; ?>

</div>

</body>
</html>
<?php
$conn->close();
?>
