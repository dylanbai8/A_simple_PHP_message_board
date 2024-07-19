<?php
session_start();
require 'config.php';

$isLoggedIn = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'];

// 获取当前页码
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // 页码不能小于1

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === $adminPassword) {
        $_SESSION['is_logged_in'] = true;
        $isLoggedIn = true;
    } elseif (isset($_POST['approve'])) {
        $id = (int)$_POST['id'];
        $db = getDb();
        $stmt = $db->prepare("UPDATE comments SET is_approved = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?page='.$page);
        exit;
    } elseif (isset($_POST['reply'])) {
        $id = (int)$_POST['id'];
        $reply = $_POST['reply'];
        $db = getDb();
        $stmt = $db->prepare("UPDATE comments SET reply = :reply WHERE id = :id");
        $stmt->execute([':reply' => $reply, ':id' => $id]);
        header('Location: admin.php?page='.$page);
        exit;
    } elseif (isset($_POST['pin'])) {
        $id = (int)$_POST['id'];
        $db = getDb();
        $stmt = $db->prepare("UPDATE comments SET is_pinned = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?page='.$page);
        exit;
    } elseif (isset($_POST['unpin'])) {
        $id = (int)$_POST['id'];
        $db = getDb();
        $stmt = $db->prepare("UPDATE comments SET is_pinned = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?page='.$page);
        exit;
    } elseif (isset($_POST['add_announcement'])) {
        $content = $_POST['announcement'];
        $db = getDb();
        $stmt = $db->prepare("INSERT INTO announcements (content) VALUES (:content)");
        $stmt->execute([':content' => $content]);
        header('Location: admin.php?page='.$page);
        exit;
    } elseif (isset($_POST['delete_announcement'])) {
        $id = (int)$_POST['id'];
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: admin.php?page='.$page);
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db = getDb();
    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: admin.php?page='.$page);
    exit;
}

// 计算偏移量
$offset = ($page - 1) * $perPage;

// 从数据库中获取留言
$comments = getDb()->query("SELECT * FROM comments ORDER BY is_pinned DESC, created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);

// 获取总留言数
$totalComments = getDb()->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalPages = ceil($totalComments / $perPage);

// 从数据库中获取公告
$announcements = getDb()->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            height: 66px;
        }
        .main {
            padding: 15px;
            background: #fff;
            border-radius: 5px;
            margin-top: 20px;
        }
        form {
            margin-bottom: 0px;
        }
        label, textarea, input {
            display: block;
            margin-bottom: 0px;
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
        .announcement {
            background: #ffeeba;
            padding: 10px;
            border: 1px solid #ffeeba;
            border-radius: 0px;
            margin-bottom: 2px;
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
        .btn-approve {
            background: #000000;
            color: #fff;
        }
        .btn-reply {
            background: #adadad;
            color: #fff;
        }
        .btn-pin, .btn-unpin {
            background: #adadad;
            color: #fff;
        }
        .btn-delete, .btn-delete-announcement {
            background: #adadad;
            color: #fff;
            text-decoration:none;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        textarea {
            max-width: 100%;
            width: 100%;
            height: 70px;
            box-sizing: border-box;
        }
        @media (min-width: 600px) {
            textarea {
                width: 480px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h3><a href="./admin.php" style="text-decoration: none; color: #fff;">管理后台</a></h3>
        </div>
    </header>
    <div class="container main">
        <?php if (!$isLoggedIn): ?>
            <div style="display: flex; justify-content: center;">
            <form method="POST" action="">
                <label style="text-align: center;" for="password">管理员登录</label>
                <input style="text-align: center; width: 160px;" type="password" id="password" name="password" required>
                <input style="margin-top: 10px; margin-bottom: 15px; width: 172px;" type="submit" value="登录">
            </form>
            </div>
        <?php else: ?>
            <h3>公告管理</h3>
            <hr style="width: 100%;">
            <ul>
                <?php foreach ($announcements as $announcement): ?>
                    <li>
                        <?php echo htmlspecialchars($announcement['content']); ?>
                        <em style="color: #969696;">(<?php echo date("Y-m-d H:i:s", strtotime($announcement['created_at']." +8 hours")); ?>)</em>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                            <input type="submit" name="delete_announcement" value="删除" class="btn btn-delete-announcement">
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
            <ul><li>
            <form method="POST" action="">
                <textarea name="announcement" required></textarea>
                <input type="submit" name="add_announcement" value="发布公告" class="btn" style="background: #e2e2e2;">
            </form>
            </li></ul>
            <div style="margin-bottom: 38px;"></div>
            <h3 style="display: inline;">留言管理</h3> [<?php echo $perPage.'条/'.$totalComments.'条'; ?>]
            <span style="float:right;">[第<?php echo $page; ?>页] <a class="btn btn-delete" onclick="location.reload()">刷新</a></span>
            <hr style="width: 100%;">
            <ul>
                <?php foreach ($comments as $comment): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($comment['name']); ?></strong>:
                        <?php echo htmlspecialchars($comment['content']); ?>
                        <em style="color: #969696;">(<?php echo date("Y-m-d H:i:s", strtotime($comment['created_at']." +8 hours")); ?>)</em>
                        <?php if (!empty($comment['reply'])) {echo "<br>[站长回复]: ".htmlspecialchars($comment['reply']);} ?>

                        <div class="action-buttons">
                            <?php if (!$comment['is_approved']): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <input type="submit" name="approve" value="审核" class="btn btn-approve">
                                </form>
                            <?php endif; ?>
                            <?php if ($comment['is_pinned']): ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <input type="submit" name="unpin" value="取消置顶" class="btn btn-unpin" style="background: #000000;">
                                </form>
                            <?php else: ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <input type="submit" name="pin" value="置顶" class="btn btn-pin">
                                </form>
                            <?php endif; ?>
                            <a href="?page=<?php echo $page; ?>&delete=<?php echo $comment['id']; ?>" class="btn btn-delete" style="height: 20px; padding-top: 3px;">删除</a>
                        </div>
                            <form method="POST" action="">
                                <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                <label for="reply" style="display: none;"></label>
                                <textarea name="reply" required style="width: 480px; height: 70px;"></textarea>
                                <input type="submit" value="回复" class="btn btn-reply">
                            </form>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div style="text-align: center;">

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

        <?php endif; ?>
        <div style="margin-bottom: 15px;"></div>
    </div>
</body>
</html>
