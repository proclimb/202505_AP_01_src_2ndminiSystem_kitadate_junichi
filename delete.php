<?php

/**
 * 削除完了画面
 *
 * ** 削除完了画面は、更新・削除画面から遷移してきます
 *
 * ** 削除完了画面で行う処理は以下です
 * ** 1.DB接続情報、クラス定義をそれぞれのファイルから読み込む
 * ** 2.DBへ削除する為、$_POSTから入力情報を取得する
 * ** 3.ユーザ情報を削除する
 * **   1.Userクラスをインスタスタンス化する
 * **     ＊User(設計図)に$user(実体)を付ける
 * **   2.メソッドを実行しユーザー情報を削除する
 * ** 4.html を描画
 * **   削除完了のメッセージを表示します
 */

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'User.php';

// 2.更新・削除画面からの入力値を変数に設定
$id = $_POST["id"];

// 3-1.Userクラスをインスタンス化
$user = new User($pdo);

// 3-2.Userクラスのdelete()メソッドでデータ削除
$user->delete($id);

// 4.html の描画
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>完了画面</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>削除完了画面</h2>
    </div>
    <div>
        <div>
            <h1>削除完了</h1>
            <p>
                削除しました。<br>
            </p>
            <a href="index.php">
                <button type="button">TOPに戻る</button>
            </a>
        </div>
    </div>
</body>

</html>