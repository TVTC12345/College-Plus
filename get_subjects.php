<?php
header('Content-Type: application/json; charset=utf-8');

$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "project";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error]);
    exit;
}

$dept  = $_GET['dept'] ?? '';
$level = $_GET['level'] ?? '';

if (empty($dept) || empty($level)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT title, file_path FROM courses WHERE dept = ? AND level = ?");
$stmt->bind_param("ss", $dept, $level);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "name" => htmlspecialchars($row["title"], ENT_QUOTES, 'UTF-8'),
        "file" => "/TVTC/uploads/" . $row["file_path"]
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
