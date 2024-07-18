<?php
session_start();
require 'config.php';

$warning = '';
$success = false;
$perPage = 20; // 每页显示的留言数量

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

// 获取当前页码
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // 页码不能小于1

// 计算偏移量
$offset = ($page - 1) * $perPage;

// 从数据库中获取留言
$comments = getDb()->query("SELECT * FROM comments WHERE is_approved = 1 ORDER BY is_pinned DESC, created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);

// 获取总留言数
$totalComments = getDb()->query("SELECT COUNT(*) FROM comments WHERE is_approved = 1")->fetchColumn();
$totalPages = ceil($totalComments / $perPage);

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
            text-align: left;
        }
        form {
            margin-bottom: 20px;
        }
        label, textarea, input {
            display: block;
            margin-bottom: 5px;
            padding: 5px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin-bottom: 10px;
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
            border-radius: 0px;
            margin-bottom: 2px;
        }
        .pinned-label {
            color: red;
            font-weight: bold;
        }
        .comment:nth-child(odd) {
            background-color: #cfcfcf;
        }
        .comment:nth-child(even) {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>留言板</h1>
        <!-- 留言表单省略 -->

        <h2>站长公告</h2>
        <hr style="width: 100%;">
        <?php foreach ($announcements as $announcement): ?>
            <div class="announcement">
                <?php echo htmlspecialchars($announcement['content']); ?>
                <em>(<?php echo date("Y-m-d H:i:s", strtotime($announcement['created_at']." +8 hours")); ?>)</em>
            </div>
        <?php endforeach; ?>

        <h2>留言列表</h2>
        <hr style="width: 100%;">
        <ul>
            <?php foreach ($comments as $comment): ?>
                <li class="comment">
                    <strong><?php echo htmlspecialchars($comment['name']); ?></strong>:
                    <?php echo htmlspecialchars($comment['content']); ?>
                    <em>(<?php echo date("Y-m-d H:i:s", strtotime($comment['created_at']." +8 hours")); ?>)</em>
                    <?php if ($comment['is_pinned']): ?>
                        <span class="pinned-label">[置顶]</span>
                    <?php endif; ?>
                    <?php if (!empty($comment['reply'])): ?>
                        <br>[站长回复]: <?php echo htmlspecialchars($comment['reply']); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">上一页</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>">下一页</a>
            <?php endif; ?>
        </div>

        <?php if ($warning): ?>
            <p class="warning"><?php echo $warning; ?></p>
        <?php elseif ($success): ?>
            <p class="success">提交成功，请等待审核。</p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="name">昵称:</label>
            <input type="text" id="name" name="name" required style="width: 120px; height: 20px;">
            <label for="content">内容:</label>
            <textarea id="content" name="content" required style="width: 480px; height: 70px;"></textarea>
            <input type="submit" value="提交" style="margin-top: 20px; margin-bottom: 50px; width: 100px; height: 35px;">
        </form>
    </div>
</body>
</html>
