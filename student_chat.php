<?php
// استدعاء ملف إرسال WhatsApp (تأكد من المسار)
require_once 'whatsapp_helper.php';

// اتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "project");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// التأكد من وجود student_id في الرابط
if (!isset($_GET['student_id'])) {
    die("رقم الطلب غير محدد.");
}

$student_id = (int) $_GET['student_id'];

// جلب بيانات الطالب
$sql_student = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows == 0) {
    die("الطلب غير موجود.");
}

$student = $result_student->fetch_assoc();

// رقم الإدارة الثابت (ID داخل النظام)
$admin_id = 1;

// عند إرسال رسالة من الطالب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if ($message !== '') {
        $sql_insert = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        // المرسل: الطالب (student_id) - المستقبل: الإدارة
        $stmt_insert->bind_param("iis", $student_id, $admin_id, $message);
        $stmt_insert->execute();
        $stmt_insert->close();

        // 🔔 إشعار واتساب للإدارة بأن فيه رسالة جديدة
        // رقم واتساب الإدارة بصيغة دولية (مثال: 9665XXXXXXXX)
        $adminWhatsapp = "9665XXXXXXXX"; // 👈 عدّل هذا لرقم إدارة الكلية
        $waText = "رسالة جديدة من المتدرب {$student['student_name']} بخصوص طلب رقم {$student_id}:\n\n{$message}";

        sendWhatsAppMessage($adminWhatsapp, $waText);

        header("Location: student_chat.php?student_id=" . $student_id);
        exit;
    }
}

// جلب الرسائل بين الطالب والإدارة
$sql_messages = "
    SELECT * FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
";

$stmt_msg = $conn->prepare($sql_messages);
$stmt_msg->bind_param("iiii", $admin_id, $student_id, $student_id, $admin_id);
$stmt_msg->execute();
$result_messages = $stmt_msg->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>متابعة طلبك - محادثة مع الإدارة</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            direction: rtl;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        /* زر العودة */
        .back-btn {
            display: inline-block;
            margin-bottom: 15px;
            background-color: #22bace;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }
        .back-btn:hover {
            background-color: #1a97a8;
        }

        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .chat-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .chat-header h2 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }
        .chat-header small {
            color: #666;
        }

        .chat-box {
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
            background-color: #fafafa;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .message {
            margin: 6px 0;
            padding: 8px 12px;
            border-radius: 15px;
            max-width: 70%;
            font-size: 14px;
            line-height: 1.4;
        }
        /* رسالة الطالب */
        .me {
            background-color: #007bff;
            color: #fff;
            margin-right: 0;
            margin-left: auto;
            text-align: right;
        }
        /* رسالة الإدارة */
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
            opacity: 0.8;
        }

        .chat-form {
            margin-top: 12px;
            display: flex;
            gap: 10px;
        }
        .chat-form textarea {
            flex: 1;
            resize: none;
            padding: 8px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-family: Tahoma, Arial, sans-serif;
        }
        .chat-form button {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            background-color: #22bace;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
        }
        .chat-form button:hover {
            background-color: #1a97a8;
        }
    </style>
</head>
<body>

<!-- 🔙 زر العودة لصفحة الطلب -->
<a href="../HTML/طلب خدمة.html" class="back-btn">⬅ العودة إلى صفحة الطلب</a>

<div class="chat-container">
    <div class="chat-header">
        <h2>متابعة طلبك رقم: <?= htmlspecialchars($student_id) ?></h2>
        <small>الاسم: <?= htmlspecialchars($student['student_name']) ?></small><br>
        <small>البريد: <?= htmlspecialchars($student['student_email']) ?></small>
    </div>

    <div class="chat-box">
        <?php if ($result_messages->num_rows > 0): ?>
            <?php while ($msg = $result_messages->fetch_assoc()): ?>
                <?php
                $is_me = ($msg['sender_id'] == $student_id);
                $class = $is_me ? 'me' : 'other';
                ?>
                <div class="message <?= $class ?>">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    <span class="time">
                        <?= htmlspecialchars($msg['created_at']) ?>
                        <?= $is_me ? ' - أنت' : ' - الإدارة' ?>
                    </span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#777;">لا توجد رسائل بعد، يمكنك كتابة سؤالك للإدارة.</p>
        <?php endif; ?>
    </div>

    <form class="chat-form" method="post">
        <textarea name="message" rows="2" placeholder="اكتب رسالتك هنا..."></textarea>
        <button type="submit">إرسال</button>
    </form>
</div>

</body>
</html>
