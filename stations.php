<?php
// stations.php — 站点管理
require_once __DIR__ . '/db.php';

$page_title = '站点管理';
$active_nav = 'stations';
$db = get_db();
$flash = '';

// ── 处理 POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $device_id    = trim($_POST['device_id']    ?? '');
        $station_name = trim($_POST['station_name'] ?? '');
        $bound_status = $_POST['bound_status']      ?? '';

        if ($device_id && $station_name && isset(STATUS_LABELS[$bound_status])) {
            // 自动生成 api_key
            $api_key = bin2hex(random_bytes(16)); // 32字符随机串

            $stmt = $db->prepare(
                'INSERT INTO stations (device_id, api_key, station_name, bound_status) VALUES (?,?,?,?)'
            );
            $stmt->bind_param('ssss', $device_id, $api_key, $station_name, $bound_status);
            if ($stmt->execute()) {
                $flash = "ok|站点「{$station_name}」已添加，API Key 已自动生成";
            } else {
                $flash = "err|添加失败：device_id 可能已存在 ({$device_id})";
            }
            $stmt->close();
        } else {
            $flash = 'err|请填写全部字段';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare('DELETE FROM stations WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $flash = 'ok|站点已删除（关联日志的 station_id 置为 NULL）';
            $stmt->close();
        }
    }

    if ($action === 'regen_key') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $new_key = bin2hex(random_bytes(16));
            $stmt = $db->prepare('UPDATE stations SET api_key = ? WHERE id = ?');
            $stmt->bind_param('si', $new_key, $id);
            $stmt->execute();
            $flash = 'ok|API Key 已重新生成，请更新 ESP8266 固件中的配置';
            $stmt->close();
        }
    }
}

// ── 查询所有站点 ──
$stations = $db->query(
    'SELECT * FROM stations ORDER BY created_at DESC'
)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/layout.php';

if ($flash) {
    [$type, $msg] = explode('|', $flash, 2);
    echo "<div class='flash flash-{$type}'>" . ($type==='ok'?'✓':'✗') . ' ' . htmlspecialchars($msg) . "</div>";
}
?>

<!-- 添加站点 -->
<div class="card" style="margin-bottom:20px">
    <div style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:14px">
        添加站点
    </div>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label>ESP8266 Device ID</label>
                <input type="text" name="device_id" placeholder="如 ESP_001" required>
            </div>
            <div class="form-group">
                <label>工位名称</label>
                <input type="text" name="station_name" placeholder="如 消毒间" required>
            </div>
            <div class="form-group" style="max-width:180px">
                <label>扫码后目标状态</label>
                <select name="bound_status" required>
                    <option value="">选择状态…</option>
                    <?php foreach (STATUS_LABELS as $s => $l): ?>
                    <option value="<?= $s ?>"><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;align-items:flex-end">
                <button class="btn btn-primary" type="submit">添加</button>
            </div>
        </div>
    </form>
    <div class="muted text-sm" style="margin-top:10px">API Key 将在添加后自动生成，无需手动填写。</div>
</div>

<!-- 站点列表 -->
<div class="card">
    <div style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:16px">
        已配置站点
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Device ID</th>
                <th>工位名称</th>
                <th>目标状态</th>
                <th>API Key</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($stations)): ?>
            <tr><td colspan="7">
                <div class="empty">
                    <div class="empty-icon">⬡</div>
                    <p>尚未添加任何站点</p>
                </div>
            </td></tr>
        <?php else: ?>
        <?php foreach ($stations as $st): ?>
            <tr>
                <td class="mono muted"><?= $st['id'] ?></td>
                <td class="mono" style="color:var(--accent2)"><?= htmlspecialchars($st['device_id']) ?></td>
                <td><strong><?= htmlspecialchars($st['station_name']) ?></strong></td>
                <td><?= status_badge($st['bound_status']) ?></td>
                <td>
                    <span class="api-key" title="点击复制"
                          onclick="navigator.clipboard.writeText(this.dataset.key);this.textContent='已复制!';setTimeout(()=>this.textContent=this.dataset.display,1500)"
                          data-key="<?= htmlspecialchars($st['api_key']) ?>"
                          data-display="<?= substr($st['api_key'],0,8) ?>···">
                        <?= substr($st['api_key'],0,8) ?>···
                    </span>
                </td>
                <td class="mono muted text-sm"><?= time_fmt($st['created_at']) ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <!-- 重新生成 Key -->
                        <form method="post" onsubmit="return confirm('重新生成 API Key 后，ESP8266 固件需同步更新，确认？')">
                            <input type="hidden" name="action" value="regen_key">
                            <input type="hidden" name="id" value="<?= $st['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm">刷新Key</button>
                        </form>
                        <!-- 删除 -->
                        <form method="post" onsubmit="return confirm('确认删除站点「<?= htmlspecialchars(addslashes($st['station_name'])) ?>」？')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $st['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">删除</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ESP8266 配置参考 -->
<div class="card" style="margin-top:20px">
    <div style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:14px">
        ESP8266 固件配置参考
    </div>
    <pre style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:16px;font-family:var(--font-mono);font-size:12px;color:var(--text);overflow-x:auto;line-height:1.7">
const char* DEVICE_ID = "ESP_001";
const char* API_KEY   = "你的32位API_KEY";
const char* SERVER    = "http://&lt;你的局域网IP&gt;/rental/api.php";

// POST body (JSON):
// {"device_id":"ESP_001","api_key":"xxx","qr_code":"ITEM_A"}
    </pre>
</div>

    </main>
</div>
</body>
</html>
