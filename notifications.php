<?php
function get_friend_request_count($conn, $user_id) {
    $query = "SELECT COUNT(*) as count FROM friendships WHERE friend_id = ? AND status = 'pending'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}