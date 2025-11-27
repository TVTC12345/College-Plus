<?php
// إعداد الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // استقبال البيانات من النموذج
    $student_dep = $conn->real_escape_string($_POST['student_dep']);
    $student_servies = $conn->real_escape_string($_POST['student_servies']);
    $student_name = $conn->real_escape_string($_POST['student_name']);
    $student_phone = $conn->real_escape_string($_POST['student_phone']);
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $student_email = $conn->real_escape_string($_POST['student_email']);
    $student_comment = $conn->real_escape_string($_POST['student_comment']);

    // إدخال البيانات في قاعدة البيانات
    $sql = "INSERT INTO students (student_dep, student_servies, student_name, student_phone, student_id, student_email, student_comment)
            VALUES ('$student_dep', '$student_servies', '$student_name', '$student_phone', '$student_id', '$student_email', '$student_comment')";

    if ($conn->query($sql) === TRUE) {
        // عرض رسالة النجاح وإعادة التوجيه بعد 3 ثوانٍ
        echo "
        <html lang='ar' dir='rtl'>
        <head>
        <meta charset='UTF-8'>
        <meta http-equiv='refresh' content='3;url=../HTML/trainee.html'>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f7f7f7;
                text-align: center;
                margin-top: 200px;
                color: #333;
            }
            .msg-box {
                background-color: #e0f7f3;
                border: 1px solid #22baced7;
                display: inline-block;
                padding: 30px 50px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        </style>
        </head>
        <body>
            <div class='msg-box'>
                <h2>✅ تم إرسال الطلب بنجاح!</h2>
                <p>سيتم تحويلك إلى صفحة المتدرب خلال ثوانٍ...</p>
            </div>
        </body>
        </html>
        ";
    } else {
        echo "❌ حدث خطأ أثناء إدخال البيانات: " . $conn->error;
    }
}

$conn->close();
?>
