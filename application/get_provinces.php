<?php
include('../includes/config.php');

if (isset($_GET['region_id'])) {
    $region_id = $_GET['region_id'];
    $stmt = $pdo->prepare("SELECT * FROM ph_provinces WHERE region_id = ? ORDER BY name");
    $stmt->execute([$region_id]);
    $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($provinces);
}
