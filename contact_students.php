<?php
session_start();

// نتأكد أن رئيس القسم / الموظف مسجل دخول
if (!isset($_SESSION['user_id'])) {
    header("Location: /TVTC/PHP/login_head.php");
    exit;
}

$current_user_id = (int) $_SESSION['user_id'];

// اتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "project");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// 👈 الطالب الذي نعرض محادثته (حسب الرقم الأكاديمي في الرابط)
$selected_student = isset($_GET['student_number']) ? trim($_GET['student_number']) : null;

// إذا فيه رد جديد من رئيس القسم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['student_number'])) {
    $message = trim($_POST['message']);
    $student_number_post = trim($_POST['student_number']);

    if ($message !== '' && $student_number_post !== '') {
        // نخزن الرسالة مع:
        // sender_id = رقم رئيس القسم
        // receiver_id = رقم رئيس القسم (نفسه، لأنه صاحب المحادثة)
        // student_number = رقم الطالب
        $sql_insert = "INSERT INTO messages (sender_id, receiver_id, message, student_number)
                       VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiss", $current_user_id, $current_user_id, $message, $student_number_post);
        $stmt_insert->execute();
        $stmt_insert->close();

        // إعادة توجيه لنفس الصفحة لتجنب إعادة الإرسال
        header("Location: contact_students.php?student_number=" . urlencode($student_number_post));
        exit;
    }
}

// جلب قائمة الطلاب (الأرقام الأكاديمية) الذين أرسلوا رسائل لهذا الرئيس
$sql_students = "
    SELECT student_number, MAX(created_at) AS last_time
    FROM messages
    WHERE receiver_id = ? AND student_number IS NOT NULL
    GROUP BY student_number
    ORDER BY last_time DESC
";
$stmt_students = $conn->prepare($sql_students);
$stmt_students->bind_param("i", $current_user_id);
$stmt_students->execute();
$result_students = $stmt_students->get_result();

// جلب الرسائل مع الطالب المحدد (إن وجد)
$messages = [];
if ($selected_student !== null && $selected_student !== '') {
    $sql_messages = "
        SELECT * FROM messages
        WHERE receiver_id = ? AND student_number = ?
        ORDER BY created_at ASC
    ";
    $stmt_msg = $conn->prepare($sql_messages);
    $stmt_msg->bind_param("is", $current_user_id, $selected_student);
    $stmt_msg->execute();
    $messages = $stmt_msg->get_result();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التواصل مع المتدربين | لوحة رئيس القسم</title>
    <style>
        body {
            font-family: "Tajawal", Tahoma, Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
            direction: rtl;
        }
        header {
            background: linear-gradient(135deg, #007bff, #00bcd4);
            color: #fff;
            padding: 15px 25px;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .container {
            max-width: 1100px;
            margin: 20px auto;
            display: flex;
            gap: 20px;
        }
        .students-list {
            width: 30%;
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-height: 600px;
            overflow-y: auto;
        }
        .students-list h2 {
            margin-top: 0;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .student-item {
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .student-item a {
            text-decoration: none;
            color: #333;
            display: block;
        }
        .student-item.active {
            background: #e3f2fd;
        }
        .student-item:hover {
            background: #f5f5f5;
        }

        .chat-panel {
            flex: 1;
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            max-height: 600px;
        }
        .chat-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .chat-header h2 {
            margin: 0;
            font-size: 18px;
        }
        .chat-header small {
            color: #777;
            font-size: 13px;
        }
        .chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 5px;
            background: #fafafa;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .message {
            margin: 5px 0;
            padding: 8px 12px;
            border-radius: 15px;
            max-width: 70%;
            font-size: 14px;
        }
        .me {
            background-color: #007bff;
            color: #fff;
            margin-right: 0;
            margin-left: auto;
            text-align: right;
        }
        .other {
            background-color: #e0e0e0;
            color: #000;
            margin-left: 0;
            margin-right: auto;
            text-align: right;
        }
        .time {
            display: block;
            font-size: 11px;
            margin-top: 3px;
            opacity: 0.7;
        }
        .chat-form {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .chat-form textarea {
            resize: none;
            padding: 8px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .chat-form button {
            align-self: flex-start;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }
        .chat-form button:hover {
            background-color: #0056b3;
        }
        .empty-state {
            text-align: center;
            color: #777;
            margin-top: 40px;
        }
        .back-link {
            margin: 10px 25px 0;
            display: inline-block;
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
        }
    </style>
</head>
<body>

<header>
    💬 التواصل مع المتدربين - لوحة رئيس القسم
</header>

<a href="/TVTC/HTML/head_dashboard.html" class="back-link">←عودة</a>

<div class="container">

    <!-- قائمة الطلاب -->
    <div class="students-list">
        <h2>المتدربون الذين راسلوا القسم</h2>
        <?php if ($result_students->num_rows > 0): ?>
            <?php while ($stu = $result_students->fetch_assoc()): ?>
                <?php
                    $stu_num = $stu['student_number'];
                    $active = ($selected_student === $stu_num) ? 'active' : '';
                ?>
                <div class="student-item <?= $active ?>">
                    <a href="contact_students.php?student_number=<?= urlencode($stu_num) ?>">
                        رقم أكاديمي: <?= htmlspecialchars($stu_num) ?><br>
                        <small>آخر رسالة: <?= htmlspecialchars($stu['last_time']) ?></small>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="font-size: 14px; color:#777;">لا توجد رسائل من المتدربين حتى الآن.</p>
        <?php endif; ?>
    </div>

    <!-- لوحة المحادثة -->
    <div class="chat-panel">
        <?php if ($selected_student === null || $selected_student === ''): ?>
            <div class="empty-state">
                اختر أحد المتدربين من القائمة لعرض المحادثة معه.
            </div>
        <?php else: ?>
            <div class="chat-header">
                <h2>المحادثة مع المتدرب (رقم أكاديمي: <?= htmlspecialchars($selected_student) ?>)</h2>
                <small>يمكنك الرد على استفسارات المتدرب من هنا.</small>
            </div>

            <div class="chat-box">
                <?php if (!empty($messages) && $messages->num_rows > 0): ?>
                    <?php while ($msg = $messages->fetch_assoc()): ?>
                        <?php
                            // الطالب: sender_id = 0
                            $is_me = ($msg['sender_id'] == $current_user_id);
                            $class = $is_me ? 'me' : 'other';
                            $label = $is_me ? 'أنت' : 'المتدرب';
                        ?>
                        <div class="message <?= $class ?>">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            <span class="time">
                                <?= htmlspecialchars($msg['created_at']) ?> - <?= $label ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty-state">لا توجد رسائل بعد مع هذا المتدرب.</p>
                <?php endif; ?>
            </div>

            <form class="chat-form" method="post">
                <input type="hidden" name="student_number" value="<?= htmlspecialchars($selected_student) ?>">
                <textarea name="message" rows="2" placeholder="اكتب ردك على المتدرب هنا..." required></textarea>
                <button type="submit">إرسال الرد</button>
            </form>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
