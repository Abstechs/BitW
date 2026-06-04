<?php

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/session.php";

/**
 * REGISTER USER
 */
function registerUser($data) {
    global $pdo;

    $sql = "INSERT INTO users 
    (username, email, phone, password, pin, 
    secret_q1, secret_a1, secret_q2, secret_a2, secret_q3, secret_a3)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        clean($data['username']),
        clean($data['email']),
        clean($data['phone']),
        password_hash($data['password'], PASSWORD_BCRYPT),
        password_hash($data['pin'], PASSWORD_BCRYPT),

        $data['q1'], $data['a1'],
        $data['q2'], $data['a2'],
        $data['q3'], $data['a3']
    ]);
}

/**
 * LOGIN USER
 */
function loginUser($identifier, $password) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return false;

    if (!password_verify($password, $user['password'])) {
        return false;
    }

    // session
    Session::set("user_id", $user['id']);
    Session::set("username", $user['username']);
    Session::set("role", $user['role']);

    return $user;
}

/**
 * GET USER
 */
function getUser($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * GET USER BY EMAIL
 */
function getUserByEmail($email) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([clean($email)]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * UPDATE USER PASSWORD
 */
function updateUserPassword($id, $password) {
    global $pdo;

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
}

/**
 * AUTH CHECK
 */
function isLoggedIn() {
    return Session::get("user_id") !== null;
}

/**
 * LOGOUT
 */
function logoutUser() {
    session_destroy();
}