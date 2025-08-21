<?php
// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect to login if not authenticated
function requireAuth()
{
    if (!isLoggedIn()) {
        header("Location: ../admin/login.php");
        exit();
    }
}

// Redirect to admin dashboard if already logged in
function redirectIfLoggedIn()
{
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../application/form.php");
        }
        exit();
    }
}
