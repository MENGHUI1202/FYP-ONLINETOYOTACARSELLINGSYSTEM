<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$license_number = $_POST['license_number'] ?? '';
$address = $_POST['address'] ?? '';

if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and email are required']);
    exit();
}

try {
    $stmt = $conn->prepare("
        UPDATE users 
        SET name = ?, email = ?, phone = ?, license_number = ?, address = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param(
        "sssssi",
        $name,
        $email,
        $phone,
        $license_number,
        $address,
        $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>