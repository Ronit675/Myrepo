<?php
// src/unsubscribe.php

require_once 'functions.php';

$email = $_GET['email'] ?? '';

if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (unsubscribeEmail($email)) {
        echo "You have been successfully unsubscribed from XKCD updates.";
    } else {
        echo "Failed to unsubscribe. Please try again later.";
    }
} else {
    echo "Invalid email address.";
}