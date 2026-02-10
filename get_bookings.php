<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户的所有预订，使用COALESCE确保使用grand_total字段
$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.booking_reference,
        b.customer_name,
        b.customer_email,
        b.customer_phone,
        b.payment_method,
        b.payment_status,
        b.booking_status,
        b.created_at,
        -- 优先使用 grand_total，如果没有则使用 total_amount
        COALESCE(b.grand_total, b.total_amount, 0) as total_amount,
        b.grand_total, -- 单独获取grand_total字段用于检查
        b.total_amount as original_total_amount, -- 获取原始total_amount用于参考
        COUNT(bi.id) as item_count,
        MIN(bi.start_datetime) as earliest_pickup
    FROM bookings b
    LEFT JOIN booking_items bi ON b.id = bi.booking_id
    WHERE b.user_id = ?
    GROUP BY b.id, b.booking_reference, b.customer_name, b.customer_email, 
             b.customer_phone, b.payment_method, b.payment_status, 
             b.booking_status, b.created_at, b.grand_total, b.total_amount
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'bookings' => $bookings
]);
?>