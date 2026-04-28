<?php
// history.php?item_id=X — 单设备流转时间线
require_once __DIR__ . '/db.php';

$db = get_db();
$item_id = (int)($_GET['item_id'] ?? 0);

// 查设备信息
$item = null;
if ($item_id) {
    $stmt = $db->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$item) {
    // 没有指定或找不到时，跳转到 items.php
    header('Location: items.php');
    exit;
}

$page_title = '设备历史 · ' . $item['name'];
$active_nav = 'items';

// 查日志
$stmt = $db->prepare(
    "SELECT l.*, s.station_name
     FROM logs l
     LEFT JOIN stations s ON s.id = l.station_id
     WHERE l.item_id = ?
     ORDER BY l.scanned_at DESC"
);
$stmt->bind_param('i', $item_id);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 状态颜色
$color_map = [
    'in_stock'    => 'var(--c-in_stock)',
    'rented'      => 'var(--c-rented)',
    'returned'    => 'var(--c-returned)',
    'inspected'   => 'var(--c-inspected)',
    'disinfected' => 'var(--c-disinfected)',
];

ob_start();
?>
<a href="items.php" class="btn btn-ghost">← 返回设备列表</a>
<?php $header_action = ob_get_clean();

require_once __DIR__ . '/layout.php';
?>

<!-- 设备信息卡 -->
<div class="card" style="margin-bottom:24px;display:flex;gap:24px;align-items:center;flex-wrap:wrap">
    <div>
        <div class="muted text-sm" style="margin-bottom:4px">设备名称</div>
        <div style="font-size:20px;font-weight:700"><?= htmlspecialchars($item['name']) ?></div>
    </div>
    <div>
        <div class="muted text-sm" style="margin-bottom:4px">二维码</div>
        <div class="mono" style="color:var(--accent2);font-size:15px"><?= htmlspecialchars($item['qr_code']) ?></div>
    </div>
    <div>
        <div class="muted text-sm" style="margin-bottom:4px">当前状态</div>
        <?= status_badge($item['status']) ?>
    </div>
    <div>
        <div class="muted text-sm" style="margin-bottom:4px">操作次数</div>
        <div class="mono" style="font-size:18px;font-weight:600"><?= count($logs) ?></div>
    </div>
    <div>
        <div class="muted text-sm" style="margin-bottom:4px">入库时间</div>
        <div class="mono text-sm"><?= time_fmt($item['created_at']) ?></div>
    </div>
</div>

<!-- 时间线 -->
<?php if (empty($logs)): ?>
<div class="card">
    <div class="empty">
        <div class="empty-icon">≡</div>
        <p>该设备暂无操作记录</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:20px">
        操作时间线（最新在前）
    </div>
    <div class="timeline">
    <?php foreach ($logs as $log):
        $color = $color_map[$log['to_status']] ?? 'var(--accent)';
    ?>
        <div class="tl-item">
            <div class="tl-dot" style="background:<?= $color ?>"></div>
            <div class="tl-time">
                <?= time_fmt($log['scanned_at']) ?>
                <?php if ($log['station_name']): ?>
                · <span><?= htmlspecialchars($log['station_name']) ?></span>
                <?php endif; ?>
            </div>
            <div class="tl-body">
                <?= status_badge($log['from_status']) ?>
                <span class="tl-arrow">→</span>
                <?= status_badge($log['to_status']) ?>
                <span class="muted text-sm" style="margin-left:8px">
                    <?= STATUS_LABELS[$log['from_status']] ?? $log['from_status'] ?>
                    →
                    <?= STATUS_LABELS[$log['to_status']] ?? $log['to_status'] ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

    </main>
</div>
</body>
</html>
