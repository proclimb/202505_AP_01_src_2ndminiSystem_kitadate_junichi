<?php

/**
 * 確認画面
 *
 * ** 確認画面は、登録画面から遷移してきます
 *
 * ** 確認画面で行う処理は以下です
 * ** 1.セッションを開始
 * **   1.セッションに登録画面の情報があるか確認する
 * **     ※無ければ、登録画面に遷移する
 * **   2.セッションから登録画面の情報を$_POSTへコピーする
 * ** 2.セッションを削除
 * ** 3.html を描画
 * **   登録画面で入力した情報を画面に表示する
 */

// 1.セッションの開始
session_cache_limiter('none');
session_start();

// 1-1.$_SESSIONに登録画面の入力情報があるか確認
if (!isset($_SESSION['input_data'])) {
    // $_SESSIONの値が空だったら、登録画面へ遷移する
    header('Location:input.php');
    exit();
}

// 1-2.セッションから登録画面の入力情報を$_POSTへコピーする
$_POST = $_SESSION['input_data'];

// 2.セッションを破棄する
session_destroy();

// 3.htmlの描画
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>確認画面</h2>
    </div>
    <div>
        <form action="submit.php" method="post">
            <input type="hidden" name="name" value="<?php echo $_POST['name'] ?>">
            <input type="hidden" name="kana" value="<?php echo $_POST['kana'] ?>">
            <input type="hidden" name="gender" value="<?php echo $_POST['gender'] ?>">
            <input type="hidden" name="birth_year" value="<?php echo $_POST['birth_year'] ?>">
            <input type="hidden" name="birth_month" value="<?php echo $_POST['birth_month'] ?>">
            <input type="hidden" name="birth_day" value="<?php echo $_POST['birth_day'] ?>">
            <input type="hidden" name="postal_code" value="<?php echo $_POST['postal_code'] ?>">
            <input type="hidden" name="prefecture" value="<?php echo $_POST['prefecture'] ?>">
            <input type="hidden" name="city_town" value="<?php echo $_POST['city_town'] ?>">
            <input type="hidden" name="building" value="<?php echo $_POST['building'] ?>">
            <input type="hidden" name="tel" value="<?php echo $_POST['tel'] ?>">
            <input type="hidden" name="email" value="<?php echo $_POST['email'] ?>">
            <h1 class="contact-title">登録内容確認入力</h1>
            <p>登録内容をご入力の上、「登録する」ボタンをクリックしてください。</p>
            <div>
                <div>
                    <label>お名前</label>
                    <p><?= htmlspecialchars($_POST['name']) ?></p>
                </div>
                <div>
                    <label>ふりがな</label>
                    <p><?= htmlspecialchars($_POST['kana']) ?></p>
                </div>
                <div>
                    <label>性別</label>
                    <p><?php if ($_POST['gender'] == '1') {
                            echo "男性";
                        } elseif ($_POST['gender'] == '2') {
                            echo "女性";
                        } elseif ($_POST['gender'] == '3') {
                            echo "その他";
                        } ?></p>
                </div>
                <div>
                    <label>生年月日</label>
                    <p><?= htmlspecialchars($_POST['birth_year']
                            . "年 "
                            . $_POST['birth_month']
                            . "月 "
                            . $_POST['birth_day']
                            . "日") ?></p>
                </div>
                <div>
                    <label>郵便番号</label>
                    <p><?= htmlspecialchars("〒"
                            . $_POST['postal_code']) ?></p>
                </div>
                <div>
                    <label>住所</label>
                    <p><?= htmlspecialchars($_POST['prefecture']
                            . $_POST['city_town']
                            . $_POST['building']) ?></p>
                </div>
                <div>
                    <label>電話番号</label>
                    <p><?= htmlspecialchars($_POST['tel']) ?></p>
                </div>
                <div>
                    <label>メールアドレス</label>
                    <p><?= htmlspecialchars($_POST['email']) ?></p>
                </div>
            </div>
            <input type="button" value="内容を修正する" onclick="history.back(-1)">
            <button type="submit" name="submit">登録する</button>
        </form>
    </div>
</body>

</html>