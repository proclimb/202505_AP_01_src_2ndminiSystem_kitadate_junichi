<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * 登録画面
 *
 * ** 登録画面は、TOP、登録、登録確認の3画面から遷移してきます
 * ** 各画面毎の処理は以下です
 * **  1.TOP画面から遷移して来た場合
 * **    1.セッションを開始
 * **    2.値は無いので入力チェックは行わない
 * **    3.セッションの削除
 * **    4.html を描画
 * **      入力項目はからで表示する
 * **
 * **  2.登録確認画面から遷移して来た場合
 * **    1.セッションを開始
 * **    2.値は無いので入力チェックは行わない
 * **    3.セッションの削除
 * **    4.html を描画
 * **      入力項目は、登録画面で入力した値を表示する
 * **
 * **  3.登録画面から遷移してきた場合
 * **    1.セッションを開始
 * **    2.入力チェックをする
 * **      2-1.入力チェックでエラーがなかった場合は、登録確認画面へリダイレクトする
 * **      2-2.入力チェックでエラーが有った場合、次の処理を行う
 * **    3.セッションの削除
 * **    4.html を描画
 * **      入力項目は、登録画面で入力した値を表示する
 * **      エラーメッセージを表示する
 */

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'Validator.php';

// 1.セッションの開始
session_cache_limiter('none');
session_start();

// 2.変数の初期化
// *$_POSTの値があるときは初期化しない
$error_message = [];
$old = $_POST ?? [];

// 3.入力項目の入力チェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($pdo);

    // 入力項目のチェック（ファイル入力がある場合は validateFiles も併用）
    $ok = $validator->validateData('input', $_POST);
    // $ok = $ok && $validator->validateFiles($_FILES, 'input'); // ←ファイルをチェックしたい場合だけ有効化

    if ($ok) {
        $_SESSION['input_data'] = $_POST;
        header('Location: confirm.php');
        exit;
    } else {
        $error_message = $validator->getErrors() + $validator->getFileErrors();
    }
}

// 4.セッションを破棄する
// POSTでバリデーションが通った場合は confirm.php に遷移するので、ここでは破棄しない
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    session_destroy();
}

// 5.html の描画
// ** これ以降は、htmlの部分になります
// ** php の部分は、入力した値を表示する時と入力エラー時のメッセージを表示する時に使用しています
// ** html 内に、php を記載する場合は、htmlで見やすいように1行で記載する事が多いです
?>
<?php
function old($key)
{
    global $old;
    return htmlspecialchars($old[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="style_new.css">
    <script src="postalcodesearch.js"></script>
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>登録画面</h2>
    </div>
    <div>
        <form action="input.php" method="post" name="form">
            <h1 class="contact-title">登録内容入力</h1>
            <p>登録内容をご入力の上、「確認画面へ」ボタンをクリックしてください。</p>
            <div>
                <div>
                    <label>お名前<span>必須</span></label>
                    <input
                        type="text"
                        name="name"
                        placeholder="例）山田太郎"
                        value="<?= old('name') ?>">
                    <?php if (isset($error_message['name'])) : ?>
                        <div class="error-msg">
                            <?= htmlspecialchars($error_message['name']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>ふりがな<span>必須</span></label>
                    <input
                        type="text"
                        name="kana"
                        placeholder="例）やまだたろう"
                        value="<?= old('kana') ?>">
                    <?php if (isset($error_message['kana'])) : ?>
                        <div class="error-msg">
                            <?= htmlspecialchars($error_message['kana']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>性別<span>必須</span></label>
                    <?php $gender = $old['gender'] ?? '1'; ?>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender"
                            value="1"
                            <?= ($gender === '1') ? 'checked' : '' ?>>男性</label>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender"
                            value="2"
                            <?= ($gender === '2') ? 'checked' : '' ?>>女性</label>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender"
                            value="3"
                            <?= ($gender === '3') ? 'checked' : '' ?>>その他</label>
                </div>
                <div>
                    <label>生年月日<span>必須</span></label>
                    <!-- 年プルダウン -->
                    <div class="birth-selects">
                        <select name="birth_year" class="form-control">
                            <option value="">年</option>
                            <?php
                            $currentYear = (int)date('Y');
                            for ($y = $currentYear; $y >= 1900; $y--) :
                                $sel = (isset($old['birth_year'])
                                    && $old['birth_year'] == $y)
                                    ? ' selected' : ''; ?>
                                <option value="<?= $y ?>"
                                    <?= $sel ?>><?= $y ?>年</option>
                            <?php endfor ?>
                        </select>

                        <!-- 月プルダウン -->
                        <select name="birth_month" class="form-control">
                            <option value="">月</option>
                            <?php
                            for ($m = 1; $m <= 12; $m++) :
                                $sel = (isset($old['birth_month'])
                                    && $old['birth_month'] == $m)
                                    ? ' selected' : ''; ?>
                                <option value="<?= $m ?>"
                                    <?= $sel ?>><?= $m ?>月</option>
                            <?php endfor ?>
                        </select>

                        <!-- 日プルダウン -->
                        <select name="birth_day" class="form-control">
                            <option value="">日</option>
                            <?php
                            for ($d = 1; $d <= 31; $d++) :
                                $sel = (isset($old['birth_day'])
                                    && $old['birth_day'] == $d)
                                    ? ' selected' : ''; ?>
                                <option value="<?= $d ?>"
                                    <?= $sel ?>><?= $d ?>日</option>
                            <?php endfor ?>
                        </select>
                    </div>
                    <?php if (isset($error_message['birth_date'])) : ?>
                        <div class="error-msg2">
                            <?= htmlspecialchars($error_message['birth_date']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>郵便番号<span>必須</span></label>
                    <div class="postal-row">
                        <input
                            class="half-width"
                            type="text"
                            name="postal_code"
                            id="postal_code"
                            placeholder="例）100-0001"
                            value="<?= old('postal_code') ?>">
                        <button type="button"
                            class="postal-code-search"
                            id="searchAddressBtn">住所検索</button>
                    </div>
                    <?php if (isset($error_message['postal_code'])) : ?>
                        <div class="error-msg2">
                            <?= htmlspecialchars($error_message['postal_code']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>住所<span>必須</span></label>
                    <input
                        type="text"
                        name="prefecture"
                        id="prefecture"
                        placeholder="都道府県"
                        value="<?= old('prefecture') ?>">
                    <input
                        type="text"
                        name="city_town"
                        id="city_town"
                        placeholder="市区町村・番地"
                        value="<?= old('city_town') ?>">
                    <input
                        type="text"
                        name="building"
                        placeholder="建物名・部屋番号  **省略可**"
                        value="<?= old('building') ?>">
                    <?php if (isset($error_message['address'])) : ?>
                        <div class="error-msg">
                            <?= htmlspecialchars($error_message['address']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>電話番号<span>必須</span></label>
                    <input
                        type="text"
                        name="tel"
                        placeholder="例）000-0000-0000"
                        value="<?= old('tel') ?>">
                    <?php if (isset($error_message['phone'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($error_message['phone']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>メールアドレス<span>必須</span></label>
                    <input
                        type="text"
                        name="email"
                        placeholder="例）guest@example.com"
                        value="<?= old('email') ?>">
                    <?php if (isset($error_message['email'])) : ?>
                        <div class="error-msg">
                            <?= htmlspecialchars($error_message['email']) ?></div>
                    <?php endif ?>
                </div>
            </div>
            <button type="submit">確認画面へ</button>
            <a href="index.php">
                <button type="button">TOPに戻る</button>
            </a>
        </form>
    </div>
</body>

</html>