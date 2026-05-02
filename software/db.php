<?php
// db.php — 数据库连接，所有页面 include 这个文件

// 强制声明 HTTP 响应编码，防止 Windows/Apache 默认用 latin1
header('Content-Type: text/html; charset=utf-8');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // XAMPP 默认用户名
define('DB_PASS', 'password');           // XAMPP 默认无密码
define('DB_NAME', 'rental');     // 你的数据库名，按实际修改

// 五种状态的中文映射
define('STATUS_LABELS', [
    'in_stock'    => '已入库',
    'rented'      => '已出租',
    'returned'    => '已回收',
    'inspected'   => '已检查',
    'disinfected' => '已消毒',
]);

// 状态流转顺序（用于校验）
define('STATUS_FLOW', [
    'in_stock'    => 'rented',
    'rented'      => 'returned',
    'returned'    => 'inspected',
    'inspected'   => 'disinfected',
    'disinfected' => 'in_stock',
]);

function get_db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            // api.php 会输出 JSON，普通页面直接 die
            $err = 'DB连接失败: ' . $conn->connect_error;
            if (php_sapi_name() === 'cli' || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                die(json_encode(['ok' => false, 'error' => $err]));
            }
            die('<h2 style="color:red">' . htmlspecialchars($err) . '</h2>');
        }
        $conn->set_charset('utf8mb4');
        // 双保险：显式告诉 MySQL 服务端用 utf8mb4，解决 Windows my.ini 默认 latin1 的问题
        $conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    }
    return $conn;
}