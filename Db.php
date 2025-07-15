<?php

/**
 * DB接続処理
 *
 * ** DB接続処理は、呼び出されたファイルにDB接続処理の結果を返します
 *
 * **
 * **
 * **
 * **
 * **
 */
// 1.DB接続設定
$host = 'localhost';
$dbname = '****';
$user = '****';
$password = '****';
$charset = 'utf8mb4';

// 2.DSN（データべース名）
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// 3.エラー時に例外を投げる設定
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// 4.PDOをインスタンス化
try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    // エラー時にエラーメッセージを出力
    die("DB接続に失敗しました: " . $e->getMessage());
}
