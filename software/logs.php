<?php
// logs.php — 全局日志，可筛选
require_once __DIR__ . '/db.php';

$page_title = '全局日志';
$active_nav = 'logs';
$db = get_db();

// ── 筛选参数 ──
$f_item    = (int)($_GET['item_id']    ?? 0);
$f_station = (int)($_GET['station_id'] ?? 0);
$f_status  = trim($_GET['status']      ?? '');
$f_date    = trim($_GET['date']        ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 50;

// 所有站点（用于下拉）
$all_stations = $db->query('SELECT id, station_name FROM stations ORDER BY station_name')
    ->fetch_all(MYSQLI_ASSOC);

// 所有设备（用于下拉）
$all_items = $db->query('SELECT id, name FROM items ORDER BY name')
    ->fetch_all(MYSQLI_ASSOC);

// ── 构建查询 ──
$where  = ['1=1'];
$params = [];
$types  = '';

if ($f_item) {
    $where[] = 'l.item_id = ?';
    $params[] = $f_item; $types .= 'i';
}
if ($f_station) {
    $where[] = 'l.station_id = ?';
    $params[] = $f_station; $types .= 'i';
}
if ($f_status && isset(STATUS_LABELS[$f_status])) {
    $where[] = 'l.to_status = ?';
    $params[] = $f_status; $types .= 's';
}
if ($f_date) {
    $where[] = 'DATE(l.scanned_at) = ?';
    $params[] = $f_date; $types .= 's';
}

$where_sql = implode(' AND ', $where);

// 总数
$count_sql = "SELECT COUNT(*) AS n FROM logs l WHERE {$where_sql}";
$stmt = $db->prepare($count_sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['n'];
$stmt->close();

$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// 日志列表
$sql = "SELECT l.id, l.scanned_at, l.from_status, l.to_status,
               i.name AS item_name, i.id AS item_id_val,
               s.station_name
        FROM logs l
        LEFT JOIN items    i ON i.id = l.item_id
        LEFT JOIN stations s ON s.id = l.station_id
        WHERE {$where_sql}
        ORDER BY l.scanned_at DESC
        LIMIT ? OFFSET ?";
$params[] = $per_page; $types .= 'i';
$params[] = $offset;   $types .= 'i';

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 分页 URL 辅助
function page_url(int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    return 'logs.php?' . http_build_query($q);
}

require_once __DIR__ . '/layout.php';
?>

<!-- 筛选栏 -->
<div class="card" style="margin-bottom:20px">
    <form method="get" class="form-row">
        <div class="form-group" style="max-width:180px">
            <label>设备</label>
            <select name="item_id">
                <option value="">全部设备</option>
                <?php foreach ($all_items as $it): ?>
                <option value="<?= $it['id'] ?>" <?= $f_item===$it['id']?'selected':'' ?>>
                    <?= htmlspecialchars($it['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="max-width:180px">
            <label>站点</label>
            <select name="station_id">
                <option value="">全部站点</option>
                <?php foreach ($all_stations as $st): ?>
                <option value="<?= $st['id'] ?>" <?= $f_station===$st['id']?'selected':'' ?>>
                    <?= htmlspecialchars($st['station_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="max-width:160px">
            <label>目标状态</label>
            <select name="status">
                <option value="">全部状态</option>
                <?php foreach (STATUS_LABELS as $s => $l): ?>
                <option value="<?= $s ?>" <?= $f_status===$s?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="max-width:160px">
            <label>日期</label>
            <input type="date" name="date" value="<?= htmlspecialchars($f_date) ?>">
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end">
            <button class="btn btn-ghost" type="submit">筛选</button>
            <a href="logs.php" class="btn btn-ghost">重置</a>
        </div>
    </form>
</div>

<!-- 日志表 -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <div style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)">
            操作日志
        </div>
        <div class="muted text-sm mono">共 <?= $total ?> 条 · 第 <?= $page ?>/<?= $total_pages ?> 页</div>
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>时间</th>
                <th>设备</th>
                <th>工位</th>
                <th>变更前</th>
                <th>变更后</th>
                <th>日志ID</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($logs)): ?>
            <tr><td colspan="6">
                <div class="empty">
                    <div class="empty-icon">≡</div>
                    <p>没有符合条件的日志</p>
                </div>
            </td></tr>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td class="mono muted text-sm"><?= time_fmt($log['scanned_at']) ?></td>
                <td>
                    <a href="history.php?item_id=<?= $log['item_id_val'] ?>"
                       style="color:var(--accent);text-decoration:none;font-weight:600">
                        <?= htmlspecialchars($log['item_name'] ?? '—') ?>
                    </a>
                </td>
                <td class="muted"><?= htmlspecialchars($log['station_name'] ?? '—') ?></td>
                <td><?= status_badge($log['from_status']) ?></td>
                <td><?= status_badge($log['to_status']) ?></td>
                <td class="mono muted text-sm">#<?= $log['id'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- 分页 -->
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;gap:6px;margin-top:16px;align-items:center;flex-wrap:wrap">
        <?php if ($page > 1): ?>
            <a href="<?= page_url(1) ?>" class="btn btn-ghost btn-sm">« 首页</a>
            <a href="<?= page_url($page-1) ?>" class="btn btn-ghost btn-sm">‹ 上页</a>
        <?php endif; ?>
        <?php
        $start = max(1, $page-2);
        $end   = min($total_pages, $page+2);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a href="<?= page_url($i) ?>"
               class="btn btn-sm <?= $i===$page ? 'btn-primary' : 'btn-ghost' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="<?= page_url($page+1) ?>" class="btn btn-ghost btn-sm">下页 ›</a>
            <a href="<?= page_url($total_pages) ?>" class="btn btn-ghost btn-sm">末页 »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

    </main>
</div>
</body>
</html>
