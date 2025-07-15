<?php

/**
 * 登録完了画面
 *
 * ** 登録完了画面は、確認画面から遷移してきます
 *
 * ** 登録完了画面で行う処理は以下です
 * ** 1.DB接続情報、クラス定義をそれぞれのファイルから読み込む
 * ** 2.DBへ登録する為、$_POSTから入力情報を取得する
 * ** 3.ユーザ情報を登録する
 * **   1.Userクラスをインスタスタンス化する
 * **     ＊User(設計図)に$user(実体)を付ける
 * **   2.メソッドを実行しユーザー情報を登録する
 * ** 4.html を描画
 * **   登録完了のメッセージを表示します
 */

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'User.php';
require_once 'Address.php';

// 2.$_POSTから入力情報を各変数へ代入
//   ※POSTメソッドで送信された場合は、入力情報を代入する
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $kana = $_POST['kana'];
    $gender = $_POST['gender'];
    $y = $_POST['birth_year'];
    $m = $_POST['birth_month'];
    $d = $_POST['birth_day'];
    $postal_code = $_POST['postal_code'];
    $prefecture = $_POST['prefecture'];
    $city_town = $_POST['city_town'];
    $building = $_POST['building'];
    $tel = $_POST['tel'];
    $email = $_POST['email'];
}

//DBへの格納する値の形成
$birth_date = sprintf('%04d-%02d-%02d', $y, $m, $d);

// 3-1.Userクラスをインスタンス化
$user = new User($pdo);
// 3-2.Userクラスのcreateメソッドを実行
$userId = $user->create([
    'name' => $_POST['name'],
    'kana' => $_POST['kana'],
    'gender_flag' => $_POST['gender'],
    'birth_date' => $birth_date,
    'tel' => $_POST['tel'],
    'email' => $_POST['email']
]);

// 4-1.UserAddressクラスをインスタンス化
$address = new UserAddress($pdo);
// 4-1.UserAddressクラスのcreateメソッドを実行
$address->create([
    'user_id' => $userId,
    'postal_code' => $_POST['postal_code'],
    'prefecture' => $_POST['prefecture'],
    'city_town' => $_POST['city_town'],
    'building' => $_POST['building']
])

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
        <h2>完了画面</h2>
    </div>
    <div>
        <div>
            <h1>登録完了</h1>
            <p>
                登録ありがとうございました。<br>
            </p>
            <a href="index.php">
                <button type="button">TOPに戻る</button>
            </a>
        </div>
    </div>
</body>

</html>