<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $success = registerEmail($email);
    
    if ($success) {
        $code = generateVerificationCode();
        // In a real app, you'd store this code and verify it
        $message = "Thank you for subscribing! Your verification code is: $code";
    } else {
        $message = "Subscription failed. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>XKCD Comic Subscriber</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <h1>Subscribe to XKCD Comics</h1>
        <?php if (isset($message)): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Your email address" required>
            <button type="submit">Subscribe</button>
        </form>
    </div>
</body>
</html>

