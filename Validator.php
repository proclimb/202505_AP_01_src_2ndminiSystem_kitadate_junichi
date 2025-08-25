<?php

class Validator
{
    private $error_message = [];

    public function validate($data)
    {
        $this->error_message = [];

        // 名前
        if (empty($data['name'])) {
            $this->error_message['name'] = '名前が入力されていません';
        } elseif (mb_strlen($data['name']) > 20) {
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

        // 生年月日（分割入力 or 1フィールド対応）
        $year = $month = $day = null;

        if (!empty($data['birth_year']) && !empty($data['birth_month']) && !empty($data['birth_day'])) {
            // 分割入力（input.php）
            $year  = $data['birth_year'];
            $month = $data['birth_month'];
            $day   = $data['birth_day'];
        } elseif (!empty($data['birth_date'])) {
            // 単一フィールド（edit.php）
            $parts = explode('-', $data['birth_date']);
            if (count($parts) === 3) {
                [$year, $month, $day] = $parts;
            }
        }

        if (empty($year) || empty($month) || empty($day)) {
            $this->error_message['birth_date'] = '生年月日が入力されていません';
        } elseif (!$this->isValidDate($year, $month, $day)) {
            $this->error_message['birth_date'] = '生年月日が正しくありません';
        } elseif ($this->isFutureDate($year, $month, $day)) {
            $this->error_message['birth_date'] = '生年月日が未来です';
        }

        // 郵便番号
        $raw_postal_code = $data['postal_code'] ?? '';
        $clean_postal_code = str_replace('-', '', $raw_postal_code);

        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'] ?? '')) {
            $this->error_message['postal_code'] = '郵便番号が正しくありません';
        } else {
            $master_data = $this->getPostalCodeData($clean_postal_code);
            if (empty($master_data)) {
                $this->error_message['postal_code'] = '郵便番号は存在しません';
            }
        }

        // 住所
        if (!empty($data['prefecture'])) {
            $normalized_pref = mb_convert_kana(trim($data['prefecture']), 'KVas');

            try {
                $dsn = "mysql:host=localhost;dbname=minisystem_relation;charset=utf8mb4";
                $pdo = new PDO($dsn, 'root', 'proclimb', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                $stmt = $pdo->prepare('SELECT 1 FROM address_master WHERE prefecture = BINARY :prefecture LIMIT 1');
                $stmt->bindValue(':prefecture', $normalized_pref, PDO::PARAM_STR);
                $stmt->execute();

                if (!$stmt->fetch()) {
                    $this->error_message['address'] = '都道府県が存在しません';
                }
            } catch (PDOException $e) {
                $this->error_message['address'] = '都道府県が存在しません';
            }
        }

        if (empty($data['prefecture']) || empty($data['city_town'])) {
            $this->error_message['address'] = '住所(都道府県もしくは市区町村・番地)が入力されていません';
        } elseif (mb_strlen($data['prefecture']) > 10) {
            $this->error_message['address'] = '都道府県は10文字以内で入力してください';
        } elseif (mb_strlen($data['city_town']) > 50 || mb_strlen($data['building']) > 50) {
            $this->error_message['address'] = '市区町村・番地もしくは建物名は50文字以内で入力してください';
        }

        // 電話番号
        if (empty($data['tel'])) {
            $this->error_message['tel'] = '電話番号が入力されていません';
        } elseif (
            !preg_match('/^0\\d{1,4}-\\d{1,4}-\\d{3,4}$/', $data['tel']) ||
            mb_strlen($data['tel']) < 12 ||
            mb_strlen($data['tel']) > 13
        ) {
            $this->error_message['tel'] = '電話番号は12~13桁で正しく入力してください';
        }

        // メールアドレス
        if (empty($data['email'])) {
            $this->error_message['email'] = 'メールアドレスが入力されていません';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error_message['email'] = '有効なメールアドレスを入力してください';
        }

        return empty($this->error_message);
    }

    public function getErrors()
    {
        return $this->error_message;
    }

    private function isValidDate($year, $month, $day)
    {
        return checkdate((int)$month, (int)$day, (int)$year);
    }

    private function isFutureDate($year, $month, $day)
    {
        $inputDate = DateTime::createFromFormat('Y-m-d', "$year-$month-$day");
        $currentDate = new DateTime('yesterday');
        return $inputDate > $currentDate;
    }

    private function getPostalCodeData($postal_code)
    {
        try {
            $dsn = "mysql:host=localhost;dbname=minisystem_relation;charset=utf8mb4";
            $pdo = new PDO($dsn, 'root', 'proclimb', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $stmt = $pdo->prepare('SELECT * FROM address_master WHERE postal_code = :postal_code');
            $stmt->execute([':postal_code' => $postal_code]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error_message['postal_code'] = '郵便番号の照合中にエラーが発生しました';
            return null;
        }
    }
}
