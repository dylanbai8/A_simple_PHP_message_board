<?php
$adminPassword = 'admin123'; // 设置管理员密码
$breakTime = '0'; // 秒 重复留言间隔时间
$perPage = 3; // 每页显示的留言数量

function getDb() {
    $db = new PDO('sqlite:messages.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        content TEXT,
        reply TEXT,
        is_approved INTEGER DEFAULT 0,
        is_pinned INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    return $db;
}
?>
