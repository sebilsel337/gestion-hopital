<?php

include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointments = [];
$error = '';
$success = '';

try {
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_id'])) {
        $cancel_id = (int)$_POST['cancel_id'];
        
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = :id AND patient_id = :user_id AND status != 'Cancelled'");
        $stmt->execute(['id' => $cancel_id, 'user_id' => $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "✅ تم إلغاء الموعد بنجاح.";
        } else {
            $error = "❌ فشل الإلغاء أو الموعد ملغى بالفعل.";
        }
    }

    
    $sql = "SELECT a.*, d.username AS doctor_username, d.specialty FROM appointments a JOIN users d ON a.doctor_id = d.id WHERE a.patient_id = :user_id ORDER BY a.appointment_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $appointments = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "❌ خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<div class="dashboard-card">
    <h2>مواعيدي المحجوزة</h2>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color: green;"><?php echo $success; ?></p><?php endif; ?>

    <?php if (!empty($appointments)): ?>
        <?php foreach ($appointments as $appt): ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 6px;">
                <h4 style="margin-top: 0; color: #007bff;">
                    الطبيب: د. <?php echo htmlspecialchars($appt['doctor_username']); ?> 
                    (<?php echo htmlspecialchars($appt['specialty']); ?>)
                </h4>
                <p><strong>التاريخ:</strong> <?php echo date('Y-m-d H:i', strtotime($appt['appointment_date'])); ?></p>
                <p><strong>السبب:</strong> <?php echo nl2br(htmlspecialchars($appt['reason'])); ?></p>
                <p><strong>الحالة:</strong> 
                    <span class="status-button status-<?php echo strtolower($appt['status']); ?>" style="padding: 5px 10px; width: auto; font-size: 0.9em;"><?php echo htmlspecialchars($appt['status']); ?></span>
                </p>
                
                <?php if ($appt['status'] == 'Pending'): ?>
                    <form method="post" style="display: inline-block;">
                        <input type="hidden" name="cancel_id" value="<?php echo $appt['id']; ?>">
                        <button type="submit" class="status-button status-cancelled" style="width: auto; margin-top: 10px;" onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟');">
                            إلغاء الموعد
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>لم تقم بحجز أي مواعيد حتى الآن.</p>
        <a href="book_appointment.php" class="button" style="width: 200px; margin-top: 15px;">حجز موعد</a>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>