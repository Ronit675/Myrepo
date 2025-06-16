<?php
require_once 'functions.php';

try {
    logMessage('Cron job started');
    
    if (!file_exists(EMAILS_FILE)) {
        throw new Exception('No subscribers file found');
    }
    
    $comic = fetchRandomXKCDComic();
    if (empty($comic)) {
        throw new Exception('Failed to fetch XKCD comic');
    }
    
    $emails = file(EMAILS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $subject = "XKCD Comic #{$comic['num']}: {$comic['safe_title']}";
    
    ob_start();
    include __DIR__ . '/templates/email_template.php';
    $body = ob_get_clean();
    
    foreach ($emails as $email) {
        if (sendEmail($email, $subject, $body)) {
            logMessage("Sent to: {$email}");
        } else {
            logMessage("Failed to send to: {$email}");
        }
    }
    
    logMessage('Cron job completed successfully');
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}
?>