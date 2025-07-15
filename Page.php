<?php
// page.php
// ──────────────────────────────────────────
// ダッシュボードの「ページネーション」関連のロジックをまとめる。
// ──────────────────────────────────────────

/**
 * 1. GET パラメータから現在のページ番号を取得（デフォルト:1）
 */
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;

/**
 * 2. ページネ―ション用のパラメータを計算する関数
 *
 * @param int $totalCount   検索結果の総件数
 * @param int $limit        １ページあたりの表示件数
 * @return array            [ $page, $offset, $totalPages ]
 */
function getPaginationParams(int $totalCount, int $limit): array
{
    global $page;

    $totalPages = (int)ceil($totalCount / $limit);
    if ($totalPages < 1) {
        $totalPages = 1;
    }

    // ページ番号が総ページ数を超えていた場合は補正
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $limit;
    return [$page, $offset, $totalPages];
}

/**
 * 3. ページネーションリンクを生成する関数
 *
 * @param int         $currentPage  現在のページ番号
 * @param int         $totalPages   総ページ数
 * @param string      $nameKeyword  検索キーワード
 * @param string|null $sortBy       ソート対象カラム
 * @param string|null $sortOrd      ソート順 ("asc"|"desc")
 * @return string                  HTML の <div class="pagination">…</div> 部分
 */
function paginationLinks(int $currentPage, int $totalPages, string $nameKeyword, ?string $sortBy, ?string $sortOrd): string
{
    // １度に表示するページ番号の数（例：5）
    $pageGroupSize = 5;
    $html = '';

    // ベースとなる GET パラメータを準備
    $baseParams = [];
    if ($nameKeyword !== '') {
        $baseParams['search_name']   = $nameKeyword;
        $baseParams['search_submit'] = '検索';
    }
    if ($sortBy !== null) {
        $baseParams['sort_by']    = $sortBy;
        $baseParams['sort_order'] = $sortOrd;
    }

    // グループ単位での開始ページ & 終了ページを計算
    $groupStart = (int)(floor(($currentPage - 1) / $pageGroupSize) * $pageGroupSize) + 1;
    $groupEnd   = min($groupStart + $pageGroupSize - 1, $totalPages);

    // 「前へ」リンク（現在ページ > 1 のときのみ表示）
    if ($currentPage > 1) {
        $prevParams = array_merge($baseParams, ['page' => $currentPage - 1]);
        $qs = http_build_query($prevParams, '', '&amp;');
        $html .= "<a href=\"dashboard.php?$qs\">&lt; 前へ</a> ";
    }

    // グループ内のページ番号リンク
    for ($p = $groupStart; $p <= $groupEnd; $p++) {
        if ($p === $currentPage) {
            // カレントページは <strong> で強調
            $html .= "<strong>$p</strong> ";
        } else {
            $linkParams = array_merge($baseParams, ['page' => $p]);
            $qs = http_build_query($linkParams, '', '&amp;');
            $html .= "<a href=\"dashboard.php?$qs\">$p</a> ";
        }
    }

    // 「次へ」リンク（現在ページ < 総ページ数 のときのみ表示）
    if ($currentPage < $totalPages) {
        $nextParams = array_merge($baseParams, ['page' => $currentPage + 1]);
        $qs = http_build_query($nextParams, '', '&amp;');
        $html .= "<a href=\"dashboard.php?$qs\">次へ &gt;</a>";
    }

    // 総ページ数が1以下（＝ページ遷移不要）なら空文字を返す
    if ($totalPages <= 1) {
        return '';
    }

    // HTML を <div> で囲んで返す
    return "<div class=\"pagination\" style=\"width:80%; margin: 10px auto; text-align:center;\">$html</div>";
}
