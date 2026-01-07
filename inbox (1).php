<?php

include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$conversations = [];
$error = '';

try {
 
    $sql_inbox = "
        SELECT m.*, u.username AS participant_username
        FROM messages m
        JOIN users u ON u.id = CASE WHEN m.sender_id = :current_id THEN m.receiver_id ELSE m.sender_id END
        WHERE m.id IN (
            SELECT MAX(id)
            FROM messages
            WHERE sender_id = :id1 OR receiver_id = :id2
            GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
        )
        ORDER BY m.sent_at DESC
    ";

    $stmt = $pdo->prepare($sql_inbox);
    $stmt->execute(['current_id' => $current_user_id, 'id1' => $current_user_id, 'id2' => $current_user_id]);
    $conversations = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "❌ خطأ في جلب المحادثات: " . $e->getMessage();
}
?>

<div class="message-container">
    <h2>✉️ رسائلي الخاصة</h2>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>

    <?php if (count($conversations) > 0): ?>
        <div class="inbox-list">
            <?php foreach ($conversations as $conv): ?>
                <?php 
                $other_user_id = ($conv['sender_id'] == $current_user_id) ? $conv['receiver_id'] : $conv['sender_id'];
                $is_sender = ($conv['sender_id'] == $current_user_id);
                $preview = $is_sender ? "أنت: " : "";
                
                if (!empty($conv['file_path'])) {
                    $preview .= "مرفق ملف (" . htmlspecialchars($conv['file_type']) . ")";
                } else if (!empty($conv['message_content'])) {
                    $preview .= mb_substr(htmlspecialchars($conv['message_content']), 0, 50) . (mb_strlen($conv['message_content']) > 50 ? '...' : '');
                } else {
                     $preview .= "[لا يوجد محتوى مرئي]";
                }
                ?>
                
                <a href="messages.php?receiver_id=<?php echo $other_user_id; ?>" class="inbox-item">
                    <div style="font-weight: bold; color: #007bff;"><?php echo htmlspecialchars($conv['participant_username']); ?></div>
                    <div style="font-size: 0.9em; color: #606770;">
                        <?php echo $preview; ?>
                    </div>
                    <div style="font-size: 0.8em; color: #aaa; text-align: left; margin-top: 5px;">
                        <?php echo date('Y-m-d H:i', strtotime($conv['sent_at'])); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>لا توجد محادثات حتى الآن. استخدم <a href="search.php">صفحة البحث</a> للتواصل.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>