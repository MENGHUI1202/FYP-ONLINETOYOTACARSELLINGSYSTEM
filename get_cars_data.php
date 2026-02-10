<?php
header('Content-Type: application/json');
require_once 'config.php';

// 获取 POST 数据
$input = json_decode(file_get_contents('php://input'), true);
$car_ids = $input['car_ids'] ?? [];

if (empty($car_ids)) {
    echo json_encode([]);
    exit;
}

$result = [];

foreach ($car_ids as $car_id) {
    $car_id = intval($car_id);

    // 获取车辆基本信息
    $sql = "SELECT * FROM cars WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $car = $stmt->get_result()->fetch_assoc();

    if ($car) {
        // 获取车辆所有图片
        $images = [];
        $sql_images = "SELECT image_url FROM car_images WHERE car_id = ? ORDER BY image_order ASC, id ASC";
        $stmt_images = $conn->prepare($sql_images);
        $stmt_images->bind_param("i", $car_id);
        $stmt_images->execute();
        $result_images = $stmt_images->get_result();

        while ($img = $result_images->fetch_assoc()) {
            $images[] = $img['image_url'];
        }

        // 如果没有图片，使用 cars 表的 image_url
        if (empty($images) && !empty($car['image_url'])) {
            $images[] = $car['image_url'];
        }

        // 如果仍然没有图片，使用默认图片
        if (empty($images)) {
            $images[] = 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=900&auto=format&fit=crop';
        }

        $car['images'] = $images;
        $result[$car_id] = $car;

        $stmt_images->close();
    }

    $stmt->close();
}

$conn->close();

echo json_encode($result);
?>