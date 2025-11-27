<?php
// create_vote.php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_vote'])) {

    $question      = trim($_POST['question']);
    $vote_audience = $_POST['vote_audience'];

    // نجمع الخيارات (مع تجاهل الفارغة)
    $options = [];
    if (!empty($_POST['option1'])) $options[] = trim($_POST['option1']);
    if (!empty($_POST['option2'])) $options[] = trim($_POST['option2']);
    if (!empty($_POST['option3'])) $options[] = trim($_POST['option3']);
    if (!empty($_POST['option4'])) $options[] = trim($_POST['option4']);

    if (count($options) < 2) {
        $message = "يجب إدخال خيارين على الأقل.";
    } else {
        // إدخال التصويت في جدول votes
        $stmt = $conn->prepare("INSERT INTO votes (question, audience, is_active) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $question, $vote_audience);

        if ($stmt->execute()) {
            $vote_id = $stmt->insert_id;
            $stmt->close();

            // إدخال الخيارات في جدول vote_options
            $optStmt = $conn->prepare("INSERT INTO vote_options (vote_id, option_text) VALUES (?, ?)");
            foreach ($options as $opt) {
                $optStmt->bind_param("is", $vote_id, $opt);
                $optStmt->execute();
            }
            $optStmt->close();

            $message = "✅ تم إنشاء التصويت بنجاح.";
        } else {
            $message = "❌ حدث خطأ أثناء إنشاء التصويت: " . $conn->error;
        }
    }

    $conn->close();
} else {
    $message = "طلب غير صحيح.";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>نتيجة إنشاء التصويت</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background:#f5f5f5; text-align:center; padding-top:60px; }
    .box { background:#fff; margin:0 auto; max-width:500px; padding:25px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    a { display:inline-block; margin-top:15px; text-decoration:none; padding:8px 15px; border-radius:8px; background:#28a745; color:#fff; }
  </style>
</head>
<body>
  <div class="box">
    <h2>🗳️ إنشاء التصويت</h2>
    <p><?php echo $message; ?></p>
    <a href="../HTML/voting.html">⬅ رجوع لصفحة إنشاء التصويت</a>
    <a href="../HTML/AD.html">🏠 الرجوع للوحة التحكم</a>
  </div>
</body>
</html>
