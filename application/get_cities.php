<?php
include('../includes/config.php');

if (isset($_GET['province_id'])) {
    $province_id = $_GET['province_id'];
    $stmt = $pdo->prepare("SELECT * FROM ph_cities WHERE province_id = ? ORDER BY name");
    $stmt->execute([$province_id]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cities);
}
