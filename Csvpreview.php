<?php
// Csvpreview.php
// ──────────────────────────────────────────
// 「日本郵便 住所の郵便番号 (UTF-8)」CSV の
// 生データおよびパース結果をプレビューし、
// OK ボタンでインポート画面(Csvimport.php)に飛ばします。
// ──────────────────────────────────────────

require_once 'Db.php'; // ※Db.php で PDO 接続 ($pdo) を行っている前提

// CSV ファイルのパス（環境に合わせて変更）
$csvDir  = __DIR__ . '/csv';
$csvFile = $csvDir . '/update.csv';  // 例：utf_ken_all.csv を update.csv にリネームして置く

// 1) ファイル存在チェック
if (! file_exists($csvFile)) {
    echo "<p style='color:red;'>CSV ファイルが見つかりません: {$csvFile}</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// 2) file_get_contents() で「生の CSV 文字列」を取得
$rawCsv = file_get_contents($csvFile);
if ($rawCsv === false) {
    echo "<p style='color:red;'>CSV ファイルを読み込めませんでした</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// 3) fopen/fgetcsv/fclose でパースした結果を配列に格納
$dataRows = [];
if (($handle = fopen($csvFile, 'r')) !== false) {
    // パース結果を全行取得
    while (($row = fgetcsv($handle)) !== false) {
        // $row の中身（例）
        //   [0] => '01101'
        //   [1] => '060'
        //   [2] => '0600000'
        //   [3] => '北海道'
        //   [4] => '札幌市中央区'
        //   [5] => '以下に掲載がない場合'
        //   … それ以降に番地やフリガナ等がある場合もあり
        $dataRows[] = $row;
    }
    fclose($handle);
} else {
    echo "<p style='color:red;'>CSV をオープンできませんでした</p>";
    echo '<p><a href="index.php">TOPに戻る</a></p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>CSV プレビュー</title>
    <!-- ここで style_new.css を読み込む -->
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>CSV プレビュー</h2>
    </div>
    <!-- ① 生の CSV 文字列 -->
    <h2>① 生の CSV（file_get_contents）</h2>
    <pre class="comon-pre"><?= htmlspecialchars($rawCsv, ENT_QUOTES) ?></pre>

    <!-- ② パース結果（必要カラムのみ抽出して一覧表示） -->
    <h2>② CSV パース結果</h2>
    <table class="common-table">
        <tr>
            <!-- 見出し：郵便番号・都道府県・市区町村・町域 -->
            <th>郵便番号 (7桁)</th>
            <th>都道府県 (漢字)</th>
            <th>市区町村 (漢字)</th>
            <th>町域 (漢字)</th>
        </tr>
        <?php foreach ($dataRows as $row): ?>
            <?php
            // “日本郵便 住所の郵便番号 CSV” の場合、漢字情報はインデックス 6,7,8
            // count($row) が最低でも 9 以上かチェック
            if (count($row) < 9) {
                continue;
            }
            $postal = htmlspecialchars(trim($row[2]), ENT_QUOTES);
            $pref   = htmlspecialchars(trim($row[6]), ENT_QUOTES);
            $city   = htmlspecialchars(trim($row[7]), ENT_QUOTES);
            $town   = htmlspecialchars(trim($row[8]), ENT_QUOTES);
            ?>
            <tr>
                <td><?= $postal ?></td>
                <td><?= $pref ?></td>
                <td><?= $city ?></td>
                <td><?= $town ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- ③ OK ボタンを押すと Csvimport.php へ（インポート実行） -->
    <a href="Csvimport.php" class="csv-btn">OK</a>
    <a href="index.php" class="csv-btn-cancel">キャンセル</a>
</body>

</html>