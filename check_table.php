<?php
require_once 'config/db.php';
try {
    $db = (new Database())->getConnection();
    echo "Connected successfully.\n";
    $stmt = $db->query("SHOW TABLES LIKE 'area_pao'");
    $table = $stmt->fetch(PDO::FETCH_COLUMN);
    if ($table) {
        echo "Table 'area_pao' EXISTS.\n";

        $desc = $db->query("DESCRIBE area_pao");
        echo "Columns:\n";
        while ($row = $desc->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }

    } else {
        echo "Table 'area_pao' DOES NOT EXIST.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>