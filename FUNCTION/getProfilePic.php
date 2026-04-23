<?php
include_once '../DB/db_connect.php';
session_start();

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : (isset($_SESSION['id']) ? $_SESSION['id'] : 0);
$connect = OpenCon();

$query = "SELECT profilePic FROM users WHERE user_id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($profilePic);

if ($stmt->fetch() && $profilePic) {
    // Try to detect image type (default to jpeg)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($profilePic);
    if (!$mimeType) $mimeType = 'image/jpeg';
    header("Content-Type: $mimeType");
    echo $profilePic;
} else {
    // Output default image
    header('Content-Type: image/png');
    readfile("../IMG/defaultProfile.png");
}
CloseCon($connect); 