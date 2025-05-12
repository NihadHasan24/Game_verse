<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'gaming_community';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// echo "Connected successfully";

session_start();
?>