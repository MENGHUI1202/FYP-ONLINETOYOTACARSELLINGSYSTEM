<?php
session_start();

// 清除所有 session 变量
$_SESSION = array();

// 如果使用了 session cookie，也删除它
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁 session
session_destroy();

// 跳转到首页
header('Location: homepage.php');
exit();
?>
