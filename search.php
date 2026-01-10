<?php

include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search_term = '';
$results = [];
$error = '';

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $search_term = trim($_GET['query']);
    $search_pattern = '%' . $search_term . '%';
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, role, specialty FROM users WHERE username LIKE :pattern AND id != :id");
        $stmt->execute(['pattern' => $search_pattern, 'id' => $_SESSION['user_id']]);
        $results = $stmt->fetchAll();

    } catch (PDOException $e) {
        $error = "❌ خطأ في تهيئة الاستعلام: " . $e->getMessage();
    }
}
?>

<div class="form-container">
    <h2>🔍 البحث عن مستخدمين (أطباء/مرضى)</h2>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <form method="get">
        <input type="text" name="query" placeholder="ابحث باسم المستخدم..." value="<?php echo htmlspecialchars($search_term); ?>" required>
        <input type="submit" value="بحث">
    </form>
</div>

<?php if (isset($_GET['query'])): ?>
    <div class="dashboard-card" style="margin-top: 20px;">
        <h3>نتائج البحث عن "<?php echo htmlspecialchars($search_term); ?>"</h3>
        
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $user): ?>
                <div style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: bold;"><?php echo htmlspecialchars($user['username']); ?></span>
                    <div>
                        <span style="margin-left: 10px; color: #666; font-size: 0.9em;">
                            (<?php echo translate_role($user['role']); ?> 
                            <?php echo $user['role'] == 'doctor' ? '(' . htmlspecialchars($user['specialty']) . ')' : ''; ?>)
                        </span>
                        <a href="messages.php?receiver_id=<?php echo $user['id']; ?>" style="color: green; text-decoration: none;">مراسلة</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>عذراً، لم يتم العثور على مستخدمين يطابقون "<?php echo htmlspecialchars($search_term); ?>"</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>