<?php
/
include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: index.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];
$arabic_days = get_arabic_days();
$availability = [];
$error = '';
$success = '';

try {
   
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_availability'])) {
            $day = (int)$_POST['day'];
            $start = $_POST['start_time'];
            $end = $_POST['end_time'];

            if ($start >= $end) {
                $error = "وقت النهاية يجب أن يكون بعد وقت البدء.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (:doc_id, :day, :start, :end)");
                if ($stmt->execute(['doc_id' => $doctor_id, 'day' => $day, 'start' => $start, 'end' => $end])) {
                    $success = "✅ تم إضافة الإتاحة بنجاح.";
                } else {
                    $error = "❌ فشل الإضافة. قد يكون هذا النطاق الزمني مسجلًا بالفعل.";
                }
            }
        } elseif (isset($_POST['delete_id'])) {
            $delete_id = (int)$_POST['delete_id'];
            $stmt = $pdo->prepare("DELETE FROM doctor_availability WHERE id = :id AND doctor_id = :doc_id");
            $stmt->execute(['id' => $delete_id, 'doc_id' => $doctor_id]);
            $success = "✅ تم حذف الإتاحة بنجاح.";
        }
    }

    
    $stmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = :doc_id ORDER BY day_of_week ASC, start_time ASC");
    $stmt->execute(['doc_id' => $doctor_id]);
    $availability = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<div class="dashboard-card">
    <h2>⏰ تحديد إتاحة المواعيد</h2>
    <p>حدد الأيام والأوقات التي تكون متاحاً فيها لاستقبال المواعيد. سيتمكن المرضى من الحجز في هذه الأوقات فقط.</p>
    
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color: green;"><?php echo $success; ?></p><?php endif; ?>

    <div class="form-container">
        <h3>إضافة نطاق إتاحة جديد</h3>
        <form method="post">
            <label for="day">اليوم:</label>
            <select id="day" name="day" required>
                <?php foreach ($arabic_days as $key => $day_name): ?>
                    <option value="<?php echo $key; ?>"><?php echo $day_name; ?></option>
                <?php endforeach; ?>
            </select>
            
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="start_time">من:</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>
                <div style="flex: 1;">
                    <label for="end_time">إلى:</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
            </div>
            
            <input type="submit" name="add_availability" value="حفظ الإتاحة">
        </form>
    </div>

    <div class="dashboard-card" style="margin-top: 30px;">
        <h3>نطاقات الإتاحة الحالية:</h3>
        
        <?php if (!empty($availability)): ?>
            <table>
                <thead>
                    <tr>
                        <th>اليوم</th>
                        <th>من الساعة</th>
                        <th>إلى الساعة</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availability as $item): ?>
                        <tr>
                            <td><?php echo $arabic_days[$item['day_of_week']]; ?></td>
                            <td><?php echo date('h:i A', strtotime($item['start_time'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($item['end_time'])); ?></td>
                            <td>
                                <form method="post" style="display: inline-block;">
                                    <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="status-button status-cancelled" style="width: auto; padding: 5px 10px;" onclick="return confirm('هل أنت متأكد من حذف هذا النطاق؟');">
                                        حذف
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>لم تقم بتحديد أي أوقات إتاحة حتى الآن.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>