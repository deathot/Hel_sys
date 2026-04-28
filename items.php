<?php
// items.php — 设备增删查
require_once __DIR__ . '/db.php';

$page_title = '设备管理';
$active_nav = 'items';
$db = get_db();
$flash = '';

// ── 处理 POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $qr   = trim($_POST['qr_code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($qr && $name) {
            $stmt = $db->prepare(
                "INSERT INTO items (qr_code, name, status) VALUES (?, ?, 'in_stock')"
            );
            $stmt->bind_param('ss', $qr, $name);
            if ($stmt->execute()) {
                $flash = "ok|设备「{$name}」已添加";
            } else {
                $flash = "err|添加失败：二维码可能重复 ({$qr})";
            }
            $stmt->close();
        } else {
            $flash = 'err|请填写二维码和设备名称';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare('DELETE FROM items WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $flash = 'ok|设备已删除（相关日志一并移除）';
            $stmt->close();
        }
    }
}

// ── 查询 ──
$search = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? '';

$sql = 'SELECT * FROM items WHERE 1=1';
$params = [];
$types = '';

if ($search) {
    $sql .= ' AND (name LIKE ? OR qr_code LIKE ?)';
    $like = "%{$search}%";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($filter && isset(STATUS_LABELS[$filter])) {
    $sql .= ' AND status = ?';
    $params[] = $filter;
    $types .= 's';
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $db->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>
<a href="items.php" class="btn btn-primary">＋ 添加设备</a>
<?php $header_action = ob_get_clean();

require_once __DIR__ . '/layout.php';

// Flash
if ($flash) {
    [$type, $msg] = explode('|', $flash, 2);
    echo "<div class='flash flash-{$type}'>" . ($type==='ok'?'✓':'✗') . ' ' . htmlspecialchars($msg) . "</div>";
}
?>

<!-- 添加表单 -->
<div class="card" style="margin-bottom:20px">
    <div style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:14px">添加设备</div>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label>二维码内容</label>
                <input type="text" name="qr_code" placeholder="如 ITEM_A001" required>
            </div>
            <div class="form-group">
                <label>设备名称</label>
                <input type="text" name="name" placeholder="如 血压计" required>
            </div>
            <div>
                <button class="btn btn-primary" type="submit">添加</button>
            </div>
        </div>
    </form>
</div>

<!-- 筛选 -->
<div class="card" style="margin-bottom:20px">
    <form method="get" class="form-row">
        <div class="form-group">
            <label>搜索设备</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="名称 / 二维码">
        </div>
        <div class="form-group" style="max-width:180px">
            <label>状态筛选</label>
            <select name="status">
                <option value="">全部状态</option>
                <?php foreach (STATUS_LABELS as $s => $l): ?>
                <option value="<?= $s ?>" <?= $filter===$s?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end">
            <button class="btn btn-ghost" type="submit">筛选</button>
            <a href="items.php" class="btn btn-ghost">重置</a>
        </div>
    </form>
</div>

<!-- 设备列表 -->
<div class="card">
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>二维码</th>
                <th>设备名称</th>
                <th>当前状态</th>
                <th>入库时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="6">
                <div class="empty">
                    <div class="empty-icon">◎</div>
                    <p>没有找到设备</p>
                </div>
            </td></tr>
        <?php else: ?>
        <?php foreach ($items as $item): ?>
            <tr>
                <td class="mono muted"><?= $item['id'] ?></td>
                <td class="mono" style="color:var(--accent2)"><?= htmlspecialchars($item['qr_code']) ?></td>
                <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                <td><?= status_badge($item['status']) ?></td>
                <td class="mono muted text-sm"><?= time_fmt($item['created_at']) ?></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="history.php?item_id=<?= $item['id'] ?>" class="btn btn-ghost btn-sm">历史</a>
                        <form method="post" onsubmit="return confirm('确认删除「<?= htmlspecialchars(addslashes($item['name'])) ?>」？此操作不可撤销。')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
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
    <div class="muted text-sm" style="margin-top:12px;padding:0 4px">共 <?= count($items) ?> 条记录</div>
</div>

    </main>
</div>
</body>
</html>
