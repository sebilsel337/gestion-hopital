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
    
    $stmt = $pdo->query("SELECT id, username, specialty FROM users WHERE role = 'doctor' AND specialty IS NOT NULL ORDER BY username ASC");
    $doctors = $stmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $patient_id = $_SESSION['user_id'];
        $doctor_id = (int)$_POST['doctor_id'];
        $appointment_datetime = str_replace('T','',$_POST['appointment_date']);
        $reason = trim($_POST['reason']);
        
        $timestamp = strtotime($appointment_datetime);
        $day_of_week = date('w', $timestamp); 
        $appointment_time = date('H:i:s', $timestamp);
        $appointment_date_only = date('Y-m-d', $timestamp);

        if (empty($doctor_id) || empty($appointment_datetime) || empty($reason)) {
            $error = "الرجاء تعبئة جميع الحقول المطلوبة.";
        } 
        
        
      $nom=strtotime(date('Y-m-d H:i'));
      if (strtotime($appointment_date)<=$nom){
        die("مكاش حجز موعد في الماضي");
      }

        
        if (!$error) {
            $stmt_avail = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = :doc_id AND day_of_week = :day AND start_time <= :time AND end_time >= :time");
            $stmt_avail->execute([
                'doc_id' => $doctor_id, 
                'day' => $day_of_week, 
                'time' => $appointment_time
            ]);
            
            if ($stmt_avail->rowCount() == 0) {
                $error = "الطبيب غير متاح في هذا اليوم أو الوقت المختار. يرجى مراجعة إتاحة الطبيب.";
            }
        }
        
        
        if (!$error) {
            $stmt_overlap = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = :doc_id AND DATE(appointment_date) = :app_date AND status IN ('Pending', 'Confirmed') AND TIME(appointment_date) BETWEEN :time_start AND :time_end");
            
            
            $time_start = date('H:i:s', $timestamp - (15 * 60)); 
            $time_end = date('H:i:s', $timestamp + (15 * 60)); 

            $stmt_overlap->execute([
                'doc_id' => $doctor_id, 
                'app_date' => $appointment_date_only, 
                'time_start' => $time_start,
                'time_end' => $time_end
            ]);

            if ($stmt_overlap->rowCount() > 0) {
                $error = "هناك تداخل في المواعيد. هذا الوقت محجوز بالفعل أو قريب جدًا من موعد آخر.";
            }
        }


        /
        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, status) VALUES (:patient_id, :doctor_id, :app_date, :reason, 'Pending')");
            $stmt->execute([
                'patient_id' => $patient_id, 
                'doctor_id' => $doctor_id, 
                'app_date' => $appointment_datetime, 
                'reason' => $reason
            ]);
            $success = "✅ تم حجز موعدك بنجاح! ننتظر تأكيد الطبيب.";
        }
    }
} catch (PDOException $e) {
    $error = "❌ حدث خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<div class="form-container">
    <h2>📅 حجز موعد جديد</h2>
    <p style="color: blue;">ملاحظة: يتم التحقق من إتاحة الطبيب وتجنب تداخل المواعيد عند الحجز.</p>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color: green;"><?php echo $success; ?></p><?php endif; ?>

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
        <input type="datetime-local" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d\TH:i'); ?>">
        
        <label for="reason">سبب الحجز (التفاصيل):</label>
        <textarea id="reason" name="reason" required></textarea>
        
        <input type="submit" value="تأكيد الحجز">
    </form>
</div>

<?php include 'footer.php'; ?>