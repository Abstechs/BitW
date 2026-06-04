<?php
// public/logout.php
require_once __DIR__ . '/../core/session.php';
session_destroy();
header('Location: /bitw/public/login.php');
exit;
