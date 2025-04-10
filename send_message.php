<?php
// filepath: c:\xampp\htdocs\log\send_message.php
// Prevent any output before our JSON response
ob_start();

require_once 'includes/session_check.php';
require_once 'includes/db.php';

// Clean any previous output
ob_clean();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Sending a message to an existing conversation
    if (isset($_POST['conversation_id']) && isset($_POST['message'])) {
        $conversation_id = (int)$_POST['conversation_id'];
        $message = trim($_POST['message']);
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            exit;
        }
        
        // Check if user is part of this conversation
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM conversation_participants 
            WHERE conversation_id = :conversation_id AND user_id = :user_id
        ");
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
            echo json_encode(['success' => false, 'message' => 'You are not part of this conversation']);
            exit;
        }
        
        // Get recipient ID
        $stmt = $conn->prepare("
            SELECT user_id 
            FROM conversation_participants 
            WHERE conversation_id = :conversation_id AND user_id != :user_id
        ");
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $recipient_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
        
        // Insert message
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("
            INSERT INTO messages 
            (conversation_id, sender_id, receiver_id, message_content, message_time, is_read) 
            VALUES 
            (:conversation_id, :sender_id, :receiver_id, :message_content, NOW(), 0)
        ");
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->bindParam(':sender_id', $user_id);
        $stmt->bindParam(':receiver_id', $recipient_id);
        $stmt->bindParam(':message_content', $message);
        $stmt->execute();
        
        // Update conversation's last message
        $stmt = $conn->prepare("
            UPDATE conversations 
            SET last_message = :message, last_message_time = NOW() 
            WHERE conversation_id = :conversation_id
        ");
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
        exit;
    }
    
    // Starting a new conversation
    if (isset($_POST['recipient_id']) && isset($_POST['message'])) {
        $recipient_id = (int)$_POST['recipient_id'];
        $message = trim($_POST['message']);
        
        if ($recipient_id == $user_id) {
            echo json_encode(['success' => false, 'message' => 'You cannot message yourself']);
            exit;
        }
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            exit;
        }
        
        // Log debug information
        error_log("Sending message to user ID: $recipient_id");
        
        // Check if recipient exists
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user WHERE user_id = :recipient_id");
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->execute();
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Recipient does not exist']);
            exit;
        }
        
        // Check if conversation already exists between these users
        $stmt = $conn->prepare("
            SELECT c.conversation_id
            FROM conversations c
            JOIN conversation_participants cp1 ON c.conversation_id = cp1.conversation_id AND cp1.user_id = :user_id
            JOIN conversation_participants cp2 ON c.conversation_id = cp2.conversation_id AND cp2.user_id = :recipient_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $conn->beginTransaction();
        
        if ($existing) {
            $conversation_id = $existing['conversation_id'];
            error_log("Using existing conversation: $conversation_id");
        } else {
            // Create new conversation
            $stmt = $conn->prepare("
                INSERT INTO conversations (last_message, last_message_time) 
                VALUES (:message, NOW())
            ");
            $stmt->bindParam(':message', $message);
            $stmt->execute();
            
            $conversation_id = $conn->lastInsertId();
            error_log("Created new conversation: $conversation_id");
            
            // Add participants
            $stmt = $conn->prepare("
                INSERT INTO conversation_participants (conversation_id, user_id) 
                VALUES (:conversation_id, :user_id), (:conversation_id, :recipient_id)
            ");
            $stmt->bindParam(':conversation_id', $conversation_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':recipient_id', $recipient_id);
            $stmt->execute();
        }
        
        // Insert message
        $stmt = $conn->prepare("
            INSERT INTO messages 
            (conversation_id, sender_id, receiver_id, message_content, message_time, is_read) 
            VALUES 
            (:conversation_id, :sender_id, :receiver_id, :message_content, NOW(), 0)
        ");
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->bindParam(':sender_id', $user_id);
        $stmt->bindParam(':receiver_id', $recipient_id);
        $stmt->bindParam(':message_content', $message);
        $stmt->execute();
        error_log("Inserted message into conversation: $conversation_id");
        
        // Update conversation's last message
        $stmt = $conn->prepare("
            UPDATE conversations 
            SET last_message = :message, last_message_time = NOW() 
            WHERE conversation_id = :conversation_id
        ");
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Message sent successfully',
            'conversation_id' => $conversation_id,
            'debug' => 'Transaction completed successfully'
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    
} catch(PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Database error in send_message.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("General error in send_message.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>