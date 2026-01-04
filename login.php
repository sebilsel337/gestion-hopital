<?php

include 'db_connect.php';
include 'header.php';

$error = '';
$success_msg = '';

if (isset($_GET['signup']) && $_GET['signup'] == 'success') {
    $success_msg = "✅ تم إنشاء الحساب بنجاح! يمكنك الدخول الآن.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = "بيانات الدخول غير صحيحة.";
        }
    } catch (PDOException $e) {
        $error = "خطأ تقني: " . $e->getMessage();
    }
}
?>

<div class="form-container">
    <h2>تسجيل الدخول</h2>
    <?php if ($success_msg): ?><p style="color: green; background: #efe; padding: 10px;"><?php echo $success_msg; ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color: red; background: #fee; padding: 10px;"><?php echo $error; ?></p><?php endif; ?>

    <form method="post">
        <label>اسم المستخدم أو البريد:</label>
        <input type="text" name="username_or_email" required>
        <label>كلمة المرور:</label>
        <input type="password" name="password" required>
        <input type="submit" value="دخول">
    </form>
</div>

<?php include 'footer.php'; ?>