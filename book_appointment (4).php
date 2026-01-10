<?php
include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';
$doctors = [];

try {
    
    $stmt = $pdo->query("SELECT id, username, specialty FROM users WHERE role = 'doctor' ORDER BY username ASC");
    $doctors = $stmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $patient_id = $_SESSION['user_id'];
        $doctor_id = (int)$_POST['doctor_id'];
        $appointment_datetime_input = $_POST['appointment_date'];
        $reason = trim($_POST['reason']);
        
        
        $appointment_datetime = date('Y-m-d H:i:s', strtotime($appointment_datetime_input));
        
        if (empty($doctor_id) || empty($appointment_datetime_input) || empty($reason)) {
            $error = "الرجاء تعبئة جميع الحقول المطلوبة.";
        } else {
            
            if (strtotime($appointment_datetime_input) <= time()) {
                $error = "لا يمكن حجز موعد في الماضي.";
            } else {
                /
                $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, status) VALUES (:patient_id, :doctor_id, :appointment_date, :reason, 'Pending')");
                
                $stmt->execute([
                    'patient_id' => $patient_id,
                    'doctor_id' => $doctor_id,
                    'appointment_date' => $appointment_datetime,
                    'reason' => $reason
                ]);
                
                $success = "✅ تم حجز موعدك بنجاح! ننتظر تأكيد الطبيب.";
            }
        }
    }
} catch (PDOException $e) {
    $error = "❌ حدث خطأ في قاعدة البيانات: " . $e->getMessage();
    
    
}
?>

<div class="form-container">
    <h2>📅 حجز موعد جديد</h2>
    
    <?php if ($error): ?>
        <div style="background: #ffe6e6; color: #c00; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="background: #e6ffe6; color: #008000; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <form method="post">
        <label for="doctor_id">اختر الطبيب:</label>
        <select id="doctor_id" name="doctor_id" required>
            <option value="">-- اختر طبيباً --</option>
            <?php foreach ($doctors as $doc): ?>
                <option value="<?php echo $doc['id']; ?>">
                    د. <?php echo htmlspecialchars($doc['username']); ?> 
                    (<?php echo htmlspecialchars($doc['specialty']); ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="appointment_date">التاريخ والوقت:</label>
        <input type="datetime-local" id="appointment_date" name="appointment_date" 
               required 
               min="<?php echo date('Y-m-d\TH:i'); ?>"
               value="<?php echo date('Y-m-d\TH:i', strtotime('+1 day 09:00')); ?>">
        
        <label for="reason">سبب الحجز (التفاصيل):</label>
        <textarea id="reason" name="reason" required 
                  placeholder="وصف الحالة أو السبب للحجز..."></textarea>
        
        <input type="submit" value="تأكيد الحجز">
    </form>
</div>

<?php include 'footer.php'; ?>