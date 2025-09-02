<?php

/**
 * 共通バリデーション（副作用なし）
 * 使い方: 呼び出し側で Db.php と一緒に require して new してください
 */
class Validator
{
    /** @var PDO|null */
    private $pdo;

    /** @var array */
    private $error_message = [];

    /** @var array */
    private $error_message_files = [];

    // ファイル条件
    const MAX_FILE_SIZE = 2097152; // 2MB
    const ALLOWED_EXT   = ['png', 'jpg', 'jpeg'];
    const ALLOWED_MIME  = ['image/png', 'image/jpeg', 'image/pjpeg'];

    /**
     * @param PDO|null $pdo 省略時は global $pdo を使用（あれば）
     */
    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
        } elseif (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->pdo = $GLOBALS['pdo'];
        } else {
            $this->pdo = null; // DB未接続でも動く（郵便番号DB照合はスキップ）
        }
    }

    /* ========== 公開API ========== */

    /** 入力値（テキスト系） */
    public function validateData($context, array $data)
    {
        $this->error_message = [];
        $ctx = strtolower($context ?: 'input');

        // 共通トリム
        $t = function ($k) use ($data) {
            return isset($data[$k]) ? trim((string)$data[$k]) : '';
        };

        // 名前
        $name = $t('name');
        if ($name === '') {
            $this->error_message['name'] = '名前が入力されていません';
        } elseif (mb_strlen($name) > 20) {
            $this->error_message['name'] = '名前は20文字以内で入力してください';
        }

        // ふりがな
        if (empty($data['kana'])) {
            $this->error_message['kana'] = 'ふりがなが入力されていません';
        } elseif (preg_match('/[^ぁ-んー]/u', $data['kana'])) {
            $this->error_message['kana'] = 'ひらがなで入力してください';
        } elseif (mb_strlen($data['kana']) > 20) {
            $this->error_message['kana'] = 'ふりがなは20文字以内で入力してください';
        }

        // メール
        $email = $t('email');
        if ($email === '') {
            $this->error_message['email'] = 'メールアドレスが入力されていません';
        } elseif (!$this->isValidEmail($email)) {
            $this->error_message['email'] = '有効なメールアドレスを入力してください';
        }

        // 電話（数字のみ or ハイフン）
        $tel = $t('phone') !== '' ? $t('phone') : $t('tel');
        if ($tel === '') {
            $this->error_message['phone'] = '電話番号が入力されていません';
        } elseif (!$this->isValidTel($tel)) {
            $this->error_message['phone'] = '電話番号は12～13桁で正しく入力してください';
        }

        // 郵便番号（7桁正規化→DB照合）
        $postalRaw = $t('postal_code');
        $postal7   = $this->normalizePostalCode($postalRaw);

        // 1. 未入力チェック
        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        }
        // 2. 形式チェック（3桁-4桁）
        elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が正しくありません';
        }
        // 3. DB照合
        elseif (!$this->postalCodeExistsInDb($postal7)) {
            $this->error_message['postal_code'] = '郵便番号が存在しません';
        }


        /* 住所（都道府県 / 市区町村・番地（city_town or city+town）） 要件定義書・基本設計書に合わせる */
        /* $pref = $t('prefecture');
        $cityTown = $t('city_town');
        if ($cityTown === '') {
            // city と town が分かれて送られてきた場合は連結して評価
            $cityTown = trim($t('city') . ' ' . $t('town'));
        }

        if ($pref === '' || $pref === '都道府県を選択') {
            $this->error_message['prefecture'] = '都道府県を選択してください';
        }
        if ($cityTown === '') {
            $this->error_message['address'] = '市区町村・番地を入力してください';
        }

         住所1
        $addr1 = $t('address1') !== '' ? $t('address1') : $t('address_line1');
        if ($addr1 === '') {
            $this->error_message['address1'] = '番地・建物名など（住所1）を入力してください';
        }*/

        if (!empty($data['prefecture'])) {
            $normalized_pref = mb_convert_kana(trim($data['prefecture']), 'KVas');

            try {
                $dsn = "mysql:host=localhost;dbname=minisystem_relation;charset=utf8mb4";
                $pdo = new PDO($dsn, 'root', 'proclimb', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                // 都道府県名を完全一致で照合
                $stmt = $pdo->prepare('SELECT 1 FROM address_master WHERE prefecture = BINARY :prefecture LIMIT 1');
                $stmt->bindValue(':prefecture', $normalized_pref, PDO::PARAM_STR);
                $stmt->execute();

                if (!$stmt->fetch()) {
                    $this->error_message['address'] = '都道府県名が存在しません';
                }
            } catch (PDOException $e) {
                // error_log($e->getMessage());
                $this->error_message['address'] = '都道府県名が存在しません';
            }
        }
        if (empty($data['prefecture']) || empty($data['city_town'])) {
            $this->error_message['address'] = '住所(都道府県もしくは市区町村・番地)が入力されていません';
        } elseif (mb_strlen($data['prefecture']) > 10) {
            $this->error_message['address'] = '都道府県は10文字以内で入力してください';
        } elseif (mb_strlen($data['city_town']) > 50 || mb_strlen($data['building']) > 50) {
            $this->error_message['address'] = '市区町村・番地もしくは建物名は50文字以内で入力してください';
        }

        // 生年月日（今日以降はNG）
        $birth = $this->parseBirthDate($data);
        if ($birth === 'missing') {
            $this->error_message['birth_date'] = '生年月日が入力されていません';
        } elseif ($birth === 'invalid') {
            $this->error_message['birth_date'] = '生年月日が正しくありません';
        } elseif ($birth instanceof DateTime) {
            $today = new DateTime('today');
            if ($birth >= $today) {
                $this->error_message['birth_date'] = '生年月日が未来です';
            }
        }

        return empty($this->error_message);
    }

    /** ファイル（document1/document2） */
    public function validateFiles(array $files, $context = 'input')
    {
        $this->error_message_files = [];
        $ctx = strtolower($context ?: 'input');

        foreach (['document1', 'document2'] as $key) {
            $f = isset($files[$key]) ? $files[$key] : null;

            // 新規は必須、編集は未選択OK
            $required = ($ctx === 'input');

            if (!$f || (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE)) {
                if ($required) {
                    $this->error_message_files[$key] = 'ファイルを選択してください';
                }
                continue;
            }

            if ($f['error'] !== UPLOAD_ERR_OK) {
                $this->error_message_files[$key] = $this->phpUploadErrorMessage($f['error']);
                continue;
            }

            if (!$this->isValidFilesize($f)) {
                $this->error_message_files[$key] = '2MB以上はアップロードできません';
                continue;
            }

            if (!$this->isValidFileExtension($f) || !$this->isValidMime($f)) {
                $this->error_message_files[$key] = 'ファイル形式は PNG, JPEG, JPG のいずれかのみ許可されています';
                continue;
            }
        }

        return empty($this->error_message_files);
    }

    public function getErrors()
    {
        return $this->error_message;
    }
    public function getFileErrors()
    {
        return $this->error_message_files;
    }

    /* ========== 内部ヘルパ ========== */

    private function isValidEmail($email)
    {
        $email = trim($email);
        if (strlen($email) > 255) return false;
        $re = '/^[A-Za-z0-9][A-Za-z0-9_.+-]*@[A-Za-z0-9_.-]+(\.[A-Za-z0-9]+)+$/u';
        return (bool)preg_match($re, $email);
    }

    private function isValidTel($tel)
    {
        $tel = trim($tel);
        $telHalf = mb_convert_kana($tel, 'n'); // 全角→半角
        $digits = preg_replace('/\D/', '', $telHalf); // 数字だけ

        // 1) 数字だけの妥当性（先頭0、10〜11桁）
        if (!preg_match('/^0\d{9,10}$/', $digits)) {
            return false;
        }

        // 2) ハイフンを含む場合の妥当性
        if (strpos($telHalf, '-') !== false) {
            // ハイフンはちょうど2個、全体文字数は12〜13
            if (substr_count($telHalf, '-') !== 2) {
                return false;
            }
            $len = strlen($telHalf);
            if ($len < 12 || $len > 13) {
                return false;
            }
            // セグメントの基本形（市外/市内/加入者の区切り）
            if (!preg_match('/^0\d{1,4}-\d{1,4}-\d{3,4}$/', $telHalf)) {
                return false;
            }
        }

        return true;
    }

    private function normalizePostalCode($s)
    {
        $s = mb_convert_kana((string)$s, 'n');
        $s = preg_replace('/\D+/', '', $s);
        return $s;
    }

    private function postalCodeExistsInDb($seven)
    {
        if (!($this->pdo instanceof PDO)) {
            return true; // DB未接続なら照合しない
        }
        $sql = 'SELECT 1 FROM address_master WHERE postal_code = :pc LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pc' => $seven]);
        return (bool)$stmt->fetchColumn();
    }

    private function parseBirthDate(array $data)
    {
        $ymd = null;

        // birth_date（単一フィールド）優先
        if (!empty($data['birth_date'])) {
            $ymd = trim((string)$data['birth_date']);
            if ($ymd === '') {
                return 'missing';
            }

            // 形式チェック（YYYY-MM-DD）
            $parts = explode('-', $ymd);
            if (count($parts) === 3) {
                $y = (int)$parts[0];
                $m = (int)$parts[1];
                $d = (int)$parts[2];
                if (!checkdate($m, $d, $y)) {
                    return 'invalid';
                }
                return DateTime::createFromFormat('Y-m-d', $ymd);
            } else {
                return 'invalid';
            }
        } elseif (
            empty($data['birth_year']) &&
            empty($data['birth_month']) &&
            empty($data['birth_day'])
        ) {
            return 'missing';
        } elseif (isset($data['birth_year'], $data['birth_month'], $data['birth_day'])) {
            $y = (int)$data['birth_year'];
            $m = (int)$data['birth_month'];
            $d = (int)$data['birth_day'];
            if (!checkdate($m, $d, $y)) {
                return 'invalid';
            }
            $ymd = sprintf('%04d-%02d-%02d', $y, $m, $d);
            return DateTime::createFromFormat('Y-m-d', $ymd);
        }

        return 'invalid';
    }

    private function phpUploadErrorMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return '2MB以上はアップロードできません';
            case UPLOAD_ERR_PARTIAL:
                return 'ファイルが途中で中断されました';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '一時フォルダが見つかりません';
            case UPLOAD_ERR_CANT_WRITE:
                return 'ディスクへの書き込みに失敗しました';
            case UPLOAD_ERR_EXTENSION:
                return '拡張機能によりアップロードが停止されました';
            default:
                return 'ファイルのアップロードに失敗しました';
        }
    }

    private function isValidFilesize(array $f)
    {
        return isset($f['size']) && (int)$f['size'] <= self::MAX_FILE_SIZE;
    }

    private function isValidFileExtension(array $f)
    {
        $ext = strtolower(pathinfo(isset($f['name']) ? $f['name'] : '', PATHINFO_EXTENSION));
        return in_array($ext, self::ALLOWED_EXT, true);
    }

    private function isValidMime(array $f)
    {
        $mime = isset($f['type']) ? $f['type'] : '';
        if (isset($f['tmp_name']) && is_uploaded_file($f['tmp_name'])) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $real = @finfo_file($fi, $f['tmp_name']);
                if ($real) $mime = $real;
                @finfo_close($fi);
            }
        }
        return in_array($mime, self::ALLOWED_MIME, true);
    }
}
