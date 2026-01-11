<?php
require __DIR__ . '/../src/Auth.php';
session_start();
Auth::logout();
header('Location: /login.php');
exit;
