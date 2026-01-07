<?php

include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$upcoming_appts = [];
$error = '';

try {
    if ($role == 'admin') {
    
        $stmt = $pdo->query("SELECT COUNT(*) as total_users, 
                                    (SELECT COUNT(*) FROM appointments WHERE status = 'Pending') as pending_appts,
                                    (SELECT COUNT(*) FROM users WHERE role = 'doctor') as total_doctors
                                    FROM users");
        $stats = $stmt->fetch();

    } elseif ($role == 'doctor') {
       
        $sql = "SELECT a.*, u.username AS patient_username FROM appointments a JOIN users u ON a.patient_id = u.id WHERE a.doctor_id = :id AND a.status = 'Pending' ORDER BY a.appointment_date ASC LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $user_id]);
        $upcoming_appts = $stmt->fetchAll();

    } else { 
        
        $sql = "SELECT a.*, d.username AS doctor_username, d.specialty FROM appointments a JOIN users d ON a.doctor_id = d.id WHERE a.patient_id = :id ORDER BY a.appointment_date DESC LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $user_id]);
        $upcoming_appts = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    $error = "❌ خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<div class="dashboard-card">
    <h2>مرحباً بك يا <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>أنت مسجل كـ <strong><?php echo translate_role($role); ?></strong> في النظام.</p>
    
    <?php if ($role == 'doctor'): ?>
        <a href="view_appointments.php" class="button" style="width: 250px; margin-top: 15px;">عرض جميع المواعيد</a>
        <a href="doctor_availability.php" class="button" style="width: 250px; margin-top: 15px; background-color: #17a2b8;">تحديد الإتاحة</a>
    <?php elseif ($role == 'patient'): ?>
        <a href="book_appointment.php" class="button" style="width: 250px; margin-top: 15px;">حجز موعد جديد</a>
    <?php elseif ($role == 'admin'): ?>
        <a href="admin_dashboard.php" class="button" style="width: 250px; margin-top: 15px; background-color: #dc3545;">إدارة النظام</a>
    <?php endif; ?>
</div>

<?php if ($role == 'admin' && isset($stats)): ?>
    <div class="dashboard-card" style="display: flex; justify-content: space-around;">
        <div style="text-align: center;">
            <h3><?php echo $stats['total_users']; ?></h3>
            <p>إجمالي المستخدمين</p>
        </div>
        <div style="text-align: center;">
            <h3 style="color: #28a745;"><?php echo $stats['total_doctors']; ?></h3>
            <p>الأطباء المسجلون</p>
        </div>
        <div style="text-align: center;">
            <h3 style="color: #ffc107;"><?php echo $stats['pending_appts']; ?></h3>
            <p>مواعيد قيد الانتظار</p>
        </div>
    </div>
<?php endif; ?>

<?php if ($role != 'admin'): ?>
    <div class="dashboard-card">
        <h3>
            <?php echo $role == 'doctor' ? 'المواعيد المعلقة الجديدة (آخر 5)' : 'آخر مواعيدك المحجوزة'; ?>
        </h3>
        <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>

        <?php if (!empty($upcoming_appts)): ?>
            <table width="100%" border="1" style="border-collapse: collapse; text-align: right; margin-top: 10px;">
                <thead>
                    <tr style="background-color: #f0f0f0;">
                        <th style="padding: 8px;"><?php echo $role == 'doctor' ? 'المريض' : 'الطبيب'; ?></th>
                        <th style="padding: 8px;">التاريخ والوقت</th>
                        <th style="padding: 8px;">الحالة</th>
                        <?php if ($role == 'patient'): ?><th style="padding: 8px;">التخصص</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming_appts as $appt): ?>
                        <tr>
                            <td style="padding: 8px;">
                                <?php echo htmlspecialchars($role == 'doctor' ? $appt['patient_username'] : $appt['doctor_username']); ?>
                            </td>
                            <td style="padding: 8px;"><?php echo date('Y-m-d H:i', strtotime($appt['appointment_date'])); ?></td>
                            <td style="padding: 8px;">
                                <span class="status-button status-<?php echo strtolower($appt['status']); ?>" style="padding: 5px 10px; width: auto; font-size: 0.9em;">
                                    <?php echo htmlspecialchars($appt['status']); ?>
                                </span>
                            </td>
                            <?php if ($role == 'patient'): ?>
                                <td style="padding: 8px;"><?php echo htmlspecialchars($appt['specialty']); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>لا توجد مواعيد حالياً في هذا القسم.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>