<?php
require_once 'Db.php'; // PDO $pdo を読み込む

header('Content-Type: application/json');

$postal_code = $_POST['postal_code'] ?? '';

if (!$postal_code) {
    echo json_encode([]);
    exit;
}

// 全角→半角、空白除去、ハイフン除去
$postal_code = mb_convert_kana($postal_code, 'n');
$postal_code = str_replace('-', '', $postal_code);
$postal_code = trim($postal_code);

$sql = "
    SELECT
        prefecture,
        CONCAT(city, town) AS city_town
    FROM
        address_master
    WHERE
        postal_code = :postal_code
    LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([':postal_code' => $postal_code]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($result ?: []);
