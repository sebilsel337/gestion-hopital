<?php
// view_appointments.php
include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: index.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];
$appointments = [];
$error = '';
$success = '';

try {
    // 1. معالجة تحديث حالة الموعد
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appt_id']) && isset($_POST['new_status'])) {
        $appt_id = (int)$_POST['appt_id'];
        $new_status = $_POST['new_status'];
        
        if (in_array($new_status, ['Confirmed', 'Cancelled'])) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE id = :id AND doctor_id = :doctor_id");
            $stmt->execute(['status' => $new_status, 'id' => $appt_id, 'doctor_id' => $doctor_id]);
            
            if ($stmt->rowCount() > 0) {
                $success = "✅ تم تحديث حالة الموعد بنجاح.";
            } else {
                $error = "❌ فشل تحديث الحالة.";
            }
        } else {
            $error = "حالة غير صالحة.";
        }
    }

    // 2. جلب جميع المواعيد الموجهة للطبيب الحالي
    $sql = "SELECT a.*, u.username AS patient_username, u.email AS patient_email
            FROM appointments a
            JOIN users u ON a.patient_id = u.id
            WHERE a.doctor_id = :doctor_id
            ORDER BY a.appointment_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['doctor_id' => $doctor_id]);
    $appointments = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "❌ خطأ في جلب المواعيد: " . $e->getMessage();
}
?>

<div class="dashboard-card">
    <h2>إدارة مواعيدي</h2>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color: green;"><?php echo $success; ?></p><?php endif; ?>

    <?php if (!empty($appointments)): ?>
        <?php foreach ($appointments as $appt): ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 6px;">
                <h4 style="margin-top: 0; color: #007bff;">موعد مع: <?php echo htmlspecialchars($appt['patient_username']); ?></h4>
                
                <p><strong>التاريخ والوقت:</strong> <?php echo date('Y-m-d H:i', strtotime($appt['appointment_date'])); ?></p>
                <p><strong>الحالة الحالية:</strong> 
                    <span class="status-button status-<?php echo strtolower($appt['status']); ?>" style="padding: 5px 10px; width: auto; font-size: 0.9em;"><?php echo htmlspecialchars($appt['status']); ?></span>
                </p>
                <p><strong>السبب:</strong> <?php echo nl2br(htmlspecialchars($appt['reason'])); ?></p>

                <hr style="border: 0; border-top: 1px dashed #eee;">

                <form method="post" style="margin-top: 10px; display: inline-block;">
                    <input type="hidden" name="appt_id" value="<?php echo $appt['id']; ?>">
                    <strong style="margin-left: 10px; font-size: 0.9em;">تغيير الحالة:</strong>
                    
                    <?php if ($appt['status'] == 'Pending'): ?>
                        <button type="submit" name="new_status" value="Confirmed" class="status-button status-confirmed" style="width: auto;">تأكيد</button>
                        <button type="submit" name="new_status" value="Cancelled" class="status-button status-cancelled" style="width: auto;">إلغاء</button>
                    <?php else: ?>
                        <span style="color: #666; font-size: 0.9em;">(الحالة نهائية)</span>
                    <?php endif; ?>
                </form>
                
                <a href="messages.php?receiver_id=<?php echo $appt['patient_id']; ?>" class="button" style="width: auto; margin-right: 15px;">مراسلة المريض</a>
                
                <a href="view_patient_records.php?patient_id=<?php echo $appt['patient_id']; ?>" class="button" style="width: auto; margin-right: 15px; background-color: #f7941d;">عرض السجلات الطبية</a>
                
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>لا توجد مواعيد محجوزة حالياً موجهة إليك.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>