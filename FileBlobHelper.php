<?php


class FileBlobHelper
{
    /**
     * アップロードされたファイルを見て、MIME チェック（image/png または image/jpeg）を行い、
     * 問題なければ BLOB（バイナリストリーム）を返す。エラー時は null。
     *
     * @param array|null $fileArr $_FILES['xxx'] の配列、もしくは null
     * @return string|null        バイナリ文字列 or null
     */
    public static function getBlobFromImage(?array $fileArr): ?string
    {
        // ファイル自体が存在しない、またはアップロードエラーがあれば null
        if (
            ! isset($fileArr)
            || ! is_array($fileArr)
            || ! isset($fileArr['error'])
            || $fileArr['error'] !== UPLOAD_ERR_OK
        ) {
            return null;
        }

        // 一時保存先が存在しない場合も null
        if (! isset($fileArr['tmp_name']) || ! is_uploaded_file($fileArr['tmp_name'])) {
            return null;
        }

        // MIME タイプを取得してチェック
        $mime = mime_content_type($fileArr['tmp_name']);
        if ($mime !== 'image/png' && $mime !== 'image/jpeg' && $mime !== 'image/jpg') {
            return null;
        }

        // 例：サイズ制限をかけたい場合はここにチェックを入れる
        // if ($fileArr['size'] > 5 * 1024 * 1024) {
        //     return null;
        // }

        // file_get_contents で BLOB として読み込む
        $blob = file_get_contents($fileArr['tmp_name']);
        if ($blob === false) {
            return null;
        }

        return $blob;
    }

    /**
     * 前後 2 つのファイルを同時に受け取り、それぞれ BLOB 化して返却する。
     * - どちらもアップロードされていなければ null
     * - 片方だけ正常な画像であれば、その片方の BLOB を返し、もう片方は null
     *
     * @param array|null $frontFiles $_FILES['document1'] など
     * @param array|null $backFiles  $_FILES['document2'] など
     * @return array|null            ['front' => string|null, 'back' => string|null] あるいはすべて null の場合は null
     */
    public static function getMultipleBlobs(?array $frontFiles, ?array $backFiles): ?array
    {
        $frontBlob = self::getBlobFromImage($frontFiles);
        $backBlob  = self::getBlobFromImage($backFiles);

        // どちらも null の場合、アップロードなしとみなして null を返す
        if ($frontBlob === null && $backBlob === null) {
            return null;
        }

        return [
            'front' => $frontBlob,
            'back'  => $backBlob,
        ];
    }
}
