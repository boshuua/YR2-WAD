<?php

$servername = "localhost";
$username = "ws369808_JOSHK";
$password = "t_k41N42q";
$dbname = "ws369808_WAD";
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";

?>