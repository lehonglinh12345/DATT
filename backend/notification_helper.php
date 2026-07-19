<?php
// backend/notification_helper.php
require_once __DIR__ . '/db.php';

/**
 * Send a notification to all customers.
 * 
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $link Optional link
 * @return bool True on success
 */
function send_global_notification($title, $message, $link = null) {
    // 1. Insert into notifications table
    $insertNotif = db_query(
        "INSERT INTO notifications (title, message, link) VALUES (?, ?, ?)",
        "sss",
        [$title, $message, $link]
    );

    if (!$insertNotif) return false;

    // Get the inserted ID using the global database connection object from db.php
    global $database;
    $notification_id = $database->insert_id;

    // 2. Fetch all customers
    $users = db_query("SELECT id FROM users WHERE role = 'customer'");
    
    if ($users && $users->num_rows > 0) {
        // 3. Insert into user_notifications
        while ($user = $users->fetch_assoc()) {
            db_query(
                "INSERT INTO user_notifications (user_id, notification_id) VALUES (?, ?)",
                "ii",
                [$user['id'], $notification_id]
            );
        }
    }

    return true;
}
