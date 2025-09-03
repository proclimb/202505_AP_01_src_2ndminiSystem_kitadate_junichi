<?php

/**
 * 更新完了画面
 *
 * ** 更新完了画面は、更新確認画面から遷移してきます
 *
 * ** 更新完了画面で行う処理は以下です
 * ** 1.DBへ更新する為、$_POSTから入力情報を取得する
 * ** 2.DBへユーザ情報を更新する
 * **   1.DBへ接続
 * **     ※接続出来なかった場合は、エラーメッセージを表示する
 * **   2.ユーザ情報を更新する
 * ** 3.html を描画
 * **   更新完了のメッセージを表示します
 */

session_start();

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'User.php';
require_once 'Address.php';
require_once 'FileBlobHelper.php';

// 2. 入力データ取得
// 2-1. ユーザーデータ取得
$id = $_POST['id'];
$userData = [
    'name'         => $_POST['name'],
    'kana'         => $_POST['kana'],
    'gender_flag'  => $_POST['gender_flag'],
    'tel'          => $_POST['tel'],
    'email'        => $_POST['email'],
];

// 2-2. 住所データも取得
$addressData = [
    'user_id'      => $id,
    'postal_code'  => $_POST['postal_code'],
    'prefecture'   => $_POST['prefecture'],
    'city_town'    => $_POST['city_town'],
    'building'     => $_POST['building'],
];
// 2-3. メールアドレスの重複チェック（自分以外に同じメールがあるか）
$sql = 'SELECT COUNT(*) FROM user_base WHERE email = :email AND id != :id';
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':email' => $_POST['email'],
    ':id'    => $_POST['id']
]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    echo "このメールアドレスは既に使用されています。";
    exit;
}
// 3. トランザクション開始
try {
    $pdo->beginTransaction();

    // 3. ユーザー＆住所クラスを生成
    $user = new User($pdo);
    // 4. 各テーブルのupdateメソッドを呼び出し
    $user->update($id, $userData);

    $address = new UserAddress($pdo);
    $address->updateByUserId($addressData); // user_id付きのデータを渡す

    // 6. ファイルアップロードを BLOB 化して取得（保存期限なし = null）
    //    edit.php の <input type="file" name="document1"> / document2
    $blobs = FileBlobHelper::getMultipleBlobs(
        $_FILES['document1'] ?? null,
        $_FILES['document2'] ?? null
    );

    // 7. BLOB が null でなければ（いずれかアップロードされたなら）user_documents に登録
    if ($blobs !== null) {
        // expires_at を NULL にして「保存期限なし」を実現
        $expiresAt = null;

        // User::saveDocument() を使って INSERT
        // ※ メソッド定義では expires_at が nullable なので null を渡す
        $user->saveDocument(
            $id,
            $blobs['front'],  // image(表)
            $blobs['back'],   // image(裏)
            $expiresAt
        );
    }

    // 8. トランザクションコミット
    $pdo->commit();
} catch (Exception $e) {
    // いずれかで例外が発生したらロールバックしてエラー表示
    $pdo->rollBack();
    // 本番環境ならログ出力してエラーページへリダイレクトなどが望ましい
    echo "エラーが発生しました。詳細: " . htmlspecialchars($e->getMessage(), ENT_QUOTES);
    exit;
}

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
        <h2>更新完了画面</h2>
    </div>
    <div>
        <div>
            <h1>更新完了</h1>
            <p>
                更新しました。<br>
            </p>
            <a href="index.php">
                <button type="button">TOPに戻る</button>
            </a>
        </div>
    </div>
</body>

</html>