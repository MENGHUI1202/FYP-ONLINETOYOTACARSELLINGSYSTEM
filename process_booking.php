<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

session_start();
header('Content-Type: application/json');

file_put_contents('booking_debug.log', date('[Y-m-d H:i:s] ') . "=== BOOKING START ===\n", FILE_APPEND);

try {
    require_once 'config.php';

    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No input data');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decode error: ' . json_last_error_msg());
    }

    $required = ['user_id', 'customer_name', 'customer_email', 'customer_phone', 'payment_method', 'cart'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing field: {$field}");
        }
    }

    if (empty($data['cart'])) {
        throw new Exception('Cart is empty');
    }

    $conn->begin_transaction();

    // =======================================================
    // ★★★ 核心修复：生成唯一的 Booking Reference (防重复) ★★★
    // =======================================================
    $date = date('Ymd');
    $prefix = 'BK' . $date; // 例如 BK20260208

    // 1. 不要用 count，而是找今天“最大”的那个订单号
    $stmt = $conn->prepare("SELECT booking_reference FROM bookings WHERE booking_reference LIKE ? ORDER BY booking_reference DESC LIMIT 1");
    $search_param = $prefix . '%';
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();

    $next_seq = 1; // 默认是第1个
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // 比如取到 BK20260208007，截取最后3位 '007'，转成数字 7，然后 +1 = 8
        $last_seq = intval(substr($row['booking_reference'], -3));
        $next_seq = $last_seq + 1;
    }
    $stmt->close();

    // 2. 生成新号码 (例如 008)
    $booking_reference = $prefix . str_pad($next_seq, 3, '0', STR_PAD_LEFT);
    
    file_put_contents('booking_debug.log', "Generated Ref: {$booking_reference}\n", FILE_APPEND);
    // =======================================================

    $total_amount = isset($data['total_amount']) ? floatval($data['total_amount']) : 0;
    $discount_amount = isset($data['discount_amount']) ? floatval($data['discount_amount']) : 0;
    $tax_amount = isset($data['tax_amount']) ? floatval($data['tax_amount']) : 0;
    $service_fee = isset($data['service_fee']) ? floatval($data['service_fee']) : 15.00;
    $grand_total = isset($data['grand_total']) ? floatval($data['grand_total']) : ($total_amount - $discount_amount + $tax_amount + $service_fee);
    $promo_code = isset($data['promo_code']) && !empty($data['promo_code']) ? $data['promo_code'] : NULL;

    // 插入主订单
    if ($promo_code) {
        $sql = "INSERT INTO bookings (
            booking_reference, user_id, customer_name, customer_email, customer_phone,
            payment_method, total_amount, tax_amount, service_fee, grand_total,
            discount_amount, promo_code, booking_status, payment_status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paid', NOW())";
    } else {
        $sql = "INSERT INTO bookings (
            booking_reference, user_id, customer_name, customer_email, customer_phone,
            payment_method, total_amount, tax_amount, service_fee, grand_total,
            discount_amount, booking_status, payment_status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paid', NOW())";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $user_id = intval($data['user_id']);
    $customer_name = $data['customer_name'];
    $customer_email = $data['customer_email'];
    $customer_phone = $data['customer_phone'];
    $payment_method = $data['payment_method'];

    if ($promo_code) {
        $stmt->bind_param(
            "sissssddddds",
            $booking_reference, $user_id, $customer_name, $customer_email, $customer_phone,
            $payment_method, $total_amount, $tax_amount, $service_fee, $grand_total,
            $discount_amount, $promo_code
        );
    } else {
        $stmt->bind_param(
            "sissssddddd",
            $booking_reference, $user_id, $customer_name, $customer_email, $customer_phone,
            $payment_method, $total_amount, $tax_amount, $service_fee, $grand_total,
            $discount_amount
        );
    }

    if (!$stmt->execute()) {
        throw new Exception('Booking insert failed: ' . $stmt->error);
    }

    $booking_id = $conn->insert_id;
    $stmt->close();

    // 准备插入 Booking Items
    $sql_item = "INSERT INTO booking_items (
        booking_id, car_id, car_name, rental_type,
        start_datetime, end_datetime, duration,
        pickup_state, pickup_location,
        dropoff_state, dropoff_location,
        base_price, services_cost, age_surcharge, insurance_cost,
        fuel_policy, fuel_cost, subtotal
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_item = $conn->prepare($sql_item);
    
    // 准备锁车语句
    $stmt_lock = $conn->prepare("UPDATE cars SET availability = 0 WHERE id = ?");

    foreach ($data['cart'] as $item) {
        $start = strtotime($item['startDateTime']);
        $end = strtotime($item['endDateTime']);
        $diff = $end - $start;
        $rental_type = isset($item['rentalType']) ? $item['rentalType'] : 'daily';

        if ($rental_type === 'hourly') {
            $duration = ceil($diff / 3600);
        } else {
            $duration = ceil($diff / 86400);
        }

        if ($rental_type === 'hourly') {
            $base_price = isset($item['pricePerHour']) ? floatval($item['pricePerHour']) : 0;
        } else {
            $base_price = isset($item['price']) ? floatval($item['price']) : 0;
        }

        $services_cost = 0;
        if (isset($item['services']) && isset($item['servicePrices'])) {
            foreach ($item['services'] as $service => $enabled) {
                if ($enabled && isset($item['servicePrices'][$service])) {
                    $services_cost += floatval($item['servicePrices'][$service]) * $duration;
                }
            }
        }

        $age_surcharge = 0;
        if (isset($item['driverAge'])) {
            if ($item['driverAge'] === 'under-25') {
                $age_surcharge = 10 * $duration;
            } elseif ($item['driverAge'] === 'over-69') {
                $age_surcharge = 15 * $duration;
            }
        }

        $insurance_cost = 0;
        if (isset($item['insuranceLevel']) && $item['insuranceLevel'] !== 'basic') {
            $insurance_prices = ['standard' => 20, 'premium' => 45];
            $insurance_cost = isset($insurance_prices[$item['insuranceLevel']]) 
                ? $insurance_prices[$item['insuranceLevel']] * $duration 
                : 0;
        }

        $fuel_policy = isset($item['fuelPolicy']) ? $item['fuelPolicy'] : 'same-to-same';
        $fuel_cost = ($fuel_policy === 'pre-purchase') ? 80.00 : 0.00;

        $item_subtotal = ($base_price * $duration) + $services_cost + $age_surcharge + $insurance_cost + $fuel_cost;

        $pickup_location = isset($item['pickupLocation']) ? $item['pickupLocation'] : '';
        $dropoff_location = isset($item['dropoffLocation']) ? $item['dropoffLocation'] : '';

        // 处理地点格式 (State - Location)
        $pickup_state = ''; $pickup_loc = $pickup_location;
        if(strpos($pickup_location, ' - ') !== false) {
            $parts = explode(' - ', $pickup_location);
            $pickup_state = $parts[0];
            $pickup_loc = $parts[1];
        }

        $dropoff_state = ''; $dropoff_loc = $dropoff_location;
        if(strpos($dropoff_location, ' - ') !== false) {
            $parts = explode(' - ', $dropoff_location);
            $dropoff_state = $parts[0];
            $dropoff_loc = $parts[1];
        }

        $car_id = intval($item['id']);
        $car_name = $item['name'];

        $stmt_item->bind_param(
            "iissssissssddddsdd",
            $booking_id, $car_id, $car_name, $rental_type,
            $item['startDateTime'], $item['endDateTime'], $duration,
            $pickup_state, $pickup_loc, $dropoff_state, $dropoff_loc,
            $base_price, $services_cost, $age_surcharge, $insurance_cost,
            $fuel_policy, $fuel_cost, $item_subtotal
        );

        if (!$stmt_item->execute()) {
            throw new Exception('Item insert failed: ' . $stmt_item->error);
        }

        $booking_item_id = $conn->insert_id;

        // ★★★ 核心功能：下单即锁车 ★★★
        if ($stmt_lock) {
            $stmt_lock->bind_param("i", $car_id);
            $stmt_lock->execute();
        }

        // 插入具体服务项 (可选)
        if (isset($item['services']) && is_array($item['services'])) {
            $sql_service = "INSERT INTO booking_services (booking_item_id, service_name, service_price, quantity, total_price) VALUES (?, ?, ?, ?, ?)";
            $stmt_service = $conn->prepare($sql_service);
            if ($stmt_service) {
                foreach ($item['services'] as $service_name => $enabled) {
                    if ($enabled && isset($item['servicePrices'][$service_name])) {
                        $service_price = floatval($item['servicePrices'][$service_name]);
                        $total_price = $service_price * $duration;
                        $stmt_service->bind_param("isdid", $booking_item_id, $service_name, $service_price, $duration, $total_price);
                        $stmt_service->execute();
                    }
                }
                $stmt_service->close();
            }
        }
    }

    $stmt_item->close();
    if ($stmt_lock) $stmt_lock->close();

    // 更新 Promo Code 使用次数
    if ($promo_code && $discount_amount > 0) {
        $stmt_promo = $conn->prepare("UPDATE promo_codes SET usage_count = usage_count + 1 WHERE code = ?");
        if ($stmt_promo) {
            $stmt_promo->bind_param("s", $promo_code);
            $stmt_promo->execute();
            $stmt_promo->close();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'booking_reference' => $booking_reference,
        'message' => 'Booking created successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>