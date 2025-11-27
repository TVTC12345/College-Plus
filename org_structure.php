<?php
// org_structure.php

// اتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "project");
$conn->set_charset("utf8");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب بيانات الهيكل الإداري
$sql = "SELECT * FROM org_structure ORDER BY sort_order ASC, id ASC";
$result = $conn->query($sql);
?>

<div class="departments-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>

            <?php
            // تجهيز مسار الصورة
            $image = $row['image_path'];

            // لو ما فيه صورة → استخدم صورة افتراضية
            if (empty($image)) {
                // من منظور صفحة HTML: /TVTC/HTML/عن الكلية.html
                $image = '../IMG/default.png';
            } else {
                // عمود image_path يحفظ اسم الملف فقط مثل "ahmed.jpg"
                $image = '../IMG/' . $image;
            }
            ?>

            <div class="department">
                <div class="box">
                    <img src="<?= htmlspecialchars($image) ?>" alt="" class="member-img">
                    <h3><?= htmlspecialchars($row['name']) ?></h3>

                    <?php if (!empty($row['email'])): ?>
                        <p><?= htmlspecialchars($row['email']) ?></p>
                    <?php endif; ?>

                    <p><?= htmlspecialchars($row['job_title']) ?></p>

                    <?php if (!empty($row['department'])): ?>
                        <p><?= htmlspecialchars($row['department']) ?></p>
                    <?php endif; ?>

                    <!-- زر الدردشة -->
                    <a href="/TVTC/PHP/chat.php?id=<?= $row['id'] ?>" class="chat-btn">مراسلة</a>
                </div>
            </div>

        <?php endwhile; ?>
    <?php else: ?>
        <p>لم يتم إضافة بيانات الهيكل الإداري بعد.</p>
    <?php endif; ?>
</div>
