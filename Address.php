<?php
class UserAddress
{
    private $pdo;

    //DB接続情報
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // 住所登録
    public function create(array $data): bool
    {
        $sql = "INSERT INTO user_addresses (user_id, postal_code, prefecture, city_town, building, created_at)
                VALUES (:user_id, :postal_code, :prefecture, :city_town, :building, NOW())";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':user_id'     => $data['user_id'],
                ':postal_code' => $data['postal_code'],
                ':prefecture'    => $data['prefecture'],
                ':city_town'    => $data['city_town'],
                ':building'    => $data['building'],
            ]);

            // Logger::logSQL($sql, $data);
            return $result;
        } catch (PDOException $e) {
            // Logger::logSQLError($e->getMessage(), $sql);
            return false;
        }
    }

    // ユーザーIDから住所取得
    public function findByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM addresses WHERE user_id = :user_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);
            return $address ?: null;
        } catch (PDOException $e) {
            // Logger::logSQLError($e->getMessage(), $sql);
            return null;
        }
    }

    // ユーザーIDで住所更新
    public function updateByUserId(array $data): bool
    {
        $sql = "UPDATE user_addresses
                SET postal_code = :postal_code,
                    prefecture = :prefecture,
                    city_town = :city_town,
                    building = :building,
                    created_at = NOW()
                WHERE user_id = :user_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $params = [
                ':postal_code' => $data['postal_code'],
                ':prefecture'    => $data['prefecture'],
                ':city_town'    => $data['city_town'],
                ':building'    => $data['building'],
                ':user_id'     => $data['user_id']
            ];
            $result = $stmt->execute($params);

            // Logger::logSQL($sql, $params);
            return $result;
        } catch (PDOException $e) {
            // Logger::logSQLError($e->getMessage(), $sql);
            return false;
        }
    }
}
