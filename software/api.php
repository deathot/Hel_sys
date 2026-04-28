<?php
// api.php — ESP8266 上报接口
// POST { "device_id": "ESP_001", "api_key": "xxx", "qr_code": "ITEM_A" }

header('Content-Type: application/json; charset=utf-8');

// 只允许 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => '只接受 POST 请求']);
    exit;
}

require_once __DIR__ . '/db.php';

// ---------- 1. 读取并解析请求体 ----------
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '请求体不是合法 JSON']);
    exit;
}

$device_id = trim($body['device_id'] ?? '');
$api_key   = trim($body['api_key']   ?? '');
$qr_code   = trim($body['qr_code']   ?? '');

if (!$device_id || !$api_key || !$qr_code) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '缺少必要字段: device_id / api_key / qr_code']);
    exit;
}

$db = get_db();

// ---------- 2. 校验 api_key，找到站点 ----------
$stmt = $db->prepare(
    'SELECT id, station_name, bound_status FROM stations WHERE device_id = ? AND api_key = ? LIMIT 1'
);
$stmt->bind_param('ss', $device_id, $api_key);
$stmt->execute();
$station = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$station) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '鉴权失败：device_id 或 api_key 错误']);
    exit;
}

$station_id   = $station['id'];
$station_name = $station['station_name'];
$bound_status = $station['bound_status'];

// ---------- 3. 查设备当前状态 ----------
$stmt = $db->prepare(
    'SELECT id, name, status FROM items WHERE qr_code = ? LIMIT 1'
);
$stmt->bind_param('s', $qr_code);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => '未找到二维码对应设备: ' . $qr_code]);
    exit;
}

$item_id     = $item['id'];
$item_name   = $item['name'];
$from_status = $item['status'];

// ---------- 4. （可选）校验状态流转是否合法 ----------
// STATUS_FLOW 定义了合法的下一步；如果不想强校验，注释掉这段
$expected_next = STATUS_FLOW[$from_status] ?? null;
if ($expected_next !== $bound_status) {
    http_response_code(422);
    $labels = STATUS_LABELS;
    echo json_encode([
        'ok'    => false,
        'error' => sprintf(
            '状态流转不合法：当前 %s（%s），本工位要写入 %s（%s），期望下一步应为 %s（%s）',
            $from_status, $labels[$from_status] ?? $from_status,
            $bound_status, $labels[$bound_status] ?? $bound_status,
            $expected_next, $labels[$expected_next] ?? $expected_next
        ),
    ]);
    exit;
}

// ---------- 5. 更新设备状态 ----------
$stmt = $db->prepare('UPDATE items SET status = ? WHERE id = ?');
$stmt->bind_param('si', $bound_status, $item_id);
$stmt->execute();
$stmt->close();

// ---------- 6. 写日志 ----------
$stmt = $db->prepare(
    'INSERT INTO logs (item_id, station_id, from_status, to_status) VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('iiss', $item_id, $station_id, $from_status, $bound_status);
$stmt->execute();
$stmt->close();

// ---------- 7. 返回成功响应 ----------
echo json_encode([
    'ok'           => true,
    'station_name' => $station_name,
    'item_name'    => $item_name,
    'from_status'  => $from_status,
    'to_status'    => $bound_status,
    'from_label'   => STATUS_LABELS[$from_status]  ?? $from_status,
    'to_label'     => STATUS_LABELS[$bound_status] ?? $bound_status,
], JSON_UNESCAPED_UNICODE);
