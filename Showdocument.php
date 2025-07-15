<?php
// show_document.php
// -----------------------------------------------------
// GET パラメータから user_id, type(front/back) を受け取り、
// user_documents テーブルから最新レコードの BLOB 画像を取得して表示する。
// -----------------------------------------------------

// 1) 共通設定読み込み（PDO インスタンスを生成するファイルを用意しておく）
require_once 'Db.php'; // 例: $pdo = new PDO(...);

// 2) GET パラメータ取得およびバリデーション
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$type   = isset($_GET['type'])    ? $_GET['type']  : '';

if ($userId <= 0 || !in_array($type, ['front', 'back'], true)) {
    // パラメータ不正なら 400 を返す
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid parameters.";
    exit;
}

try {
    // 3) 最新の user_documents レコードを取得
    $sql = "
        SELECT front_image, back_image
          FROM user_documents
         WHERE user_id = :user_id
         ORDER BY created_at DESC
         LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (! $row) {
        // レコードが存在しない場合は 404 Not Found
        header("HTTP/1.1 404 Not Found");
        echo "Document not found.";
        exit;
    }

    // 4) 指定された type に対応する BLOB を取り出す
    $blob = null;
    if ($type === 'front') {
        $blob = $row['front_image'];
    } else {
        $blob = $row['back_image'];
    }

    if ($blob === null) {
        // BLOB が NULL の場合も 404 Not Found
        header("HTTP/1.1 404 Not Found");
        echo "Document not uploaded.";
        exit;
    }

    // 5) MIME タイプ判定（buffer() で直接バイナリから判定）
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($blob);

    // 6) PNG/JPEG 以外は不正とみなして 415 を返す
    if (!in_array($mimeType, ['image/png', 'image/jpeg'], true)) {
        header("HTTP/1.1 415 Unsupported Media Type");
        echo "Invalid image format.";
        exit;
    }

    // 7) レスポンスヘッダーをセットし、バイナリを返す
    header("Content-Type: " . $mimeType);
    header("Content-Length: " . strlen($blob));
    // 必要に応じてキャッシュ無効化
    // header("Cache-Control: no-cache, no-store, must-revalidate");
    // header("Pragma: no-cache");
    // header("Expires: 0");

    echo $blob;
    exit;
} catch (Exception $e) {
    // 例外時は 500 Internal Server Error
    header("HTTP/1.1 500 Internal Server Error");
    error_log("[ERROR] show_document.php Exception: " . $e->getMessage());
    echo "Server error.";
    exit;
}
