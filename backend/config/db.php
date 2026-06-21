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
        
        // Auto-migrate reports table if user_id column is missing
        try {
            $stmt = $pdo->query("DESCRIBE reports");
            $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            if (!in_array('user_id', $columns)) {
                $pdo->exec("UPDATE reports SET status = 'Resolved' WHERE status = 'Completed'");
                $pdo->exec("ALTER TABLE reports MODIFY COLUMN status ENUM('Pending', 'Assigned', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending'");
                
                $pdo->exec("ALTER TABLE reports ADD COLUMN user_id INT NULL AFTER id");
                $pdo->exec("ALTER TABLE reports ADD COLUMN report_code VARCHAR(20) NULL AFTER user_id");
                $pdo->exec("ALTER TABLE reports ADD COLUMN hostel_block VARCHAR(20) NULL AFTER room");
                $pdo->exec("ALTER TABLE reports ADD COLUMN room_number VARCHAR(20) NULL AFTER hostel_block");
                $pdo->exec("ALTER TABLE reports ADD COLUMN assigned_staff_id INT NULL AFTER assignedStaff");
                $pdo->exec("ALTER TABLE reports ADD COLUMN student_remarks TEXT NULL AFTER priority");
                $pdo->exec("ALTER TABLE reports ADD COLUMN staff_remarks TEXT NULL AFTER student_remarks");
                $pdo->exec("ALTER TABLE reports ADD COLUMN assigned_at TIMESTAMP NULL");
                $pdo->exec("ALTER TABLE reports ADD COLUMN resolved_at TIMESTAMP NULL");
                $pdo->exec("ALTER TABLE reports ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                
                try {
                    $pdo->exec("ALTER TABLE reports ADD CONSTRAINT fk_reports_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
                } catch (Exception $ex) {}
                try {
                    $pdo->exec("ALTER TABLE reports ADD CONSTRAINT fk_reports_assigned_staff_id FOREIGN KEY (assigned_staff_id) REFERENCES users(id) ON DELETE SET NULL");
                } catch (Exception $ex) {}
            }
        } catch (Exception $ex) {}

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
