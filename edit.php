<?php

/**
 * 更新・削除画面
 *
 * ** 更新・削除画面は、ダッシュボード、更新・削除確認の2画面から遷移してきます
 * * **
 * ** 【説明】
 * **   更新・削除では、入力チェックと画面遷移をjavascriptで行います
 * **   そのため、登録の時とは違い、セッションを使用しないパターンのプログラムになります
 * **
 * ** 各画面毎の処理は以下です
 * ** 1.DB接続情報、クラス定義をそれぞれのファイルから読み込む
 * ** 2.DBからユーザ情報を取得する為、$_GETからID情報を取得する
 * ** 3.ユーザ情報を取得する
 * **   1.Userクラスをインスタスタンス化する
 * **     ＊User(設計図)に$user(実体)を付ける
 * **   2.メソッドを実行じユーザー情報を取得する
 * ** 4.html を描画
 */

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'User.php';

// 2.ダッシュボードから送信した変数を設定
$id = $_GET['id'];

// 3-1.Userクラスをインスタンス化
$user = new User($pdo);

// 3-2.UserクラスのfindById()メソッドで1件検索
$_POST = $user->findById($id);

// 4.html の描画
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="style_new.css">
    <script src="postalcodesearch.js"></script>
    <script src="contact.js"></script>
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>更新・削除画面</h2>
    </div>
    <div>
        <form action="update.php" method="post" name="edit" enctype="multipart/form-data">
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
                </div>
                <div>
                    <label>ふりがな<span>必須</span></label>
                    <input
                        type="text"
                        name="kana"
                        placeholder="例）やまだたろう"
                        value="<?= htmlspecialchars($_POST['kana']) ?>">
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
                </div>
                <div>
                    <label>電話番号<span>必須</span></label>
                    <input
                        type="text"
                        name="tel"
                        placeholder="例）000-000-0000"
                        value="<?= htmlspecialchars($_POST['tel']) ?>">
                </div>
                <div>
                    <label>メールアドレス<span>必須</span></label>
                    <input
                        type="text"
                        name="email"
                        placeholder="例）guest@example.com"
                        value="<?= htmlspecialchars($_POST['email']) ?>">
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
            <button type="button" onclick="validate()">更新</button>
            <input type="button" value="ダッシュボードに戻る" onclick="history.back(-1)">
        </form>
        <form action="delete.php" method="post" name="delete">
            <input type="hidden" name="id" value="<?php echo $_POST['id'] ?>">
            <button type="submit">削除</button>
        </form>
    </div>
</body>

</html>