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
// Page クラスに pagination デフォルト処理を追加
class Page
{

    public $page = 1;
    public $per_page = 20;
    public $total = 0;
    public $total_pages = 1;

    public function normalizePagination($pagination)
    {
        $this->page = isset($pagination['page']) ? max(1, (int)$pagination['page']) : 1;
        $this->per_page = isset($pagination['per_page']) ? max(1, (int)$pagination['per_page']) : 20;
        $this->total = isset($pagination['total']) ? max(0, (int)$pagination['total']) : 0;
        $this->total_pages = max(1, (int)ceil($this->total / $this->per_page));

        return [
            'page' => $this->page,
            'per_page' => $this->per_page,
            'total' => $this->total,
            'total_pages' => $this->total_pages
        ];
    }

    // ページリンク描画メソッド
    public function renderPaginationHtml($currentPage, $pageGroupSize, $totalPages, $baseParams = [])
    {
        $html = '';

        $groupStart = (int)(floor(($currentPage - 1) / $pageGroupSize) * $pageGroupSize) + 1;
        $groupEnd = min($groupStart + $pageGroupSize - 1, $totalPages);

        // 常時表示するアクション群
        $defaultQs = http_build_query([], '', '&amp;');
        $html .= '<span class="pager-actions">';
        $html .= "<a class=\"btn\" href=\"dashboard.php?$defaultQs\">デフォルトのページに戻る</a> ";

        $keepParams = array_merge($baseParams, ['page' => 1]);
        $keepQs = http_build_query($keepParams, '', '&amp;');
        $html .= "<a class=\"btn primary\" href=\"dashboard.php?$keepQs\">フィルタを維持して先頭に戻る</a>";
        $html .= '</span> ';

        // 「最初へ」
        if ($currentPage > 1) {
            $qs = http_build_query(array_merge($baseParams, ['page' => 1]), '', '&amp;');
            $html .= "<a href=\"dashboard.php?$qs\">≪ 最初へ</a> ";
        } else {
            $html .= "<span class=\"disabled\" aria-disabled=\"true\">≪ 最初へ</span> ";
        }

        // 「前へ」
        if ($currentPage > 1) {
            $qs = http_build_query(array_merge($baseParams, ['page' => $currentPage - 1]), '', '&amp;');
            $html .= "<a href=\"dashboard.php?$qs\">&lt; 前へ</a> ";
        } else {
            $html .= "<span class=\"disabled\" aria-disabled=\"true\">&lt; 前へ</span> ";
        }

        // グループ内ページ番号
        for ($p = $groupStart; $p <= $groupEnd; $p++) {
            if ($p === $currentPage) {
                $html .= "<strong>" . htmlspecialchars((string)$p, ENT_QUOTES, 'UTF-8') . "</strong> ";
            } else {
                $qs = http_build_query(array_merge($baseParams, ['page' => $p]), '', '&amp;');
                $html .= "<a href=\"dashboard.php?$qs\">" . htmlspecialchars((string)$p, ENT_QUOTES, 'UTF-8') . "</a> ";
            }
        }

        // 「次へ」
        if ($currentPage < $totalPages) {
            $qs = http_build_query(array_merge($baseParams, ['page' => $currentPage + 1]), '', '&amp;');
            $html .= "<a href=\"dashboard.php?$qs\">次へ &gt;</a> ";
        } else {
            $html .= "<span class=\"disabled\" aria-disabled=\"true\">次へ &gt;</span> ";
        }

        // 「最後へ」
        if ($currentPage < $totalPages) {
            $qs = http_build_query(array_merge($baseParams, ['page' => $totalPages]), '', '&amp;');
            $html .= "<a href=\"dashboard.php?$qs\">最後へ ≫</a>";
        } else {
            $html .= "<span class=\"disabled\" aria-disabled=\"true\">最後へ ≫</span>";
        }

        // ページ情報表示（常時）
        $html .= ' <span class="pager-info">' . htmlspecialchars("{$currentPage} / {$totalPages}", ENT_QUOTES, 'UTF-8') . '</span>';

        return "<div class=\"pagination\">$html</div>";
    }
}
