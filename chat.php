<?php
session_start();

// اتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "project");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// 👈 رقم الموظف من الرابط chat.php?id=...
if (!isset($_GET['id'])) {
    die("الموظف غير محدد.");
}

$staff_id = (int) $_GET['id'];

// جلب بيانات الموظف من جدول org_structure
$sql_staff = "SELECT * FROM org_structure WHERE id = ?";
$stmt = $conn->prepare($sql_staff);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result_staff = $stmt->get_result();

if ($result_staff->num_rows == 0) {
    die("الموظف غير موجود.");
}

$staff = $result_staff->fetch_assoc();

// نتأكد أنه عنده إيميل
if (empty($staff['email'])) {
    die("لا يوجد بريد إلكتروني مرتبط بهذا الموظف في جدول org_structure.");
}

$staff_email = $staff['email'];

// نجيب رقم المستخدم (user_id) من جدول loginss بناءً على الايميل (بدون حساسية حروف)
$sql_get_user = "SELECT id FROM loginss WHERE LOWER(email) = LOWER(?) LIMIT 1";
$stmt_get_user = $conn->prepare($sql_get_user);
$stmt_get_user->bind_param("s", $staff_email);
$stmt_get_user->execute();
$res_user = $stmt_get_user->get_result();

if ($res_user->num_rows == 0) {
    die("لا يوجد حساب في جدول loginss مرتبط بهذا البريد الإلكتروني: " . htmlspecialchars($staff_email));
}

$user_row    = $res_user->fetch_assoc();
$receiver_id = (int) $user_row['id'];   // هذا هو نفس user_id الذي يستخدمه contact_students.php


// ======================= 1️⃣ طلب الرقم الأكاديمي =========================

if (!isset($_GET['student_number'])) {

    // إذا الطالب أرسل الرقم الأكاديمي
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_number_only'])) {

        $s_number = trim($_POST['student_number_only']);

        if ($s_number === '') {
            $error = "الرجاء إدخال رقمك الأكاديمي";
        } else {
            // نعيد التوجيه مع رقم الطالب في الرابط
            header("Location: chat.php?id=$staff_id&student_number=" . urlencode($s_number));
            exit;
        }
    }

    // عرض صفحة إدخال الرقم الأكاديمي فقط
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>تأكيد الرقم الأكاديمي</title>
        <style>
            body { font-family:Tajawal, Arial; background:#f7f7f7; text-align:center; padding-top:60px; }
            .box {
                width: 350px; margin:auto; background:white; padding:20px;
                border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);
            }
            input {
                width: 90%; padding:10px; margin-top:10px; border-radius:8px; border:1px solid #ccc;
            }
            button {
                margin-top:15px; padding:10px 20px; border:none; border-radius:8px;
                background:#007bff; color:white; cursor:pointer;
            }
            a {
                display:block; margin-top:15px; color:#007bff; text-decoration:none;
            }
        </style>
    </head>
    <body>

    <div class="box">
        <h2>الرجاء إدخال رقمك الأكاديمي</h2>

        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form method="post">
            <input type="text" name="student_number_only" placeholder="أدخل رقمك الأكاديمي">
            <button type="submit">متابعة</button>
        </form>

        <a href="/TVTC/HTML/عن الكلية.html">⬅ الرجوع إلى عن الكلية</a>
    </div>

    </body>
    </html>
    <?php
    exit;
}

// ======================= 2️⃣ الطالب أدخل رقمه =========================

$student_number = trim($_GET['student_number']);

if ($student_number === '') {
    die("رقم أكاديمي غير صالح.");
}

// ======================= 3️⃣ إرسال رسالة الطالب =========================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {

    $message = trim($_POST['message']);

    if ($message !== '') {
        // الطالب دائمًا sender_id = 0
        // الرسالة تذهب إلى الموظف (رئيس القسم/المدرب) receiver_id = $receiver_id
        $sql_insert = "INSERT INTO messages (sender_id, receiver_id, message, student_number)
                       VALUES (0, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iss", $receiver_id, $message, $student_number);
        $stmt_insert->execute();
        $stmt_insert->close();

        // نرجع لنفس الصفحة لتجنب إعادة الإرسال عند إعادة تحميلها
        header("Location: chat.php?id=$staff_id&student_number=" . urlencode($student_number));
        exit;
    }
}

// ======================= 4️⃣ جلب الرسائل =========================

$sql_messages = "
    SELECT * FROM messages
    WHERE receiver_id = ? AND student_number = ?
    ORDER BY created_at ASC
";
$stmt_msg = $conn->prepare($sql_messages);
$stmt_msg->bind_param("is", $receiver_id, $student_number);
$stmt_msg->execute();
$messages = $stmt_msg->get_result();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الدردشة مع <?= htmlspecialchars($staff['name']) ?></title>

    <style>
        body { font-family: Tajawal, Arial; background:#f2f2f2; margin:0; padding:0; }
        .chat-container { max-width:800px; margin:30px auto; background:white; padding:20px;
                          border-radius:12px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
        .chat-header { display:flex; align-items:center; border-bottom:1px solid #ddd; padding-bottom:10px; }
        .chat-header img { width:60px; height:60px; border-radius:50%; margin-left:15px; object-fit:cover; }
        .chat-box { height:350px; overflow-y:auto; padding:10px; background:#fafafa; border-radius:10px; }
        .message { margin:8px 0; padding:8px 12px; max-width:70%; border-radius:15px; }
        .me { background:#007bff; color:white; margin-left:auto; text-align:right; }
        .other { background:#ddd; color:black; margin-right:auto; text-align:right; }
        .time { font-size:11px; opacity:0.7; margin-top:4px; }
        textarea { width:100%; height:70px; padding:8px; border-radius:8px; border:1px solid #ccc; }
        button { margin-top:8px; padding:10px 20px; background:#007bff; color:white; border:none;
                 border-radius:8px; cursor:pointer; }
        .back-link { display:inline-block; margin-bottom:10px; color:#007bff; text-decoration:none; }
    </style>
</head>
<body>

<div class="chat-container">

    <a href="/TVTC/HTML/عن الكلية.html" class="back-link">⬅ الرجوع إلى عن الكلية</a>

    <div class="chat-header">
        <?php
        $image = $staff['image_path'] ? "../IMG/".$staff['image_path'] : "../IMG/default.png";
        ?>
        <img src="<?= htmlspecialchars($image) ?>">
        <div>
            <h3><?= htmlspecialchars($staff['name']) ?></h3>
            <small><?= htmlspecialchars($staff['job_title']) ?></small><br>
            <?php if (!empty($staff['department'])): ?>
                <small><?= htmlspecialchars($staff['department']) ?></small><br>
            <?php endif; ?>
            <?php if (!empty($staff['email'])): ?>
                <small>البريد: <?= htmlspecialchars($staff['email']) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <h4>رقمك الأكاديمي: <?= htmlspecialchars($student_number) ?></h4>

    <div class="chat-box">
        <?php if ($messages->num_rows > 0): ?>
            <?php while ($msg = $messages->fetch_assoc()): ?>
                <?php
                    // الطالب دائماً sender_id = 0
                    $is_me  = ($msg['sender_id'] == 0);
                    $class  = $is_me ? 'me' : 'other';
                    $label  = $is_me ? 'أنت' : 'مدرب';
                ?>
                <div class="message <?= $class ?>">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    <div class="time"><?= htmlspecialchars($msg['created_at']) ?> - <?= $label ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#777;">لا توجد رسائل بعد.</p>
        <?php endif; ?>
    </div>

    <form method="post">
        <textarea name="message" placeholder="اكتب رسالتك هنا..." required></textarea>
        <button type="submit">إرسال</button>
    </form>

</div>

</body>
</html>
