<?php
session_start();
require 'config.php';

$warning = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name']) && isset($_POST['content'])) {
    $name = $_POST['name'];
    $content = $_POST['content'];
    $lastPosted = isset($_SESSION['last_posted']) ? $_SESSION['last_posted'] : null;

    if ($lastPosted && (time() - $lastPosted) < $breakTime) {
        $warning = '请等待'.($breakTime/60).'分钟后再留言。';
    } else {
        if (!empty($name) && !empty($content)) {
            $db = getDb();
            $stmt = $db->prepare("INSERT INTO comments (name, content) VALUES (:name, :content)");
            $stmt->execute([':name' => $name, ':content' => $content]);
            $_SESSION['last_posted'] = time();
            $success = true;
            // 防止重复提交，重定向到同一页面
            header('Location: index.php');
            exit;
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
    <title>留言板-建议和反馈</title>
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
            height: 66px;
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
            margin-bottom: 60px;
        }
        .pinned-label {
            color: red;
            font-weight: bold;
        }
        .comment:nth-child(odd) {
            background-color: #f1f1f1;
        }
        .comment:nth-child(even) {
            background-color: #ffffff;
        }
        .btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            border-radius: 3px;
            height: 28px;
        }
        .btn-delete, .btn-delete-announcement {
            background: #adadad;
            color: #fff;
            text-decoration:none;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h3><a href="./index.php" style="text-decoration: none; color: #fff;">留言板 --建议和反馈</a></h3>
        </div>
    </header>
    <div class="container main">
        <?php if ($warning): ?>
            <p class="warning"><?php echo $warning; ?></p>
        <?php elseif ($success): ?>
            <p class="success">提交成功，请等待审核。</p>
        <?php endif; ?>

        <h3>站长公告</h3>
        <hr style="width: 100%;">
        <?php foreach ($announcements as $announcement): ?>
            <div class="announcement">
                <?php echo htmlspecialchars($announcement['content']); ?>
                <em style="color: #969696;">(<?php echo date("Y-m-d H:i:s", strtotime($announcement['created_at']." +8 hours")); ?>)</em>
            </div>
        <?php endforeach; ?>

        <h3 style="display: inline;">留言精选</h3>
        <span style="float:right;">[第<?php echo $page; ?>页] <a class="btn btn-delete" onclick="location.reload()">刷新</a></span>
        <hr style="width: 100%;">
        <ul>
            <?php foreach ($comments as $comment): ?>
                <li class="comment">
                    <strong>[访客]: <?php echo htmlspecialchars($comment['name']); ?></strong>
                    <em style="color: #969696;"><?php echo date("Y-m-d H:i:s", strtotime($comment['created_at']." +8 hours")); ?></em>
                    <?php if ($comment['is_pinned']): ?>
                        <span class="pinned-label">[置顶]</span>
                    <?php endif; ?>
                    <br>[留言]: <?php echo htmlspecialchars($comment['content']); ?>
                    <?php if (!empty($comment['reply'])): ?>
                        <br>[站长回复]: <?php echo htmlspecialchars($comment['reply']); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

            <div style="text-align: center; margin-bottom: 45px; margin-top: 30px;">

                <?php if ($page == 1): ?>
                    <a class="btn btn-delete">上一页</a> 
                <?php endif; ?>

                <?php if ($page > 1): ?>
                    <a class="btn btn-delete" href="?page=<?php echo $page - 1; ?>">上一页</a> 
                <?php endif; ?>

                    <a class="btn btn-delete" href="?page=<?php echo $page; ?>">第<?php echo $page; ?>页</a> 

                <?php if ($page < $totalPages): ?>
                    <a class="btn btn-delete" href="?page=<?php echo $page + 1; ?>">下一页</a>
                <?php endif; ?>

                <?php if ($page == $totalPages): ?>
                    <a class="btn btn-delete">下一页</a>
                <?php endif; ?>

            </div>

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
