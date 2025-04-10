<?php
// filepath: c:\xampp\htdocs\log\messages.php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$active_conversation = isset($_GET['conversation']) ? (int)$_GET['conversation'] : null;

try {
    // Get all conversations for this user
    $stmt = $conn->prepare("
        SELECT 
            c.conversation_id,
            c.last_message_time,
            c.last_message,
            u.user_id as other_user_id,
            u.user_name,
            COALESCE(s.full_name, sv.supervisor_name) as full_name,
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.conversation_id AND m.receiver_id = :user_id AND m.is_read = 0) as unread_count
        FROM conversations c
        JOIN conversation_participants cp1 ON c.conversation_id = cp1.conversation_id AND cp1.user_id = :user_id
        JOIN conversation_participants cp2 ON c.conversation_id = cp2.conversation_id AND cp2.user_id != :user_id
        JOIN user u ON cp2.user_id = u.user_id
        LEFT JOIN student s ON u.user_id = s.student_id
        LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
        ORDER BY c.last_message_time DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If a conversation is selected, get its messages
    $messages = [];
    $other_user = null;
    if ($active_conversation) {
        // Check if user is part of this conversation
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM conversation_participants
            WHERE conversation_id = :conversation_id AND user_id = :user_id
        ");
        $stmt->bindParam(':conversation_id', $active_conversation);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $has_access = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($has_access) {
            // Get other user in conversation
            $stmt = $conn->prepare("
                SELECT 
                    u.user_id,
                    u.user_name,
                    COALESCE(s.full_name, sv.supervisor_name) as full_name
                FROM conversation_participants cp
                JOIN user u ON cp.user_id = u.user_id
                LEFT JOIN student s ON u.user_id = s.student_id
                LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
                WHERE cp.conversation_id = :conversation_id AND cp.user_id != :user_id
            ");
            $stmt->bindParam(':conversation_id', $active_conversation);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $other_user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get messages
            $stmt = $conn->prepare("
                SELECT 
                    m.message_id,
                    m.sender_id,
                    m.message_content,
                    m.message_time,
                    m.is_read
                FROM messages m
                WHERE m.conversation_id = :conversation_id
                ORDER BY m.message_time ASC
            ");
            $stmt->bindParam(':conversation_id', $active_conversation);
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mark messages as read
            $stmt = $conn->prepare("
                UPDATE messages
                SET is_read = 1
                WHERE conversation_id = :conversation_id AND receiver_id = :user_id AND is_read = 0
            ");
            $stmt->bindParam(':conversation_id', $active_conversation);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } else {
            // Redirect if user doesn't have access to this conversation
            header("Location: messages.php");
            exit();
        }
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/messages.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <div class="messages-container">
            <!-- Conversations list (left sidebar) -->
            <div class="conversations-list">
                <div class="list-header">
                    <h2>Messages</h2>
                </div>
                
                <?php if (empty($conversations)): ?>
                    <div class="no-conversations">
                        <p>No conversations yet</p>
                        <p class="hint">Start a conversation by messaging a user from their profile</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <a href="messages.php?conversation=<?php echo $conversation['conversation_id']; ?>" 
                           class="conversation-item <?php echo ($active_conversation == $conversation['conversation_id']) ? 'active' : ''; ?>">
                            <div class="conversation-info">
                                <div class="conversation-name">
                                    <?php echo htmlspecialchars($conversation['full_name'] ?? $conversation['user_name']); ?>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-last-message">
                                    <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 40) . (strlen($conversation['last_message']) > 40 ? '...' : '')); ?>
                                </div>
                                <div class="conversation-time">
                                    <?php echo formatMessageTime($conversation['last_message_time']); ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message content (right side) -->
            <div class="message-content">
                <?php if ($active_conversation && $other_user): ?>
                    <div class="message-header">
                        <div class="recipient-name"><?php echo htmlspecialchars($other_user['full_name'] ?? $other_user['user_name']); ?></div>
                    </div>
                    
                    <div class="messages-wrapper">
                        <div class="messages" id="messagesContainer">
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?php echo ($message['sender_id'] == $user_id) ? 'sent' : 'received'; ?>">
                                    <div class="message-bubble">
                                        <?php echo nl2br(htmlspecialchars($message['message_content'])); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php echo formatMessageTime($message['message_time'], true); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="message-input">
                        <form id="messageForm" data-conversation="<?php echo $active_conversation; ?>">
                            <textarea id="messageText" placeholder="Write a message..." required></textarea>
                            <button type="submit" id="sendButton">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z" fill="currentColor"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H5.17L4 17.17V4H20V16Z" fill="currentColor"/>
                            </svg>
                        </div>
                        <h2>Your Messages</h2>
                        <p>Select a conversation or start a new one from a user's profile</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Scroll to bottom of messages when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        });

        // Handle message submission
        $('#messageForm').on('submit', function(e) {
            e.preventDefault();
            
            const conversationId = $(this).data('conversation');
            const messageText = $('#messageText').val().trim();
            
            if (!messageText) return;
            
            // Disable the form temporarily
            $('#messageText').prop('disabled', true);
            $('#sendButton').prop('disabled', true);
            
            // Send the message to the server
            $.post('send_message.php', {
                conversation_id: conversationId,
                message: messageText
            }, function(response) {
                try {
                    const result = JSON.parse(response);
                    
                    if (result.success) {
                        // Clear the input
                        $('#messageText').val('');
                        
                        // Append the new message to the messages container
                        $('#messagesContainer').append(`
                            <div class="message sent">
                                <div class="message-bubble">
                                    ${messageText.replace(/\n/g, '<br>')}
                                </div>
                                <div class="message-time">
                                    Just now
                                </div>
                            </div>
                        `);
                        
                        // Scroll to the bottom
                        const messagesContainer = document.getElementById('messagesContainer');
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch(e) {
                    console.error('Error parsing response:', e);
                    alert('An error occurred while sending your message');
                }
                
                // Re-enable the form
                $('#messageText').prop('disabled', false).focus();
                $('#sendButton').prop('disabled', false);
            }).fail(function() {
                alert('Failed to send message. Please try again.');
                $('#messageText').prop('disabled', false).focus();
                $('#sendButton').prop('disabled', false);
            });
        });

        // Auto-resize textarea as user types
        $('#messageText').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>

<?php
// Helper function to format message times
function formatMessageTime($timestamp, $showTime = false) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) { // Less than a minute
        return "Just now";
    } elseif ($diff < 3600) { // Less than an hour
        $minutes = floor($diff / 60);
        return $minutes . "m ago";
    } elseif ($diff < 86400) { // Less than a day
        return date("g:i A", $time);
    } elseif ($diff < 604800) { // Less than a week
        return date("D", $time);
    } else {
        return $showTime ? date("M j, g:i A", $time) : date("M j", $time);
    }
}
?>