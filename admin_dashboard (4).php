<?php
// admin_dashboard.php
include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$error = '';
$users = [];
$appointments = [];

try {
    // 1. جلب إجمالي المستخدمين
    $stmt_users = $pdo->query("SELECT id, username, email, role, specialty, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt_users->fetchAll();

    // 2. جلب آخر 10 مواعيد (الكل)
    $sql_appts = "SELECT a.*, p.username AS patient_username, d.username AS doctor_username 
                  FROM appointments a 
                  JOIN users p ON a.patient_id = p.id
                  JOIN users d ON a.doctor_id = d.id
                  ORDER BY a.created_at DESC LIMIT 10";
    $stmt_appts = $pdo->query($sql_appts);
    $appointments = $stmt_appts->fetchAll();

    // 3. معالجة حذف مستخدم (اختياري)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user_id'])) {
        $del_id = (int)$_POST['delete_user_id'];
        
        // منع حذف المدير الحالي
        if ($del_id == $_SESSION['user_id']) {
            $error = "لا يمكنك حذف حساب المدير الذي تستخدمه حالياً.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            if ($stmt->execute(['id' => $del_id])) {
                $success = "✅ تم حذف المستخدم بنجاح.";
                header("Location: admin_dashboard.php");
                exit;
            } else {
                $error = "❌ فشل حذف المستخدم.";
            }
        }
    }

} catch (PDOException $e) {
    $error = "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<div class="dashboard-card">
    <h2>⚙️ لوحة تحكم المدير</h2>
    <p>هنا يمكنك الإشراف على جميع مستخدمي النظام والمواعيد.</p>
    
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if (isset($success)): ?><p style="color: green;"><?php echo $success; ?></p><?php endif; ?>

    <a href="index.php" class="button" style="width: 250px; background-color: #333; margin-bottom: 20px;">عرض الإحصائيات الرئيسية</a>

    <div class="dashboard-card">
        <h3>جميع مستخدمي النظام (<?php echo count($users); ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>اسم المستخدم</th>
                    <th>البريد</th>
                    <th>الدور</th>
                    <th>التخصص</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo translate_role($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['specialty'] ?? '-'); ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="post" style="display: inline-block;">
                                    <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="status-button status-cancelled" style="width: auto; padding: 5px 10px;" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ سيتم حذف جميع بياناته.');">
                                        حذف
                                    </button>
                                </form>
                            <?php else: ?>
                                (أنت)
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="dashboard-card">
        <h3>آخر 10 مواعيد في النظام</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>المريض</th>
                    <th>الطبيب</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?php echo $appt['id']; ?></td>
                        <td><?php echo htmlspecialchars($appt['patient_username']); ?></td>
                        <td><?php echo htmlspecialchars($appt['doctor_username']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($appt['appointment_date'])); ?></td>
                        <td>
                             <span class="status-button status-<?php echo strtolower($appt['status']); ?>" style="padding: 5px 10px; width: auto; font-size: 0.9em;">
                                <?php echo htmlspecialchars($appt['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>