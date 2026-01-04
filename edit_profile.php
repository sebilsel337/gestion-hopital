<?php

include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$user_data = [];

try {
    
    $stmt = $pdo->prepare("SELECT username, email, role, specialty FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        throw new Exception("بيانات المستخدم غير موجودة.");
    }
    
    $is_doctor = ($user_data['role'] == 'doctor');

    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_email = trim($_POST['email']);
        $new_password = $_POST['new_password'];
        $current_password = $_POST['current_password'];
        $new_specialty = $is_doctor ? trim($_POST['specialty']) : NULL;
        
        
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current_password, $hash)) {
            $error = "كلمة المرور الحالية غير صحيحة. لا يمكن حفظ التغييرات.";
        } else {
            
            $update_fields = [];
            $update_params = ['id' => $user_id];
            
            
            if ($new_email != $user_data['email']) {
                $update_fields[] = "email = :email";
                $update_params['email'] = $new_email;
            }

            
            if (!empty($new_password)) {
                $update_fields[] = "password = :password";
                $update_params['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            
            if ($is_doctor) {
                if ($new_specialty != $user_data['specialty']) {
                    $update_fields[] = "specialty = :specialty";
                    $update_params['specialty'] = $new_specialty;
                }
            }

            if (!empty($update_fields)) {
                $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($update_params);
                $success = "✅ تم تحديث ملفك الشخصي بنجاح.";
                
                
                $stmt = $pdo->prepare("SELECT username, email, role, specialty FROM users WHERE id = :id");
                $stmt->execute(['id' => $user_id]);
                $user_data = $stmt->fetch();
            } else {
                $error = "لم تقم بإجراء أي تغييرات لحفظها.";
            }
        }
    }

} catch (Exception $e) {
    $error = "❌ خطأ غير متوقع: " . $e->getMessage();
}
?>

<div class="form-container">
    <h2>👤 تعديل الملف الشخصي</h2>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color: green;"><?php echo $success; ?></p><?php endif; ?>

    <form method="post">
        
        <label>اسم المستخدم (لا يمكن تغييره):</label>
        <input type="text" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
        
        <label for="email">البريد الإلكتروني:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        
        <?php if ($is_doctor): ?>
            <label for="specialty">تخصصك:</label>
            <input type="text" id="specialty" name="specialty" value="<?php echo htmlspecialchars($user_data['specialty']); ?>">
        <?php endif; ?>
        
        <h4 style="margin-top: 30px;">تغيير كلمة المرور (اختياري):</h4>
        <label for="new_password">كلمة المرور الجديدة:</label>
        <input type="password" id="new_password" name="new_password" placeholder="اتركها فارغة للإبقاء على كلمة المرور الحالية">
        
        <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
        
        <label for="current_password">كلمة المرور الحالية (مطلوبة لحفظ أي تغيير):</label>
        <input type="password" id="current_password" name="current_password" required>
        
        <input type="submit" value="حفظ التغييرات">
    </form>
</div>

<?php include 'footer.php'; ?>