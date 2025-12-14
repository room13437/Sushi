<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Get image path to delete file
    $sql = "SELECT image_path FROM promotions WHERE id = $id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['image_path'];

        // Delete from DB
        $sql_del = "DELETE FROM promotions WHERE id = $id";
        if ($conn->query($sql_del) === TRUE) {
            // Delete file if exists
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            header("Location: manage_promotions");
        } else {
            echo "Error deleting record: " . $conn->error;
        }
    } else {
        echo "Promotion not found";
    }
} else {
    header("Location: manage_promotions");
}

$conn->close();
?>