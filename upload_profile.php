<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$user_id = $_SESSION['user_id'];
$file = $_FILES['profile_picture'];

// 检查文件类型
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG and GIF files are allowed']);
    exit();
}

// 检查文件大小 (最大2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 2MB']);
    exit();
}

// 创建上传目录
$upload_dir = 'uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 生成唯一文件名
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
$destination = $upload_dir . $new_filename;

// 移动上传的文件
if (move_uploaded_file($file['tmp_name'], $destination)) {
    // 更新数据库
    $profile_url = $destination;
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $profile_url, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Profile picture updated',
            'profile_picture' => $profile_url
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
}
?>