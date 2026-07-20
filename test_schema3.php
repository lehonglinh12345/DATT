<?php
require 'backend/db.php';
global $database;
$res = $database->query("SHOW CREATE TABLE orders");
$row = $res->fetch_assoc();
echo $row['Create Table'];
