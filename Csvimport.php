<?php
// Csvimport.php
// ──────────────────────────────────────────
// プレビューを経て OK が押された場合に呼び出される。
// 日本郵便「住所の郵便番号 (UTF-8)」CSV を
// address_master テーブルに丸ごと取り込む。
// ──────────────────────────────────────────

require_once 'Db.php'; // ※Db.php で PDO 接続 ($pdo) を行っている前提

// 1) CSV ファイルのパス
$csvDir  = __DIR__ . '/csv';
$csvFile = $csvDir . '/update.csv';

if (! file_exists($csvFile)) {
    echo "<p style='color:red;'>CSV ファイルが見つかりません: {$csvFile}</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// 2) fopen/fgetcsv/fclose で CSV をパースして全行を配列に格納
$rows = [];
if (($handle = fopen($csvFile, 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        // $data は配列。最低でも 6 カラム以上あることを想定
        $rows[] = $data;
    }
    fclose($handle);
} else {
    echo "<p style='color:red;'>CSV を開けませんでした</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// 3) DB トランザクション開始
try {
    $pdo->beginTransaction();

    // 3-1) address_master を物理削除（全件削除）
    //      AUTO_INCREMENT もリセットしたい場合は TRUNCATE
    $pdo->exec("TRUNCATE TABLE address_master");

    // 3-2) INSERT 用プリペアドステートメントを準備
    $insertSql = "
        INSERT INTO address_master
            (postal_code, prefecture, city, town, updated_at)
        VALUES
            (:postal_code, :prefecture, :city, :town, NOW())
    ";
    $stmt = $pdo->prepare($insertSql);

    // 3-3) CSV の各行をループしてバインド＆実行
    foreach ($rows as $row) {
        // カラム数チェック
        if (count($row) < 6) {
            continue;
        }
        // カラム２～５を取得（例: $row[2]='0600000', $row[3]='北海道', $row[4]='札幌市中央区', $row[5]='…'）
        $postal   = trim($row[2]);
        $pref     = trim($row[6]);
        $city     = trim($row[7]);
        $town     = trim($row[8]);

        // 郵便番号が7桁でない行はスキップ
        if ($postal === '' || mb_strlen($postal) !== 7) {
            continue;
        }

        $stmt->bindValue(':postal_code', $postal, PDO::PARAM_STR);
        $stmt->bindValue(':prefecture',   $pref,   PDO::PARAM_STR);
        $stmt->bindValue(':city',         $city,   PDO::PARAM_STR);
        $stmt->bindValue(':town',         $town,   PDO::PARAM_STR);
        $stmt->execute();
    }

    // 3-4) コミット
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color:red;'>CSV 取込中にエラーが発生しました: "
        . htmlspecialchars($e->getMessage(), ENT_QUOTES)
        . "</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// CSVファイルの削除処理
if (file_exists($csvFile)) {
    if (! unlink($csvFile)) {
        // 削除に失敗した場合はログを残すか、画面に出力
        error_log("Failed to delete CSV file: {$csvFile}");
        // 必要ならユーザーに通知する
        echo "<p style='color:red;'>ファイルの削除に失敗しました</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>CSV 取込完了</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>CSV取り込み完了</h2>
    </div>
    <div>
        <div>
            <h1>CSV取り込み完了</h1>
            <p>
                住所マスタを更新しました。
            </p>
            <a href="index.php">
                <button type="button">TOPに戻る</button>
            </a>
        </div>
    </div>
</body>

</html>