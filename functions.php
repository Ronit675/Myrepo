<?php

/**
 * Generate a 6-digit numeric verification code.
 */
function generateVerificationCode(): string {
  $code = random_int(100000, 999999);
  return (string)$code;
}

/**
 * Send a verification code to an email.
 */
function sendVerificationEmail(string $email, string $code): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $subject = 'Your Verification Code';
    $message = "
    <html>
    <head>
        <title>Verification Code</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .code { 
                font-size: 24px; 
                font-weight: bold; 
                color: #0066cc;
                margin: 20px 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Verification Code</h2>
            <p>Please use the following verification code to complete your registration:</p>
            <div class='code'>$code</div>
            <p>This code will expire in 15 minutes.</p>
            <p>If you didn't request this code, please ignore this email.</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: no-reply@yourdomain.com\r\n";
    $headers .= "Reply-To: no-reply@yourdomain.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    return mail($email, $subject, $message, $headers);
}


/**
 * Register an email by storing it in a file.
 */
function registerEmail(string $email): bool {
  $file = __DIR__ . '/registered_emails.txt';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $email = trim(strtolower($email));
    $registeredEmails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (in_array($email, $registeredEmails)) {
      return false;
    }
    try {
        $result = file_put_contents(
            $file, 
            $email . PHP_EOL,  
            FILE_APPEND | LOCK_EX 
        );
        return $result !== false;
    } catch (Exception $e) {
        error_log("Email registration failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Unsubscribe an email by removing it from the list.
 */
function unsubscribeEmail(string $email): bool {
  $file = __DIR__ . '/registered_emails.txt';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $email = trim(strtolower($email));

    if (!file_exists($file)) {
        return false;
    }

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $emailIndex = array_search($email, $emails);
    if ($emailIndex === false) {
        return false; 
    }

    unset($emails[$emailIndex]);

    try {
        $result = file_put_contents(
            $file,
            implode(PHP_EOL, $emails) . PHP_EOL,  
            LOCK_EX  
        );
        
        return $result !== false;
    } catch (Exception $e) {
        error_log("Email unsubscription failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch random XKCD comic and format data as HTML.
 */
function fetchAndFormatXKCDData(): string {
        try {
            // First get the latest comic to know the maximum number
            $latestResponse = file_get_contents('https://xkcd.com/info.0.json');
            if ($latestResponse === false) {
                throw new Exception('Failed to fetch latest comic data');
            }
            
            $latestData = json_decode($latestResponse, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from XKCD');
            }
            
            $maxNum = $latestData['num'] ?? 0;
            if ($maxNum <= 0) {
                throw new Exception('Invalid comic number received');
            }
            
            // Get a random comic between 1 and the latest
            $randomNum = rand(1, $maxNum);
            $comicResponse = file_get_contents("https://xkcd.com/{$randomNum}/info.0.json");
            if ($comicResponse === false) {
                throw new Exception('Failed to fetch random comic data');
            }
            
            $comicData = json_decode($comicResponse, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response for random comic');
            }
            
            // Extract comic data
            $title = htmlspecialchars($comicData['safe_title'] ?? $comicData['title'] ?? 'Untitled');
            $imgUrl = htmlspecialchars($comicData['img'] ?? '');
            $altText = htmlspecialchars($comicData['alt'] ?? '');
            $comicNum = $comicData['num'] ?? 0;
            $date = sprintf('%d-%d-%d', 
                $comicData['year'] ?? '0000', 
                $comicData['month'] ?? '00', 
                $comicData['day'] ?? '00'
            );
            
            // Format as HTML
            $html = <<<HTML
    <div class="xkcd-comic">
        <h2>{$title} (#{$comicNum})</h2>
        <p class="date">Published: {$date}</p>
        <div class="comic-image">
            <img src="{$imgUrl}" alt="{$altText}" title="{$altText}">
        </div>
        <p class="alt-text"><em>{$altText}</em></p>
        <p class="comic-link">
            <a href="https://xkcd.com/{$comicNum}/" target="_blank">View on XKCD</a>
        </p>
    </div>
    HTML;
            
            return $html;
            
        } catch (Exception $e) {
            // Return error message if something goes wrong
            return '<div class="xkcd-error">Failed to load XKCD comic: ' . 
                   htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

/**
 * Send the formatted XKCD updates to registered emails.
 */
function sendXKCDUpdatesToSubscribers(): void {
    $file = __DIR__ . '/registered_emails.txt';
    
    // Fetch and format a random XKCD comic
    $comicHtml = fetchAndFormatXKCDData();
    
    // Check if we have valid comic content
    if (strpos($comicHtml, 'xkcd-error') !== false) {
        error_log("Failed to fetch XKCD comic data");
        return;
    }
    
    // Read registered emails
    if (!file_exists($file)) {
        error_log("No registered emails file found");
        return;
    }
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($emails)) {
        error_log("No registered emails found");
        return;
    }
    
    // Email headers
    $subject = 'Your Weekly XKCD Comic Update';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: XKCD Updates <xkcd@yourdomain.com>\r\n";
    $headers .= "Reply-To: no-reply@yourdomain.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Add CSS styling for better email presentation
    $styledHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
<style>
    .xkcd-comic { 
        max-width: 600px; 
        margin: 0 auto; 
        font-family: Arial, sans-serif;
        border: 1px solid #ddd;
        padding: 20px;
        background-color: #f9f9f9;
    }
    .comic-image img { 
        max-width: 100%; 
        height: auto; 
        display: block;
        margin: 0 auto;
    }
    .alt-text {
        font-style: italic;
        color: #555;
        margin: 15px 0;
        text-align: center;
    }
    .comic-link {
        text-align: center;
        margin-top: 20px;
    }
    .unsubscribe {
        text-align: center;
        margin-top: 30px;
        font-size: 12px;
        color: #888;
    }
</style>
</head>
<body>
    {$comicHtml}
    <div class="unsubscribe">
        <p>To stop receiving these updates, <a href="https://yourdomain.com/unsubscribe.php?email=%%EMAIL%%">unsubscribe here</a></p>
    </div>
</body>
</html>
HTML;

    // Send to each subscriber
    foreach ($emails as $email) {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue; // Skip invalid emails
        }
        
        // Personalize with unsubscribe link
        $personalizedHtml = str_replace('%%EMAIL%%', urlencode($email), $styledHtml);
        
        // Send the email
        if (!mail($email, $subject, $personalizedHtml, $headers)) {
            error_log("Failed to send XKCD update to: $email");
        }
    }
}