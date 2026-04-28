<?php
// index.php — 仪表盘
require_once __DIR__ . '/db.php';

$page_title  = '仪表盘';
$active_nav  = 'index';
$db = get_db();

// 各状态数量
$counts = [];
foreach (array_keys(STATUS_LABELS) as $s) $counts[$s] = 0;
$res = $db->query('SELECT status, COUNT(*) AS n FROM items GROUP BY status');
while ($r = $res->fetch_assoc()) $counts[$r['status']] = (int)$r['n'];
$total = array_sum($counts);

// 今日操作次数
$today_logs = $db->query(
    "SELECT COUNT(*) AS n FROM logs WHERE DATE(scanned_at) = CURDATE()"
)->fetch_assoc()['n'];

// 最近 10 条日志
$recent = $db->query(
    "SELECT l.scanned_at, l.from_status, l.to_status,
            i.name AS item_name, s.station_name
     FROM logs l
     LEFT JOIN items    i ON i.id = l.item_id
     LEFT JOIN stations s ON s.id = l.station_id
     ORDER BY l.scanned_at DESC LIMIT 10"
);

$accent_map = [
    'in_stock'    => 'var(--c-in_stock)',
    'rented'      => 'var(--c-rented)',
    'returned'    => 'var(--c-returned)',
    'inspected'   => 'var(--c-inspected)',
    'disinfected' => 'var(--c-disinfected)',
];

require_once __DIR__ . '/layout.php';
?>

<!-- 统计卡片 -->
<div class="stats-grid">
    <div class="stat-card" style="--accent-color:var(--accent)">
        <div class="stat-label">设备总数</div>
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-sub">全部在库设备</div>
    </div>
    <?php foreach (STATUS_LABELS as $s => $label):
        $color = $accent_map[$s];
    ?>
    <div class="stat-card" style="--accent-color:<?= $color ?>">
        <div class="stat-label"><?= $label ?></div>
        <div class="stat-value"><?= $counts[$s] ?></div>
        <div class="stat-sub"><?= $total > 0 ? round($counts[$s]/$total*100) : 0 ?>% 占比</div>
    </div>
    <?php endforeach; ?>
    <div class="stat-card" style="--accent-color:var(--accent2)">
        <div class="stat-label">今日操作</div>
        <div class="stat-value"><?= $today_logs ?></div>
        <div class="stat-sub">扫码次数</div>
    </div>
</div>

<!-- 状态流转图示 -->
<div class="card" style="margin-bottom:24px">
    <div style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:14px">
        状态流转
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <?php
        $flow = ['in_stock','rented','returned','inspected','disinfected'];
        foreach ($flow as $i => $s):
        ?>
        <?= status_badge($s) ?>
        <?php if ($i < count($flow)-1): ?>
            <span style="color:var(--muted);font-size:12px">→</span>
        <?php endif; ?>
        <?php endforeach; ?>
        <span style="color:var(--muted);font-size:12px">→ ↩ 已入库</span>
    </div>
</div>

<!-- 最近日志 -->
<div class="card">
    <div style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:16px">
        最近操作记录
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>时间</th>
                <th>设备</th>
                <th>工位</th>
                <th>变更</th>
            </tr>
        </thead>
        <tbody>
        <?php $rows = $recent->fetch_all(MYSQLI_ASSOC); ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:32px">暂无记录</td></tr>
        <?php else: ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="mono muted text-sm"><?= time_fmt($r['scanned_at']) ?></td>
                <td><?= htmlspecialchars($r['item_name'] ?? '—') ?></td>
                <td class="muted"><?= htmlspecialchars($r['station_name'] ?? '—') ?></td>
                <td>
                    <?= status_badge($r['from_status']) ?>
                    <span style="color:var(--muted);margin:0 4px;font-size:12px">→</span>
                    <?= status_badge($r['to_status']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    <div style="margin-top:12px">
        <a href="logs.php" class="btn btn-ghost btn-sm">查看全部日志 →</a>
    </div>
</div>

    </main>
</div>
</body>
</html>
