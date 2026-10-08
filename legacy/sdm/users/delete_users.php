<?php
require "../../conn/conn.php";

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
}

header("Location: ../index.php?page=users&success=deleted");
exit;