<?php

include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$target_patient_id = ($user_role == 'patient') ? $user_id : (isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0);
$patient_username = '';
$records = [];
$error = '';
$success = '';


if ($target_patient_id == 0) {
    $error = "لم يتم تحديد المريض. يرجى العودة إلى قائمة المواعيد.";
}

try {
    
    if ($target_patient_id > 0) {
        $stmt_p = $pdo->prepare("SELECT username FROM users WHERE id = :id AND role = 'patient'");
        $stmt_p->execute(['id' => $target_patient_id]);
        $patient_data = $stmt_p->fetch();
        if ($patient_data) {
            $patient_username = $patient_data['username'];
        } else {
            $error = "بيانات المريض غير متوفرة.";
            $target_patient_id = 0;
        }
    }

    
    if ($user_role == 'doctor' && $target_patient_id > 0 && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_record'])) {
        $diagnosis = trim($_POST['diagnosis']);
        $treatment = trim($_POST['treatment']);
        $notes = trim($_POST['notes']);

        if (empty($diagnosis) || empty($treatment)) {
            $error = "التشخيص والعلاج مطلوبان لإضافة السجل.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO medical_records (patient_id, doctor_id, diagnosis, treatment, notes) VALUES (:pid, :did, :diag, :treat, :note)");
            $stmt->execute([
                'pid' => $target_patient_id,
                'did' => $user_id,
                'diag' => $diagnosis,
                'treat' => $treatment,
                'note' => $notes
            ]);
            $success = "✅ تم إضافة السجل الطبي بنجاح للمريض " . htmlspecialchars($patient_username) . ".";
        }
    }

    
    if ($target_patient_id > 0) {
        $sql = "SELECT mr.*, d.username AS doctor_username, d.specialty
                FROM medical_records mr
                JOIN users d ON mr.doctor_id = d.id
                WHERE mr.patient_id = :pid
                ORDER BY mr.record_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['pid' => $target_patient_id]);
        $records = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    $error = "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<div class="dashboard-card">
    <h2>📝 السجل الطبي لـ: <?php echo htmlspecialchars($patient_username); ?></h2>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color: green;"><?php echo $success; ?></p><?php endif; ?>

    <?php if ($user_role == 'doctor' && $target_patient_id > 0): ?>
        <div class="form-container" style="background-color: #e6f0ff;">
            <h3>إضافة سجل طبي جديد</h3>
            <form method="post">
                <input type="hidden" name="add_record" value="1">
                <label for="diagnosis">التشخيص:</label>
                <textarea id="diagnosis" name="diagnosis" required></textarea>
                
                <label for="treatment">خطة العلاج / الإجراءات:</label>
                <textarea id="treatment" name="treatment" required></textarea>
                
                <label for="notes">ملاحظات إضافية (اختياري):</label>
                <textarea id="notes" name="notes"></textarea>
                
                <input type="submit" value="إضافة السجل">
            </form>
        </div>
        <hr>
    <?php endif; ?>
    
    <h3>السجلات السابقة:</h3>

    <?php if (!empty($records)): ?>
        <?php foreach ($records as $record): ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 6px; background-color: #fff;">
                <p style="float: left; color: #555; font-size: 0.8em;">تاريخ السجل: <?php echo date('Y-m-d H:i', strtotime($record['record_date'])); ?></p>
                <h4 style="margin-top: 0; color: #007bff;">
                    بواسطة: د. <?php echo htmlspecialchars($record['doctor_username']); ?> 
                    (<?php echo htmlspecialchars($record['specialty']); ?>)
                </h4>
                
                <p><strong>التشخيص:</strong><br><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                <p><strong>العلاج:</strong><br><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></p>
                <?php if (!empty($record['notes'])): ?>
                    <p><strong>ملاحظات:</strong><br><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>لا توجد سجلات طبية مسجلة لهذا المريض حتى الآن.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>