<?php
//ユーザー情報のDB操作処理
class User
{
    private $pdo;

    //DB接続情報
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // ユーザ登録
    public function create($data)
    {
        $sql = "INSERT INTO
                    user_base (
                    name,
                    kana,
                    gender_flag,
                    birth_date,
                    tel,
                    email,
                    created_at
                    )
                VALUES (
                    :name,
                    :kana,
                    :gender_flag,
                    :birth_date,
                    :tel,
                    :email,
                    now()
                    )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name'         => $data['name'],
            ':kana'         => $data['kana'],
            ':gender_flag'  => $data['gender_flag'],
            ':birth_date'   => $data['birth_date'],
            ':tel'          => $data['tel'],
            ':email'        => $data['email']
        ]);
        return $this->pdo->lastInsertId();
    }

    // ユーザ更新
    public function update($id, $data)
    {
        $sql = "UPDATE
                    user_base
                SET name = :name,
                    kana = :kana,
                    gender_flag = :gender_flag,
                    tel = :tel,
                    email = :email,
                    updated_at = now()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name'         => $data['name'],
            ':kana'         => $data['kana'],
            ':gender_flag'  => $data['gender_flag'],
            ':email'        => $data['email'],
            ':tel'          => $data['tel'],
            ':id'           => $id
        ]);
    }

    // ユーザ検索(1件検索)
    public function findById($id)
    {
        $sql = "SELECT
                u.id,
                u.name,
                u.kana,
                u.gender_flag,
                u.birth_date,
                u.tel,
                u.email,
                a.postal_code,
                a.prefecture,
                a.city_town,
                a.building
            FROM user_base u
            LEFT JOIN user_addresses a ON u.id = a.user_id
            WHERE u.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // ユーザ削除
    public function delete($id)
    {
        $sql = "UPDATE
            user_base
            SET del_flag = 1
            WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }



    // ユーザ検索(キーワード検索、全件検索)
    // ＊システム開発演習Ⅰで、キーワード検索機能は実装しない
    public function search($keyword = '')
    {
        $sql = "SELECT
                u.id,
                u.name,
                u.kana,
                u.gender_flag,
                u.birth_date,
                u.tel,
                u.email,
                a.postal_code,
                a.prefecture,
                a.city_town,
                a.building,
                (ud.front_image IS NOT NULL) AS has_front,
                (ud.back_image  IS NOT NULL) AS has_back
            FROM user_base u
            LEFT JOIN user_addresses a
            ON u.id = a.user_id

            LEFT JOIN (
                SELECT
                    ud2.user_id,
                    ud2.front_image,
                    ud2.back_image
                  FROM user_documents AS ud2
                 INNER JOIN (
                     SELECT
                       user_id,
                       MAX(created_at) AS max_created
                     FROM user_documents
                     GROUP BY user_id
                 ) AS latest
                   ON ud2.user_id = latest.user_id
                  AND ud2.created_at = latest.max_created
            ) AS ud
              ON u.id = ud.user_id

            WHERE u.del_flag = 0
            ";

        if ($keyword) {
            $sql .= " AND u.name LIKE :keyword";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':keyword' => "%{$keyword}%"]);
        } else {
            $stmt = $this->pdo->query($sql);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ① キーワード検索後の「総件数」を返すメソッド
     *
     * @param string|null $keyword  名前の部分一致キーワード（空文字 or null は検索なし＝全件）
     * @return int                  マッチしたレコード数
     */
    public function countUsersWithKeyword(?string $keyword): int
    {
        $sql = "SELECT COUNT(*) AS cnt
                  FROM user_base u
                  LEFT JOIN user_addresses a
                    ON u.id = a.user_id
                  LEFT JOIN (
                        SELECT
                            ud2.user_id,
                            ud2.front_image,
                            ud2.back_image
                          FROM user_documents AS ud2
                         INNER JOIN (
                             SELECT
                                 user_id,
                                 MAX(created_at) AS max_created
                             FROM user_documents
                             GROUP BY user_id
                         ) AS latest
                           ON ud2.user_id = latest.user_id
                          AND ud2.created_at = latest.max_created
                    ) AS ud
                    ON u.id = ud.user_id
                 WHERE u.del_flag = 0
        ";
        $params = [];
        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= " AND u.name LIKE :keyword ";
            $params[':keyword'] = '%' . trim($keyword) . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        if (isset($params[':keyword'])) {
            $stmt->bindValue(':keyword', $params[':keyword'], PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['cnt'];
    }


    /**
     * ② キーワード検索＋ソート＋ページネーションでユーザー一覧を取得する
     *
     * @param string|null $keyword   名前の部分一致キーワード（空文字 or null で全件）
     * @param string|null $sortBy    ソート対象カラム名: 'kana' or 'postal_code' or 'email'
     * @param string|null $sortOrder 'asc' or 'desc'
     * @param int         $offset    SQL OFFSET
     * @param int         $limit     SQL LIMIT
     * @return array                  取得したユーザー一覧（連想配列の行リスト）
     */
    public function fetchUsersWithKeyword(
        ?string $keyword,
        ?string $sortBy,
        ?string $sortOrder,
        int $offset,
        int $limit
    ): array {
        // 基本の SELECT 文（search() と同様の JOIN 構造）
        $sql = "SELECT
                    u.id,
                    u.name,
                    u.kana,           -- ふりがな
                    u.gender_flag,
                    u.birth_date,
                    u.tel,
                    u.email,
                    a.postal_code,    -- 郵便番号
                    a.prefecture,
                    a.city_town,
                    a.building,
                    (ud.front_image IS NOT NULL) AS has_front,
                    (ud.back_image  IS NOT NULL) AS has_back
                FROM user_base u
                LEFT JOIN user_addresses a
                  ON u.id = a.user_id
                LEFT JOIN (
                    SELECT
                        ud2.user_id,
                        ud2.front_image,
                        ud2.back_image
                      FROM user_documents AS ud2
                     INNER JOIN (
                         SELECT
                             user_id,
                             MAX(created_at) AS max_created
                         FROM user_documents
                         GROUP BY user_id
                     ) AS latest
                       ON ud2.user_id = latest.user_id
                      AND ud2.created_at = latest.max_created
                ) AS ud
                  ON u.id = ud.user_id
               WHERE u.del_flag = 0
        ";
        $params = [];

        // (1) キーワード検索 条件追加
        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= " AND u.name LIKE :keyword ";
            $params[':keyword'] = '%' . trim($keyword) . '%';
        }

        // (2) ソート 条件追加
        //    allowed: kana, postal_code, email
        $allowedSort = ['kana', 'postal_code', 'email'];
        if ($sortBy !== null && in_array($sortBy, $allowedSort, true)) {
            // ふりがなの場合、テーブル側では u.kana カラム
            // postal_code, email は a.postal_code / u.email
            $column = '';
            if ($sortBy === 'kana') {
                $column = 'u.kana';
            } elseif ($sortBy === 'postal_code') {
                $column = 'a.postal_code';
            } elseif ($sortBy === 'email') {
                $column = 'u.email';
            }
            $order = (strtolower($sortOrder) === 'desc') ? 'DESC' : 'ASC';
            $sql .= " ORDER BY {$column} {$order} ";
        } else {
            // デフォルト: u.id 昇順
            $sql .= " ORDER BY u.id ASC ";
        }

        // (3) LIMIT & OFFSET
        $sql .= " LIMIT :lim OFFSET :off ";

        $stmt = $this->pdo->prepare($sql);
        // バインド: キーワード
        if (isset($params[':keyword'])) {
            $stmt->bindValue(':keyword', $params[':keyword'], PDO::PARAM_STR);
        }
        // バインド: LIMIT, OFFSET
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ユーザドキュメント処理
    public function saveDocument($id, $frontBlob, $backBlob, ?string $expiresAt)
    {
        $sql = "INSERT INTO
                    user_documents
                        (
                        user_id,
                        front_image,
                        back_image,
                        expires_at,
                        created_at)
                VALUES(
                    :user_id,
                    :front_image,
                    :back_image,
                    :expires_at,
                    NOW()
                    )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':front_image', $frontBlob, PDO::PARAM_LOB);
        $stmt->bindParam(':back_image',  $backBlob,  PDO::PARAM_LOB);

        if ($expiresAt === null) {
            $stmt->bindValue(':expires_at', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
        }

        return $stmt->execute();
    }
}
