<?php
require 'backend/db.php';
$res = db_query("SELECT id, full_name, username, phone FROM users");
while($row = $res->fetch_assoc()) {
    echo $row['id'] . " | " . $row['full_name'] . " | " . $row['username'] . " | '" . $row['phone'] . "'\n";
}
