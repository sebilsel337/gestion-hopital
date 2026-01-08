<?php

include 'db_connect.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$receiver_id = 0;
$receiver_username = '';
$error = '';
$success_msg = '';

try {
    if (isset($_GET['receiver_id']) && is_numeric($_GET['receiver_id'])) {
        $receiver_id = (int)$_GET['receiver_id'];
        
        if ($receiver_id == $current_user_id) {
            $error = "لا يمكنك مراسلة نفسك.";
            $receiver_id = 0;
        }
    }

    if ($receiver_id > 0) {
        $stmt_user = $pdo->prepare("SELECT username FROM users WHERE id = :id");
        $stmt_user->execute(['id' => $receiver_id]);
        $user_data = $stmt_user->fetch();
        
        if ($user_data) {
            $receiver_username = $user_data['username'];
        } else {
            $error = "المستخدم المستهدف غير موجود.";
            $receiver_id = 0;
        }
    } else if (empty($error)) {
        $error = "الرجاء تحديد مستخدم للمراسلة. انتقل إلى <a href='inbox.php'>المحادثات</a> أو <a href='search.php'>البحث</a>.";
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $receiver_id > 0) {
        $message = trim($_POST['message_content']);
        $file_path = NULL;
        $file_type = NULL;

        if (empty($message) && empty($_FILES['message_file']['name'])) {
            $error = "الرسالة لا يمكن أن تكون فارغة ويجب أن تحتوي على محتوى أو ملف مرفق.";
        } else {
            
            if (isset($_FILES['message_file']) && $_FILES['message_file']['error'] == 0) {
                $target_dir = "uploads/";
                $original_file_name = $_FILES['message_file']['name'];
                $file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
                
                $new_file_name = uniqid('msg_', true) . '.' . $file_extension;
                $target_file = $target_dir . $new_file_name;
                $upload_ok = true;

                if ($upload_ok) {
                    if (move_uploaded_file($_FILES['message_file']['tmp_name'], $target_file)) {
                        $file_path = $new_file_name; 
                        $file_type = $file_extension;
                    } else {
                        $error = "عذراً، حدث خطأ أثناء رفع ملفك.";
                    }
                }
            }

            // إدراج الرسالة في قاعدة البيانات
            if (!$error) {
                $message_content_to_insert = empty($message) ? NULL : $message;

                $stmt_insert = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_content, file_path, file_type) VALUES (:sender, :receiver, :content, :fpath, :ftype)");
                
                $stmt_insert->execute([
                    'sender' => $current_user_id, 
                    'receiver' => $receiver_id, 
                    'content' => $message_content_to_insert, 
                    'fpath' => $file_path, 
                    'ftype' => $file_type
                ]);
                
               
                header("Location: messages.php?receiver_id={$receiver_id}");
                exit;
            }
        }
    }

   
    $messages = [];
    if ($receiver_id > 0 && empty($error)) {
        $sql_messages = "
            SELECT * FROM messages 
            WHERE (sender_id = :id1 AND receiver_id = :id2) 
               OR (sender_id = :id2 AND receiver_id = :id1)
            ORDER BY sent_at ASC
        ";
        $stmt_messages = $pdo->prepare($sql_messages);
        $stmt_messages->execute(['id1' => $current_user_id, 'id2' => $receiver_id]);
        $messages = $stmt_messages->fetchAll();
    }

} catch (PDOException $e) {
    $error = "❌ حدث خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<div class="message-container">
    <?php if ($error): ?><p style="color: red; padding: 10px; border: 1px solid red; background-color: #fdd;"><?php echo $error; ?></p><?php endif; ?>

    <?php if ($receiver_id > 0 && empty($error)): ?>
        <h2>محادثتك مع <?php echo htmlspecialchars($receiver_username); ?></h2>

        <div class="chat-box">
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                    $is_sender = ($msg['sender_id'] == $current_user_id);
                    $align = $is_sender ? 'right' : 'left';
                    $bg_color = $is_sender ? '#007bff' : '#e4e6eb';
                    $text_color = $is_sender ? 'white' : 'black';
                    $time_color = $is_sender ? '#cce5ff' : '#606770';
                    ?>
                    <div class="message" style="margin: 10px 0; text-align: <?php echo $align; ?>;">
                        <div style="
                            display: inline-block; 
                            padding: 8px 12px; 
                            border-radius: 15px; 
                            max-width: 70%;
                            background-color: <?php echo $bg_color; ?>;
                            color: <?php echo $text_color; ?>;">
                            
                            <?php if (!empty($msg['file_path'])): ?>
                                <p style="margin: 0 0 5px 0; font-size: 0.9em;">
                                    <span style="font-weight: bold;"><?php echo strtoupper(htmlspecialchars($msg['file_type'])); ?> مرفق</span>
                                    <a href="uploads/<?php echo htmlspecialchars($msg['file_path']); ?>" target="_blank" style="color: <?php echo $is_sender ? '#ffcc00' : '#0056b3'; ?>; text-decoration: underline;">
                                        (تحميل الملف)
                                    </a>
                                </p>
                                <?php 
                                $image_types = ['jpg', 'jpeg', 'png', 'gif'];
                                if (in_array(strtolower($msg['file_type']), $image_types)): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($msg['file_path']); ?>" style="max-width: 100%; height: auto; border-radius: 4px; margin-top: 5px;">
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($msg['message_content'])): ?>
                                <p style="margin: 0; word-wrap: break-word;">
                                    <?php echo nl2br(htmlspecialchars($msg['message_content'])); ?>
                                </p>
                            <?php endif; ?>
                            
                            <span style="display: block; font-size: 0.7em; opacity: 0.8; margin-top: 5px; color: <?php echo $time_color; ?>;">
                                <?php echo date('H:i', strtotime($msg['sent_at'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #606770;">ابدأ المحادثة الآن!</p>
            <?php endif; ?>
        </div>

        <form method="post" enctype="multipart/form-data">
            <textarea name="message_content" placeholder="اكتب رسالتك هنا... (اختياري مع ملف)" style="margin-bottom: 5px;"></textarea>
            <label for="message_file" style="display: block; margin-bottom: 10px; font-size: 0.9em;">
                ارفاق ملف: <input type="file" id="message_file" name="message_file" style="margin-right: 10px;">
            </label>
            <input type="submit" value="إرسال">
        </form>

    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>