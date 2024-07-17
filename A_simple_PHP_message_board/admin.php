<?php
session_start();
require 'config.php';

$adminPassword = 'admin123'; // 设置管理员密码
$isLoggedIn = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === $adminPassword) {
        $_SESSION['is_logged_in'] = true;
        $isLoggedIn = true;
    } elseif (isset($_POST['approve'])) {
        $id = (int)$_POST['id'];
        $db = getDb();
        $stmt = $db->prepare("UPDATE comments SET is_approved = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php');
        exit;
    } elseif (isset($_POST['reply'])) {
        $id = (int)$_POST['id'];
        $reply = $_POST['reply'];
        $db = getDb();
        $stmt = $db->prepare("UPDATE comments SET reply = :reply WHERE id = :id");
        $stmt->execute([':reply' => $reply, ':id' => $id]);
        header('Location: admin.php');
        exit;
    } elseif (isset($_POST['pin'])) {
        $id = (int)$_POST['id'];
        $db = getDb();
        $stmt = $db->prepare("UPDATE comments SET is_pinned = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php');
        exit;
    } elseif (isset($_POST['unpin'])) {
        $id = (int)$_POST['id'];
        $db = getDb();
        $stmt = $db->prepare("UPDATE comments SET is_pinned = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php');
        exit;
    } elseif (isset($_POST['add_announcement'])) {
        $content = $_POST['announcement'];
        $db = getDb();
        $stmt = $db->prepare("INSERT INTO announcements (content) VALUES (:content)");
        $stmt->execute([':content' => $content]);
        header('Location: admin.php');
        exit;
    } elseif (isset($_POST['delete_announcement'])) {
        $id = (int)$_POST['id'];
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db = getDb();
    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: admin.php');
    exit;
}

$comments = getDb()->query("SELECT * FROM comments ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$announcements = getDb()->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$groupedComments = [];
foreach ($comments as $comment) {
    $date = substr($comment['created_at'], 0, 10);
    if (!isset($groupedComments[$date])) {
        $groupedComments[$date] = [];
    }
    $groupedComments[$date][] = $comment;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>管理后台</title>
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
        .warning {
            color: red;
            text-align: center;
        }
        .comment-date {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
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
        .btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            border-radius: 3px;
        }
        .btn-approve {
            background: #4CAF50;
            color: #fff;
        }
        .btn-reply {
            background: #008CBA;
            color: #fff;
        }
        .btn-pin, .btn-unpin {
            background: #ff9800;
            color: #fff;
        }
        .btn-delete, .btn-delete-announcement {
            background: #f44336;
            color: #fff;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>管理后台</h1>
        </div>
    </header>
    <div class="container main">
        <?php if (!$isLoggedIn): ?>
            <form method="POST" action="">
                <label for="password">密码:</label><br>
                <input type="password" id="password" name="password" required><br>
                <input type="submit" value="登录">
            </form>
        <?php else: ?>
            <button onclick="location.reload()">刷新</button>
            <h2>添加公告</h2>
            <form method="POST" action="">
                <label for="announcement">公告内容:</label>
                <textarea id="announcement" name="announcement" required></textarea>
                <input type="submit" name="add_announcement" value="添加公告">
            </form>

            <h2>公告列表</h2>
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement">
                    <?php echo htmlspecialchars($announcement['content']); ?>
                    <em>(<?php echo $announcement['created_at']; ?>)</em>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                        <input type="submit" name="delete_announcement" value="删除公告" class="btn btn-delete-announcement">
                    </form>
                </div>
            <?php endforeach; ?>

            <h2>留言列表</h2>
            <?php foreach ($groupedComments as $date => $comments): ?>
                <div class="comment-date"><?php echo $date; ?></div>
                <ul>
                    <?php foreach ($comments as $index => $comment): ?>
                        <li class="comment">
                            <strong><?php echo htmlspecialchars($comment['name']); ?></strong>: 
                            <?php echo htmlspecialchars($comment['content']); ?>
                            <em>(<?php echo $comment['created_at']; ?>)</em>
                            <?php if ($comment['is_pinned']): ?>
                                <span class="pinned-label">[置顶]</span>
                            <?php endif; ?>
                            <?php if ($comment['is_approved'] == 0): ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <input type="submit" name="approve" value="审核通过" class="btn btn-approve">
                                </form>
                            <?php endif; ?>
                            <?php if (!empty($comment['reply'])): ?>
                                <br><strong>管理员回复:</strong> <?php echo htmlspecialchars($comment['reply']); ?>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <label for="reply">回复:</label>
                                    <textarea name="reply" required></textarea>
                                    <input type="submit" value="回复" class="btn btn-reply">
                                </form>
                            <?php endif; ?>
                            <?php if ($comment['is_pinned']): ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <input type="submit" name="unpin" value="取消置顶" class="btn btn-unpin">
                                </form>
                            <?php else: ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <input type="submit" name="pin" value="置顶" class="btn btn-pin">
                                </form>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $comment['id']; ?>" class="btn btn-delete">删除</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
