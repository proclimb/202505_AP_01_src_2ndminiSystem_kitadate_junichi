<?php

class Validator
{
    private $error_message = [];

    // 呼び出し元で使う
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
            $this->error_message['kana'] = 'ひらがなを入れてください';
        } elseif (mb_strlen($data['kana']) > 20) {
            $this->error_message['kana'] = 'ふりがなは20文字以内で入力してください';
        }

        $birth_year = $data['birth_year'] ?? '';
        $birth_month = $data['birth_month'] ?? '';
        $birth_day = $data['birth_day'] ?? '';

        // 生年月日
        if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
            $this->error_message['birth_date'] = '生年月日が入力されていません';
        } elseif (!$this->isValidDate($data['birth_year'] ?? '', $data['birth_month'] ?? '', $data['birth_day'] ?? '')) {
            $this->error_message['birth_date'] = '生年月日が正しくありません';
        } else {
            $birth_timestamp = strtotime("{$birth_year}-{$birth_month}-{$birth_day}");
            $now_timestamp = strtotime(date('Y-m-d'));
            if ($birth_timestamp  >= $now_timestamp) {
                $this->error_message['birth_date'] = '生年月日が未来です';
            }
        }

        // 郵便番号
        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'] ?? '')) {
            $this->error_message['postal_code'] = '郵便番号が正しくありません';
        }

        // 住所
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
            !preg_match('/^0\d{1,4}-\d{1,4}-\d{3,4}$/', $data['tel'] ?? '') ||
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


    // エラーメッセージ取得
    public function getErrors()
    {
        return $this->error_message;
    }

    // 生年月日の日付整合性チェック
    private function isValidDate($year, $month, $day)
    {
        return checkdate((int)$month, (int)$day, (int)$year);
    }
}
