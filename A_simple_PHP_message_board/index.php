<?php
session_start();
require 'config.php';

$warning = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $content = $_POST['content'];
    $lastPosted = isset($_SESSION['last_posted']) ? $_SESSION['last_posted'] : null;

    if ($lastPosted && (time() - $lastPosted) < 300) {
        $warning = '请等待5分钟后再留言。';
    } else {
        if (!empty($name) && !empty($content)) {
            $db = getDb();
            $stmt = $db->prepare("INSERT INTO comments (name, content) VALUES (:name, :content)");
            $stmt->execute([':name' => $name, ':content' => $content]);
            $_SESSION['last_posted'] = time();
            $success = true;
        }
    }
}

$comments = getDb()->query("SELECT * FROM comments WHERE is_approved = 1 ORDER BY is_pinned DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$announcements = getDb()->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>留言板</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }
        header {
            background: #333;
            color: #fff;
            padding-top: 30px;
            min-height: 70px;
            border-bottom: #77aaff 3px solid;
        }
        header a {
            color: #fff;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 16px;
        }
        header ul {
            padding: 0;
            list-style: none;
        }
        header li {
            float: left;
            display: inline;
            padding: 0 20px 0 20px;
        }
        .main {
            padding: 15px;
            background: #fff;
            border-radius: 5px;
            margin-top: 20px;
        }
        h1, h2 {
            text-align: center;
        }
        form {
            margin-bottom: 20px;
        }
        label, textarea, input {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            padding: 10px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
        }
        .warning, .success {
            text-align: center;
        }
        .warning {
            color: red;
        }
        .success {
            color: green;
        }
        .announcement {
            background: #ffeeba;
            padding: 10px;
            border: 1px solid #ffeeba;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .pinned-label {
            color: red;
            font-weight: bold;
        }
        .comment:nth-child(odd) {
            background-color: #f9f9f9;
        }
        .comment:nth-child(even) {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>留言板</h1>
        </div>
    </header>
    <div class="container main">
        <?php if ($warning): ?>
            <p class="warning"><?php echo $warning; ?></p>
        <?php elseif ($success): ?>
            <p class="success">提交成功，请等待审核。</p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="name">姓名:</label><br>
            <input type="text" id="name" name="name" required><br>
            <label for="content">内容:</label><br>
            <textarea id="content" name="content" required></textarea><br>
            <input type="submit" value="提交">
        </form>
        <button onclick="location.reload()">刷新</button>

        <h2>公告</h2>
        <?php foreach ($announcements as $announcement): ?>
            <div class="announcement">
                <?php echo htmlspecialchars($announcement['content']); ?>
                <em>(<?php echo $announcement['created_at']; ?>)</em>
            </div>
        <?php endforeach; ?>

        <h2>留言列表</h2>
        <ul>
            <?php foreach ($comments as $index => $comment): ?>
                <li class="comment">
                    <strong><?php echo htmlspecialchars($comment['name']); ?></strong>: 
                    <?php echo htmlspecialchars($comment['content']); ?>
                    <em>(<?php echo $comment['created_at']; ?>)</em>
                    <?php if ($comment['is_pinned']): ?>
                        <span class="pinned-label">[置顶]</span>
                    <?php endif; ?>
                    <?php if (!empty($comment['reply'])): ?>
                        <br><strong>管理员回复:</strong> <?php echo htmlspecialchars($comment['reply']); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
