<?php
// استدعاء ملف إرسال WhatsApp (تأكد من المسار بالنسبة لهذا الملف)
require_once 'whatsapp_helper.php';

// رقم الإدارة الثابت (تقدّر تغيّره)
$admin_id = 1;

// اتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "project");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// التأكد من وجود student_id في الرابط
if (!isset($_GET['student_id'])) {
    die("الطالب غير محدد.");
}

$student_id = (int) $_GET['student_id'];

// جلب بيانات الطالب من جدول students
$sql_student = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows == 0) {
    die("الطالب غير موجود.");
}

$student = $result_student->fetch_assoc();

// إذا تم إرسال رسالة من الإدارة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if ($message !== '') {
        $sql_insert = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        // المرسل: الإدارة - المستقبل: الطالب (رقم الطلب)
        $stmt_insert->bind_param("iis", $admin_id, $student_id, $message);
        $stmt_insert->execute();
        $stmt_insert->close();

        // 🔔 إشعار واتساب للطالب بأن الإدارة ردّت على طلبه

        // الرقم المخزن في قاعدة البيانات مثلاً: 05XXXXXXXX
        $rawPhone = trim($student['student_phone']);
        // إزالة المسافات
        $rawPhone = preg_replace('/\s+/', '', $rawPhone);
        // تحويله لصيغة دولية (9665XXXXXXXX)
        $studentWhatsapp = '966' . ltrim($rawPhone, '0');

        // نص الرسالة التي ستصل للطالب
        $waText  = "تم الرد على طلبك رقم {$student_id} من قبل إدارة الكلية:\n\n";
        $waText .= "{$message}\n\n";
        $waText .= "يمكنك متابعة تفاصيل المحادثة عبر موقع الكلية.";

        // إرسال رسالة واتساب
        sendWhatsAppMessage($studentWhatsapp, $waText);

        header("Location: admin_chat_student.php?student_id=" . $student_id);
        exit;
    }
}

// جلب الرسائل بين الإدارة والطالب
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
    <title>الدردشة مع <?= htmlspecialchars($student['student_name']) ?></title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            direction: rtl;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
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
        }
        .chat-header small {
            color: #555;
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
            gap: 10px;
        }
        .chat-form textarea {
            flex: 1;
            resize: none;
            padding: 8px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .chat-form button {
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
        .back-link {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: #333;
        }
    </style>
</head>
<body>

<div class="chat-container">
    <div class="chat-header">
        <h2>الدردشة مع: <?= htmlspecialchars($student['student_name']) ?></h2>
        <small>البريد: <?= htmlspecialchars($student['student_email']) ?></small><br>
        <small>الجوال: <?= htmlspecialchars($student['student_phone']) ?></small>
    </div>

    <div class="chat-box">
        <?php if ($result_messages->num_rows > 0): ?>
            <?php while ($msg = $result_messages->fetch_assoc()): ?>
                <?php
                $is_me = ($msg['sender_id'] == $admin_id);
                $class = $is_me ? 'me' : 'other';
                ?>
                <div class="message <?= $class ?>">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    <span class="time">
                        <?= htmlspecialchars($msg['created_at']) ?>
                        <?= $is_me ? ' - الإدارة' : ' - الطالب' ?>
                    </span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#777;">لا توجد رسائل بعد، ابدأ المحادثة الآن.</p>
        <?php endif; ?>
    </div>

    <form class="chat-form" method="post">
        <textarea name="message" rows="2" placeholder="اكتب ردك هنا..."></textarea>
        <button type="submit">إرسال</button>
    </form>

    <a href="عرض_الطلبات_حقك.php" class="back-link">🔙 الرجوع لقائمة الطلبات</a>
</div>

</body>
</html>
