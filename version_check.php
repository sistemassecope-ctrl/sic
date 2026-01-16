<?php
// Check Versions
require_once 'config/db.php';

echo "--------------------------------\n";
echo "PHP Version: " . phpversion() . "\n";
echo "--------------------------------\n";

try {
    $db = (new Database())->getConnection();
    // Get simple version
    $stmt = $db->query("SELECT VERSION() as v");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $row['v'] . "\n";

    // Get comment (MariaDB vs MySQL)
    $stmt2 = $db->query("SHOW VARIABLES LIKE 'version_comment'");
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($row2) {
        echo "Variant: " . $row2['Value'] . "\n";
    }

} catch (Exception $e) {
    echo "MySQL Check Failed: " . $e->getMessage() . "\n";
}
echo "--------------------------------\n";
?>