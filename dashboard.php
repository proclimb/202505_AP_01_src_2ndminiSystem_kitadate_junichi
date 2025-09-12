<?php

/**
 * ダッシュボード画面
 *
 * ** ダッシュボード画面は、TOPから遷移してきます
 *
 * ** ダッシュボードで行う処理は以下です
 * ** 1.DB接続情報、クラス定義をそれぞれのファイルから読み込む
 * ** 2.ユーザ情報を取得する
 * **   1.Userクラスをインスタスタンス化する
 * **     ＊User(設計図)に$user(実体)を付ける
 * **   2.メソッドを実行しユーザー情報を取得する
 * **     ＊システム開発演習Ⅰで、キーワード検索機能は実装しない
 * ** 3.html を描画
 * **   DBから取得した結果を <table>タグを使用して表示しています
 * **   $result が、0件の場合は、表を表示しない
 * **   ユーザ情報が有る場合は、foreach を使用して検索結果をします
 * **   編集のリンクに関しては、idの値をURLに設定してGET送信で「更新・削除」へidを渡します
 */

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'User.php';
require_once 'Sort.php';      // ソート関連の処理と sortLink() 関数を定義
require_once 'Page.php';      // ページネーション関連の処理と paginationLinks() 関数を定義

// ---------------------------------------------
// 1. リクエストパラメータ取得・初期化
// ---------------------------------------------
$nameKeyword = '';
$genderFlag  = '';
$searchPref  = '';
$sortBy      = $sortBy  ?? null;  // sort.php でセット済み
$sortOrd     = $sortOrd ?? 'asc'; // sort.php でセット済み
$page        = $page    ?? 1;     // page.php でセット済み

// 検索フォームで「検索」ボタンが押された場合
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_submit'])) {
    $nameKeyword = trim($_GET['search_name'] ?? '');
    $genderFlag  = trim($_GET['search_gender'] ?? '');
    $searchPref = trim($_GET['search_pref'] ?? '');
    // 検索時は常に1ページ目、ソートもリセット
    $sortBy  = null;
    $sortOrd = 'asc';
    $page    = 1;
} else {
    // 検索キーがある場合のみ受け取る
    $nameKeyword = trim($_GET['search_name'] ?? '');
    $genderFlag = trim($_GET['search_gender'] ?? '');
    $searchPref = trim($_GET['search_pref'] ?? '');
    // ソートとページは sort.php / page.php により既にセット済み
}

// ---------------------------------------------
// 2. ページネーション用定数・総件数数取得
// ---------------------------------------------
$userModel  = new User($pdo);
$totalCount = $userModel->countUsersWithKeyword($nameKeyword, $genderFlag, $searchPref);

// 1ページあたりの表示件数
$limit = 10;

// ページネーション用パラメータを取得 (update $page, $offset, $totalPages)
list($page, $offset, $totalPages) = getPaginationParams($totalCount, $limit);

// ---------------------------------------------
// 3. 実際のユーザー一覧を取得
// ---------------------------------------------
$users = $userModel->fetchUsersWithKeyword(
    $nameKeyword,
    $sortBy,
    $sortOrd,
    $offset,
    $limit,
    $genderFlag,
    $searchPref
);

// 3.html の描画
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="style_new.css">
    <style>
        .sortable-header {
            position: relative;
            cursor: pointer;
            text-decoration: underline;
            color: #333;
        }

        .sortable-header:hover {
            color: #0078D4;
        }

        .tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            white-space: nowrap;
            font-size: 12px;
            z-index: 10;
            transition: opacity 0.3s ease;
        }

        .sortable-header:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }
    </style>

</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>ダッシュボード</h2>
    </div>
    <form method="get" action="dashboard.php" class="name-search-form" style="width:80%; margin: 20px auto; display: flex; align-items: center; gap: 20px;">
        <!-- 名前検索 -->
        <label for="search_name">名前で検索：</label>
        <input
            type="text"
            name="search_name"
            id="search_name"
            value="<?= htmlspecialchars($nameKeyword, ENT_QUOTES) ?>"
            placeholder="名前の一部を入力"
            style="width: 16%; min-width: 120px; height: 30px;">
        <!-- 性別検索 -->
        <label for="search_gender">性別で検索：</label>
        <select name="search_gender" id="search_gender" style="min-width: 150px; height: 30px;">
            <option value="">-- 全て --</option>
            <option value="1" <?= ($genderFlag === '1') ? 'selected' : '' ?>>男性</option>
            <option value="2" <?= ($genderFlag === '2') ? 'selected' : '' ?>>女性</option>
            <option value="3" <?= ($genderFlag === '3') ? 'selected' : '' ?>>未回答</option>
        </select>
        <!-- 都道府県検索 -->
        <label for="search_pref">都道府県で検索：</label>
        <select name="search_pref" id="search_pref" style="min-width: 150px; height: 30px;">
            <option value="">-- 全て --</option>
            <?php
            $stmt = $pdo->query("SELECT DISTINCT prefecture FROM address_master ORDER BY prefecture");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                $pref = $row['prefecture'];
            ?>
                <option value="<?= htmlspecialchars($pref, ENT_QUOTES) ?>"
                    <?= ($searchPref === $pref) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pref, ENT_QUOTES) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <!-- 検索ボタン -->
        <input type="submit" name="search_submit" value="検索" style="margin-right: 40px;">
    </form>

    <!-- 5. 検索結果件数表示（テーブルの左上へ置きたいので、幅80%・中央寄せして左寄せテキスト） -->
    <div class="result-count" style="width:80%; margin: 5px auto 0;">
        検索結果：<strong><?= $totalCount ?></strong> 件 　　　▲でソート機能を使用できます（▲マウス左クリックで昇順／降順が切り替わります）
    </div>

    <!-- 6. 一覧テーブル -->
    <table class="common-table">
        <tr>
            <th>編集</th>
            <th>名前</th>
            <!-- ① ふりがな ソートリンク -->
            <th class="sortable-header">
                <?= sortLink('kana', 'ふりがな', $sortBy, $sortOrd, $nameKeyword, $genderFlag, $searchPref) ?>
                <span class="tooltip">この項目はソートが利用できます</span>
            </th>
            <th>性別</th>
            <!-- ⑤ 生年月日 ソートリンク -->
            <th class="sortable-header">
                <?= sortLink('birth_date', '生年月日', $sortBy, $sortOrd, $nameKeyword, $genderFlag, $searchPref) ?>
                <span class="tooltip">この項目はソートが利用できます</span>
            </th>
            <!-- ② 郵便番号 ソートリンク -->
            <th class="sortable-header">
                <?= sortLink('postal_code', '郵便番号', $sortBy, $sortOrd, $nameKeyword, $genderFlag, $searchPref) ?>
                <span class="tooltip">この項目はソートが利用できます</span>
            </th>
            <!-- ⑥ 住所 ソートリンク -->
            <th class="sortable-header">
                <?= sortLink('address', '住所', $sortBy, $sortOrd, $nameKeyword, $genderFlag, $searchPref) ?>
                <span class="tooltip">この項目はソートが利用できます</span>
            </th>
            <!-- ④ 電話番号 ソートリンク -->
            <th class="sortable-header">
                <?= sortLink('tel', '電話番号', $sortBy, $sortOrd, $nameKeyword, $genderFlag, $searchPref) ?>
                <span class="tooltip">この項目はソートが利用できます</span>
            </th>
            <!-- ③ メールアドレス ソートリンク -->
            <th class="sortable-header">
                <?= sortLink('email', 'メールアドレス', $sortBy, $sortOrd, $nameKeyword, $genderFlag, $searchPref) ?>
                <span class="tooltip">この項目はソートが利用できます</span>
            </th>
            <th>画像①</th>
            <th>画像②</th>
        </tr>

        <?php if (count($users) === 0): ?>
            <tr>
                <td colspan="11" style="text-align:center; padding:10px 0;">
                    該当するデータがありません。
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($users as $val): ?>
                <tr>
                    <td>
                        <a href="edit.php?id=<?= htmlspecialchars($val['id'], ENT_QUOTES) ?>">編集</a>
                    </td>
                    <td><?= htmlspecialchars($val['name'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($val['kana'], ENT_QUOTES) ?></td>
                    <td><?= $val['gender_flag'] == '1' ? '男性' : ($val['gender_flag'] == '2' ? '女性' : '未回答'); ?></td>
                    <td><?= date('Y年n月j日', htmlspecialchars(strtotime($val['birth_date']))); ?></td>
                    <td><?= htmlspecialchars($val['postal_code']); ?></td>
                    <td><?= htmlspecialchars($val['prefecture'] . $val['city_town'] . $val['building']); ?></td>
                    <td><?= htmlspecialchars($val['tel']); ?></td>
                    <td><?= htmlspecialchars($val['email']); ?></td>
                    <!-- 追加した出力部分：書類①(front_image) -->
                    <td><?php if ((int)$val['has_front'] === 1): ?>
                            <a
                                class="dl-link"
                                href="Showdocument.php?user_id=<?= urlencode($val['id']) ?>&type=front"
                                target="_blank">DL</a>
                        <?php else: ?>
                            無し
                        <?php endif; ?>
                    </td>
                    <!-- 追加した出力部分：書類②(back_image) -->
                    <td>
                        <?php if ((int)$val['has_back'] === 1): ?>
                            <a
                                class="dl-link"
                                href="Showdocument.php?user_id=<?= urlencode($val['id']) ?>&type=back"
                                target="_blank">DL</a>
                        <?php else: ?>
                            無し
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- 7. ページネーション -->
    <?= paginationLinks($page, $totalPages, $nameKeyword, $sortBy, $sortOrd, $genderFlag, $searchPref) ?>

    <!-- 8. 「TOPに戻る」ボタン -->
    <a href="index.php">
        <button type="button">TOPに戻る</button>
    </a>
</body>

</html>