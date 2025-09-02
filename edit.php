<?php
require_once 'Db.php';
require_once 'User.php';
require_once 'Validator.php'; // バリデーション用クラス

$id = $_GET['id'] ?? null;

$user = new User($pdo);
$validator = new Validator($pdo);
$error_message = [];

// 初回表示（GET）の場合はDBからデータ取得
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_POST = $user->findById($id);
}

// 更新ボタン押下時（POST）の場合
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($validator->validateData('edit', $_POST)) {
        // バリデーションOK → update.php に渡す
        require 'update.php';
        exit;
    } else {
        // バリデーションNG → エラー配列取得
        $error_message = $validator->getErrors();
    }
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
        <h2>更新・削除画面</h2>
    </div>
    <div>
        <form action="edit.php" method="post" name="edit" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $_POST['id'] ?>">
            <h1 class="contact-title">更新内容入力</h1>
            <p>更新内容をご入力の上、「更新」ボタンをクリックしてください。</p>
            <p>削除する場合は「削除」ボタンをクリックしてください。</p>
            <div>
                <div>
                    <label>お名前<span>必須</span></label>
                    <input
                        type="text"
                        name="name"
                        placeholder="例）山田太郎"
                        value="<?= htmlspecialchars($_POST['name']) ?>">
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
                        value="<?= htmlspecialchars($_POST['kana']) ?>">
                    <?php if (isset($error_message['kana'])) : ?>
                        <div class="error-msg">
                            <?= htmlspecialchars($error_message['kana']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>性別<span>必須</span></label>
                    <?php $_POST['gender_flag'] ?? '1'; ?>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender_flag"
                            value='1'
                            <?= ($_POST['gender_flag'] ?? '1') == '1'
                                ? 'checked' : '' ?>>男性</label>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender_flag"
                            value='2'
                            <?= ($_POST['gender_flag'] ?? '') == '2'
                                ? 'checked' : '' ?>>女性</label>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender_flag"
                            value='3'
                            <?= ($_POST['gender_flag'] ?? '') == '3'
                                ? 'checked' : '' ?>>その他</label>
                </div>
                <div>
                    <label>生年月日<span>必須</span></label>
                    <input
                        type="text"
                        name="birth_date"
                        value="<?php echo $_POST['birth_date'] ?>"
                        readonly
                        class="readonly-field">
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
                            value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                        <button type="button"
                            class="postal-code-search"
                            id="searchAddressBtn">住所検索</button>
                    </div>
                </div>
                <?php if (isset($error_message['postal_code'])) : ?>
                    <div class="error-msg2">
                        <?= htmlspecialchars($error_message['postal_code']) ?>
                    </div>
                <?php endif ?>
                <div>
                    <label>住所<span>必須</span></label>
                    <input
                        type="text"
                        name="prefecture"
                        id="prefecture"
                        placeholder="都道府県"
                        value="<?= htmlspecialchars($_POST['prefecture'] ?? '') ?>">
                    <input
                        type="text"
                        name="city_town"
                        id="city_town"
                        placeholder="市区町村・番地"
                        value="<?= htmlspecialchars($_POST['city_town'] ?? '') ?>">
                    <input
                        type="text"
                        name="building"
                        placeholder="建物名・部屋番号  **省略可**"
                        value="<?= htmlspecialchars($_POST['building'] ?? '') ?>">
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
                        placeholder="例）000-000-0000"
                        value="<?= htmlspecialchars($_POST['tel']) ?>">
                    <?php if (isset($error_message['tel'])) : ?>
                        <div class="error-msg">
                            <?= htmlspecialchars($error_message['tel']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>メールアドレス<span>必須</span></label>
                    <input
                        type="text"
                        name="email"
                        placeholder="例）guest@example.com"
                        value="<?= htmlspecialchars($_POST['email']) ?>">
                    <?php if (isset($error_message['email'])) : ?>
                        <div class="error-msg">
                            <?= htmlspecialchars($error_message['email']) ?></div>
                    <?php endif ?>
                </div>
                <div>
                    <label>本人確認書類（表）</label>
                    <input
                        type="file"
                        name="document1"
                        id="document1"
                        accept="image/png, image/jpeg, image/jpg">
                    <span id="filename1" class="filename-display"></span>
                    <div class="preview-container">
                        <img id="preview1" src="#" alt="プレビュー画像１" style="display: none; max-width: 200px; margin-top: 8px;">
                    </div>
                </div>

                <div>
                    <label>本人確認書類（裏）</label>
                    <input
                        type="file"
                        name="document2"
                        id="document2"
                        accept="image/png, image/jpeg, image/jpg">
                    <span id="filename2" class="filename-display"></span>
                    <div class="preview-container">
                        <img id="preview2" src="#" alt="プレビュー画像２" style="display: none; max-width: 200px; margin-top: 8px;">
                    </div>
                </div>
            </div>
            <button type="submit">更新</button>
            <input type="button" value="ダッシュボードに戻る" onclick="location.href='dashboard.php'">
        </form>
        <form action="delete.php" method="post" name="delete">
            <input type="hidden" name="id" value="<?php echo $_POST['id'] ?>">
            <button type="submit">削除</button>
        </form>
    </div>
</body>

</html>