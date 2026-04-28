<?php
// layout.php — 公共导航，被每个页面 include
// 调用方需先定义 $page_title 和 $active_nav

$active_nav = $active_nav ?? '';
$page_title = $page_title ?? '设备管理系统';

$nav = [
    'index'    => ['icon' => '◈',  'label' => '仪表盘',   'href' => 'index.php'],
    'items'    => ['icon' => '◉',  'label' => '设备管理', 'href' => 'items.php'],
    'stations' => ['icon' => '⬡',  'label' => '站点管理', 'href' => 'stations.php'],
    'logs'     => ['icon' => '≡',  'label' => '全局日志', 'href' => 'logs.php'],
];

function status_badge(string $s): string {
    $labels = STATUS_LABELS;
    $label = $labels[$s] ?? $s;
    return "<span class='badge badge-{$s}'>{$label}</span>";
}

function time_fmt(string $t): string {
    return date('Y-m-d H:i', strtotime($t));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?> — 设备租赁管理</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <!-- 侧边栏 -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-mark">RENTAL SYS</div>
            <h1>设备租赁管理</h1>
        </div>
        <div class="nav-section">主菜单</div>
        <?php foreach ($nav as $key => $item): ?>
        <a href="<?= $item['href'] ?>"
           class="nav-item <?= $active_nav === $key ? 'active' : '' ?>">
            <span class="nav-icon"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
        <div class="nav-section" style="margin-top:auto">接口</div>
        <a href="api.php" class="nav-item" target="_blank">
            <span class="nav-icon">⟁</span>api.php
        </a>
    </aside>
    <!-- 主内容 -->
    <main class="main">
        <div class="page-header">
            <div>
                <h2><?= htmlspecialchars($page_title) ?></h2>
                <div class="sub"><?= date('Y-m-d H:i') ?> · 局域网部署</div>
            </div>
            <?php if (!empty($header_action)) echo $header_action; ?>
        </div>
