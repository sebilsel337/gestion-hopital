<?php

include 'db_connect.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $specialty = ($role == 'doctor' && !empty($_POST['specialty'])) ? trim($_POST['specialty']) : NULL;

    if (empty($username) || empty($email) || empty($password)) {
        $error = "الرجاء تعبئة كافة الحقول.";
    } else {
        try {
           
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->execute([$username, $email]);
            
            if ($stmt_check->rowCount() > 0) {
                $error = "المستخدم أو البريد مسجل مسبقاً.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (username, email, password, role, specialty) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $email, $hashed_password, $role, $specialty]);

                
                header("Location: login.php?signup=success");
                exit; 
            }
        } catch (PDOException $e) {
            $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
        }
    }
}
include 'header.php'; 
?>

<div class="form-container">
    <h2>إنشاء حساب جديد</h2>
    <?php if ($error): ?><p style="color: red; background: #fee; padding: 10px;"><?php echo $error; ?></p><?php endif; ?>

    <form method="post">
        <label>نوع الحساب:</label>
        <select name="role" id="user_role" onchange="document.getElementById('spec_area').style.display=(this.value=='doctor'?'block':'none')">
            <option value="patient">مريض</option>
            <option value="doctor">طبيب</option>
        </select>
        
        <div id="spec_area" style="display:none; margin-top:10px;">
            <label>التخصص:</label>
            <input type="text" name="specialty">
        </div>

        <label>اسم المستخدم:</label>
        <input type="text" name="username" required>

        <label>البريد الإلكتروني:</label>
        <input type="email" name="email" required>

        <label>كلمة المرور:</label>
        <input type="password" name="password" required>

        <input type="submit" value="إنشاء الحساب">
    </form>
</div>

<?php include 'footer.php'; ?>