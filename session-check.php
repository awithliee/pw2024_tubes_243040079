<?php
// Session Management untuk Lamarin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// memebuat session login
function createSession($user_id, $role_id, $full_name, $email) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role_id'] = $role_id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID untuk keamanan
    session_regenerate_id(true);
}

//Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}


//Cek apakah user adalah admin
 
function isAdmin() {
    return isLoggedIn() && $_SESSION['role_id'] == 1;
}


//Cek apakah user adalah user biasa
 
function isUser() {
    return isLoggedIn() && $_SESSION['role_id'] == 2;
}

//mendapatkan informasi user saat ini
function getCurrentUser() {
    if(isLoggedIn()) {
        return [
            'user_id' => $_SESSION['user_id'],
            'role_id' => $_SESSION['role_id'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'login_time' => $_SESSION['login_time']
        ];
    }
    return null;
}

// Menghapus session dan logout
function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

//redirect kalo belum login
function requireLogin() {
    if(!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

//redirect kalu bukan admin
function requireAdmin() {
    requireLogin();
    if(!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

// Mendapatkan nama peran berdasarkan ID
function getRoleName($role_id = null) {
    if($role_id === null) {
        $role_id = $_SESSION['role_id'] ?? 0;
    }
    
    switch($role_id) {
        case 1: return 'Admin';
        case 2: return 'User';
        default: return 'Unknown';
    }
}
?>