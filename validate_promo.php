<?php
session_start(); // ★★★ 必须开启 Session ★★★
require_once 'includes/config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

// 检查是否是“移除优惠码”的操作
if (isset($input['action']) && $input['action'] === 'remove') {
    unset($_SESSION['applied_promo']); // 清除 Session
    echo json_encode(['success' => true, 'message' => 'Promo code removed']);
    exit;
}

$code = isset($input['code']) ? trim($conn->real_escape_string($input['code'])) : '';
$totalAmount = isset($input['total']) ? floatval($input['total']) : 0;

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a promo code']);
    exit;
}

// 查询数据库 (使用你 phpMyAdmin 的字段: valid_until, is_active)
$sql = "SELECT * FROM promo_codes WHERE code = '$code' AND is_active = 1 LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $promo = $result->fetch_assoc();
    $currentDate = date('Y-m-d H:i:s');
    
    // 1. 检查开始时间
    if ($currentDate < $promo['valid_from']) {
        echo json_encode(['success' => false, 'message' => 'This promo code is not active yet']);
        exit;
    }

    // 2. 检查过期时间
    if ($currentDate > $promo['valid_until']) {
        echo json_encode(['success' => false, 'message' => 'This promo code has expired']);
        exit;
    }

    // 3. 检查使用次数
    if ($promo['usage_limit'] > 0 && $promo['usage_count'] >= $promo['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Promo code usage limit reached']);
        exit;
    }

    // 4. 计算折扣
    $discountAmount = 0;
    if ($promo['discount_type'] == 'fixed') {
        $discountAmount = floatval($promo['discount_value']);
    } else {
        $discountAmount = $totalAmount * (floatval($promo['discount_value']) / 100);
    }

    if ($discountAmount > $totalAmount) {
        $discountAmount = $totalAmount;
    }

    // ★★★ 核心修复：把折扣存入 SESSION，这样 Checkout 页面才能读到 ★★★
    $_SESSION['applied_promo'] = [
        'code' => $code,
        'discount_amount' => $discountAmount,
        'type' => $promo['discount_type'],
        'value' => $promo['discount_value']
    ];

    echo json_encode([
        'success' => true,
        'promo_code' => $code,
        'discount_amount' => number_format($discountAmount, 2, '.', ''),
        'new_total' => number_format($totalAmount - $discountAmount, 2, '.', '')
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or inactive promo code']);
}
?>