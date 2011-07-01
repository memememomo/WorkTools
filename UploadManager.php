<?php

class UploadManager2 {

    // 画像ファイルがあるディレクトリ
    public $image_dir;

    // 画像ファイル名
    public $image_name;

    // プレフィックス、サフィックス、拡張子
    public $prefix, $suffix, $ext;

    // HTTPリクエスト
    public $req;


    /**
     * コンストラクタ
     *
     */

    public function __construct(&$req, $image_dir, $image_name, $ext, $prefix = '', $suffix = '') {
        $this->req        = $req;
        $this->image_dir  = $image_dir;
        $this->image_name = $image_name;
        $this->ext        = $ext;
        $this->prefix     = $prefix;
        $this->suffix     = $suffix;
    }


    /**
     * 編集時に既存ファイルを一時ファイルにする
     *
     */

    public function remakeTmpImage($id) {

        $req_serial_name = $this->buildReqSerialName();

        // すでにシリアル番号が発行されていたらなにもしない
        if ( array_key_exists( $req_serial_name, $this->req ) && $this->req[ $req_serial_name ] ) {
            return false;
        }

        $image_path = $this->image_dir . '/' . $this->buildImageFileName($id);
        $serial = $this->generateSerial();
        $this->req[ $req_serial_name ] = $serial;

        if ( file_exists( $image_path ) ) {
            $tmp_image_path =
                 $this->image_dir
                . '/'
                . $this->buildTmpFileName($serial);

            copy($image_path, $tmp_image_path);
            chmod($tmp_image_path, 0777);

            $this->req[ $this->buildReqTmpFileName() ] = $this->buildTmpFileName($serial);

            return true;
        }
        else {
            return false;
        }
    }


    /**
     * アップロードされた画像から一時ファイルを作成する
     *
     */

    public function uploadTmpImage() {

        $file_name = $this->buildReqFileName();

        if ( $_FILES[ $file_name ]['tmp_name'] ) {

            $req_serial_name = $this->buildReqSerialName();
            $tmp_file_name   = $this->buildTmpFileName( $this->req[ $req_serial_name ] );


            $this->req[ $this->buildReqTmpFileName() ] = $tmp_file_name;

            $this->uploadFile(
                              $_FILES[ $file_name ],
                              $this->image_dir . '/' . $tmp_file_name,
                              '/image/i'
                              );

            return true;
        }
        else {
            return false;
        }

    }


    /**
     * 一時ファイルから正式なファイルを作成する
     *
     */

    public function uploadImage($id) {
        $temp_image_path =
            $this->image_dir
            . '/'
            . $this->buildTmpFileName( $this->req[ $this->buildReqSerialName() ] );

        if ( file_exists( $temp_image_path ) ) {
            $image_path =
                $this->image_dir
                . '/'
                . $this->buildImageFileName($id);

            copy($temp_image_path, $image_path);
            chmod($image_path, 0777);
        }

        return $this;
    }


    /**
     * 期限が過ぎたら、残骸一時ファイルを削除する
     *
     */

    public function deleteTempFile( $force = null) {
        $deltime = 60 * 15;
        $dh = opendir($this->image_dir);
        while ( $file = readdir($dh) ) {
            if ( !is_dir($file) ) {
                if ( preg_match("/^temp_.*/", $file) ) {
                    if ( (time() - filemtime($this->image_dir.'/'.$file)) > $deltime || $force === "delete" ) {
                        unlink($this->image_dir.'/'.$file);
                    }
                }
            }
        }
        closedir($dh);
    }


    /**
     * 画像ファイルを削除する
     *
     */

    public function deleteImage($id) {
        $image_path = $this->image_dir . '/' . $this->buildImageFileName($id);
        if ( file_exists( $image_path ) ) {
            unlink( $image_path );
        }
        return 1;
    }



    /**
     * 保存ファイル名を作成
     *
     */

    public function buildImageFileName($id) {
        return $this->prefix . $id . $this->suffix . '.' . $this->ext;
    }


    /**
     * 一時ファイル名を作成
     *
     */

    public function buildTmpFileName($serial) {
        return 'temp_' . $this->prefix . $serial . $this->suffix . '.' . $this->ext;
    }


    /**
     * HTTPリクエスト用のシリアル名を作成
     *
     */

    public function buildReqSerialName() {
        $serial_name = 'serial';
        if ( $this->prefix ) { $serial_name .= '_' . $this->prefix; }
        if ( $this->suffix ) { $serial_name .= '_' . $this->suffix; }
        return $serial_name;
    }


    /**
     * HTTPリクエスト用の一時ファイル名を作成
     *
     */

    public function buildReqTmpFileName() {
        $tmp_file_name = 'tmp_file_name';
        if ( $this->prefix ) { $tmp_file_name .= '_' . $this->prefix; }
        if ( $this->suffix ) { $tmp_file_name .= '_' . $this->suffix; }
        return $tmp_file_name;
    }


    /**
     * HTTPリクエスト用の画像ファイル名を作成
     *
     */

    public function buildReqFileName() {
        $req_image_name = $this->image_name;
        if ( $this->prefix ) { $req_image_name .= $this->prefix; }
        if ( $this->suffix ) { $req_image_name .= $this->suffix; }
        return $req_image_name;
    }


    /**
     * HTTPリクエストにあるシリアル番号を取得
     *
     */

    public function reqSerialValue() {
        return $this->req[ $this->buildReqSerialName() ];
    }


    /**
     * HTTPリクエストにある一時ファイル名を取得
     *
     */

    public function reqTmpFileValue() {
        return $this->req[ $this->buildReqTmpFileName() ];
    }


    /**
     * HTTPリクエストにある画像を保存する
     *
     */

    public function uploadFile($files, $create_name, $checktype) {
        if ( !preg_match( $checktype, $files['type'] ) ) {
            throw new Exception($checktype . "をアップロードしてください。" . $files['type']);
        }

        if ( copy($files['tmp_name'], $create_name) ) {
            chmod($create_name, 0777);
            return 1;
        }
        else {
            return 0;
        }
    }


    /**
     * シリアル番号を生成
     *
     */

    public function generateSerial() {
        return md5(rand());
    }



}