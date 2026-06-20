<?php
function getDBConnection()
{
    $host = "localhost";
    $dbname = "hostel_management";
    $username = "root";
    $password = "";
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",
            $username,
            $password
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo json_encode([
            "error" => "Database connection failed",
            "message" => $e->getMessage()
        ]);
        exit;
    }
}
?>
