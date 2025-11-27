<?php
session_start();

// ربط قاعدة البيانات
require_once 'dp.php';

// جلب البيانات من الجدول
$sql = "SELECT * FROM org_structure
        ORDER BY 
            FIELD(department,
                'العميد',
                'الإدارة',
                'الوكيل',
                'رئيس قسم الحاسب وتقنية المعلومات',
                'رئيس قسم التقنية الإدارية والمالية',
                'مدرب - قسم الحاسب وتقنية المعلومات',
                'مدرب - قسم التقنية الإدارية والمالية',
                ''
            ),
            sort_order ASC,
            id ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الهيكل الإداري</title>

    <style>
        body { font-family: Tahoma, Arial, sans-serif; background:#f9f9f9; padding:20px; }
        h2 { text-align:center; }

        table { border-collapse: collapse; width: 100%; margin-top: 20px; background:white; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #e6e6e6; }

        a.button {
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .add-btn { background: #28a745; color: white; }
        .edit-btn { background: #007bff; color: white; }
        .delete-btn { background: #dc3545; color: white; }
    </style>
</head>

<body>

<h2>إدارة الهيكل الإداري</h2>

<p style="text-align:center;">
    <a href="add_member.php" class="button add-btn">+ إضافة عضو جديد</a>
</p>

<table>
    <tr>
        <th>الرقم</th>
        <th>الاسم</th>
        <th>البريد الإلكتروني</th>
        <th>المسمى الوظيفي</th>
        <th>القسم</th>
        <th>ترتيب الظهور</th>
        <th>الصورة</th>
        <th>الإجراءات</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['job_title']) ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td><?= htmlspecialchars($row['sort_order']) ?></td>

                <td>
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="<?= htmlspecialchars($row['image_path']) ?>" width="60">
                    <?php else: ?>
                        لا يوجد صورة
                    <?php endif; ?>
                </td>

                <td>
                    <a href="edit_member.php?id=<?= $row['id'] ?>" class="button edit-btn">تعديل</a>
                    <a href="delete_member.php?id=<?= $row['id'] ?>" class="button delete-btn"
                       onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا العضو؟');">
                        حذف
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>

    <?php else: ?>
        <tr>
            <td colspan="8">لا يوجد أعضاء مضافين بعد.</td>
        </tr>
    <?php endif; ?>

</table>

</body>
</html>
