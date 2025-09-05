<?php
// sort.php
// ──────────────────────────────────────────
// ダッシュボードの「ふりがな・郵便番号・メールアドレス」の
// ソート関連パラメータを処理し、
// ソートリンクを生成する関数を提供する。
// ──────────────────────────────────────────

/**
 * 1. GET パラメータからソートキー・ソート順を取得・バリデーション
 */
$allowedSortKeys = ['kana', 'postal_code', 'email'];

// sort_by の正当性チェック
if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowedSortKeys, true)) {
    $sortBy = $_GET['sort_by'];
} else {
    $sortBy = null;
}

// sort_order の正当性チェック
if (isset($_GET['sort_order']) && in_array(strtolower($_GET['sort_order']), ['asc', 'desc'], true)) {
    $sortOrd = strtolower($_GET['sort_order']);
} else {
    $sortOrd = 'asc';
}

/**
 * 2. テーブルヘッダー用のソートリンクを生成する関数
 *
 * @param string      $column          ソート対象カラム名 ("kana"|"postal_code"|"email")
 * @param string      $label           ヘッダーに表示するテキスト（漢字・ひらがな等）
 * @param string|null $currentSortBy   現在適用中のソートキー
 * @param string|null $currentSortOrd  現在適用中のソート順 ("asc"|"desc")
 * @param string      $nameKeyword     現在の検索キーワード
 * @return string                     <a>タグ形式のリンク HTML
 */
function sortLink(string $column, string $label, ?string $currentSortBy, ?string $currentSortOrd, string $nameKeyword): string
{
    // 今のソートキーと同じなら矢印を表示
    $arrow = '▲';
    if ($currentSortBy === $column) {
        $arrow = ($currentSortOrd === 'asc') ? ' ▲' : ' ▼';
    }

    // GET パラメータを構築
    $params = [];
    // 検索キーワードが空文字でなければ検索状態を維持
    if ($nameKeyword !== '') {
        $params['search_name']   = $nameKeyword;
        $params['search_submit'] = '検索';
    }

    // 同じカラムをクリックされたら "asc" ↔ "desc" をトグル
    if ($currentSortBy === $column && $currentSortOrd === 'asc') {
        $params['sort_order'] = 'desc';
    } else {
        $params['sort_order'] = 'asc';
    }
    $params['sort_by'] = $column;

    // ソート後は常に1ページ目に戻す
    $params['page'] = 1;

    // クエリ文字列を生成して URL を作る
    $qs  = http_build_query($params, '', '&amp;');
    $url = "dashboard.php?$qs";

    // <a>タグを返す
    return "<a href=\"$url\">{$label}{$arrow}</a>";
}
