<?php
include('../includes/config.php');

if (isset($_GET['city_id'])) {
    $city_id = $_GET['city_id'];
    $stmt = $pdo->prepare("SELECT * FROM ph_barangays WHERE city_id = ? ORDER BY name");
    $stmt->execute([$city_id]);
    $barangays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($barangays);
}
