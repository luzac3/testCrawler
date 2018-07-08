<?php
echo <<< EOM
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="content-style-type" content="text/css">
<link rel="stylesheet" href="/testCrawler/css/base.css" type="text/css" media="screen">
<script type="text/javascript" src="/testCrawler/javascript/entrance.js"></script>
</head>
<body>
EOM;

if(!empty($_GET["url"]) && !empty($_GET["search_str"])){
    // urlと検索文字列を設定
    $url = $_GET["url"];
    $search_str = $_GET["search_str"];

    /**
     *
     * @author Takuto
     * メイン処理
     * URLと文字列を受け取り、リンクをたどって文字列を検索する
     *
     */
    class Crawler{
        // ドメイン
        private $domain;

        // 検索文字列
        private $search_str;

        // URL情報を階層ごとに保持する配列
        private $url_property_list = array();

        // サーチする階層
        private $search_level = 0;

        // リンク取得用正規表現のパターン
        private $pattern_link = "@(<a [^>]*?href|location\.href|<script [^>]*?src *?=|Location:) *?['|\"]( *[^#].*?)['|\"].*?(>.*?[</a>|</script>]|;)@si";

        /**
         *
         * @param string $url 前のページから連携される検索の基点となるURL
         * @param string $search_str 前のページから連携される検索対象の文字列
         */
        function __construct($url, $search_str){
            if(!preg_match("@^http.?@",$url)){
                die("正しいURLではありません");
                return;
            }

            // ドメインの取得
            preg_match_all("@^http.*://(.*?)/@",$url,$domain_arr);
            $this->domain = $domain_arr[1][0];

            // 検索用の文字列を設定
            $this->search_str = $search_str;

            // 初期URL情報を設定
            // 第0階層のURLプロパティリスト
            $this->url_property_list[0] = array(
                new UrlProperty(0,array(),$url)
            );
        }

        /**
         * 文字列の検索を行い、見つからなければ検索対象ファイルのリンクを取得する
         * たどれるリンクがなくなっても発見できなければfalseを返す
         * @param integer @search_level 現在検索中の階層
         * @return array|boolean 文字列を発見した段階でのURLプロパティリスト　発見できなかった場合はfalse
         */
        function crawler($search_level){
            // 次の階層のプロパティリストを格納する配列を設定
            $this->url_property_list[$search_level + 1] = array();

            foreach($this->url_property_list[$search_level] as $url_property){
                // プロパティからURLを取得
                $url = $url_property->get_url();

                // ソースを取得
                $source = @file_get_contents($url);

                // 正常に取得できなかったらスキップ
                if(!$source){
                    continue;
                }

                // リンク先のサーチ
                // メモ　サーチ関数において全リンクとのマッチをして、存在したら戻す
                if($this->search($source)){
                    // マッチングしたら終了
                    return $url_property;
                }

                // aタグのリンク一覧を配列に格納
                preg_match_all($this->pattern_link,$source,$link_list,PREG_SET_ORDER);

                // 取得したリンクのリストが空ならスキップ
                if(empty($link_list)){
                    continue;
                }

                // リンクリストをアクセス可能なURLリストに変換、プロパティリストに格納する
                $this->change_link($link_list,$search_level,$url,$url_property->get_traced_url());
            }

            // 次階層のURLプロパティが空の場合、捜索先がないためマッチングなしで終了
            if(empty($this->url_property_list[$search_level + 1])){
                return false;
            }

            return $this->crawler(++$search_level);
        }

        // 文字列の検索を行う
        /**
         *
         * @param string $source 検索中のファイル内の全文字列
         * @return boolean 文字列を発見したらtrueを返す
         */
        function search($source){
            if(strpos($source,$this->search_str)){
                return true;
            }
            return false;
        }

        /**
         * 受け取ったリンクを含む配列のURLを抽出し、変換したURLのリストをURLプロパティリストに格納する
         * @param array $link_list 検索対象のファイル内に存在するリンクのリスト
         * @param integer $search_level 基点となるファイルから見て、検索中のファイルが存在する階層
         * @param string $now_url 検索対象のファイル名
         * @param array $traced_url 基点となるファイルから、検索対象のファイルまでにたどったリンクの配列
         *
         * @return void
         */
        function change_link($link_list,$search_level,$now_url,$traced_url){
            // リンクリストのインデックス番号
            $link_list_index = 0;

            // 受け取ったリンクをループしてURLのリストを抽出
            foreach($link_list as $index => $link){
                // 変換のためリンクを分割
                $ex_url = explode("/",$link[3]);

                // ファイルがGETを前提にしている場合(?を含む場合)、その部分を削除する
                // 最後のファイル名のインデックスを取得
                $file_name_index = count($ex_url) - 1;
                $get_pos = strpos($ex_url[$file_name_index],"?");
                if($get_pos){
                    $ex_url[$file_name_index] = substr($ex_url[$file_name_index], 0, $get_pos);
                }

                // jsファイルの場合は呼び出し元ファイルを現ファイルとして扱うため変換を行う
                // 現URLファイルを配列に変換
                $now_url_arr = str_split($now_url);

                // 現URLファイルの長さを取得
                $now_url_arr_len = count($now_url_arr);

                if($now_url_arr[$now_url_arr_len-1] == "s" && $now_url_arr[$now_url_arr_len-2] == "j" && $now_url_arr[$now_url_arr_len-3] == "."){
                    // リンクをさかのぼってJSファイル以外が見つかるまで続ける
                    foreach($traced_url as $traced_url_val){
                        // たどったリンクを配列に変換
                        $traced_url_val_arr = str_split($traced_url_val);

                        // たどったリンクの長さを取得
                        $traced_url_val_arr_len = count($traced_url_val_arr);
                        if($traced_url_val_arr[$traced_url_val_arr_len-1] != "s" || $traced_url_val_arr[$traced_url_val_arr_len-2] != "j" || $traced_url_val_arr[$traced_url_val_arr_len-3] != "."){
                            // 親ファイル(JS呼び出し元ファイル)取得、現URLとして設定
                            $now_url = $traced_url_val;
                            break;
                        }
                    }
                }

                // 現URLを分割
                $ex_now_url = explode("/",$now_url);

                // urlを格納する変数を初期化
                $url = "";

                // 変換なし
                if(preg_match("@^http@",$link[3])){
                    // ドメインが違う場合はスキップ
                    if($ex_url[2] != $this->domain){
                        continue;
                    }

                    $url = $link[1];
                }

                // ルートパスを変換
                if($ex_url[0] == ""){
                    // 基点のURLからhtml/htmlsを解析
                    $ex_url_master = explode("/",$this->url_property_list[0][0]->get_url());
                    $url = $ex_url_master[0] . "//" . $this ->domain . $link[3];
                }

                // 相対パスを変換
                if($url == "" && preg_match("@^\.?@",$link[3])){
                    // 現在のディレクトリ位置のインデックスを取得
                    $ex_now_len = count($ex_now_url) - 2;

                    // ./の場合
                    //if(preg_match("@^\./@",$link[3])){
                        //$ex_url[0] = $ex_now_url[$ex_now_len];
                    //}

                    // ../の場合
                    if(preg_match("@^\.\./@",$link[3])){
                        foreach($ex_url as $index => $directory){
                            if($ex_url[$index] == ".."){
                                // $ex_url[$i] = $ex_now_url[$ex_now_len--];
                                // 階層を一つ戻る
                                $ex_now_len--;
                            }
                        }
                    }

                    for($i = 0; $i <= $ex_now_len; $i++){
                        $url .= $ex_now_url[$i] . "/";
                    }

                    foreach($ex_url as $directory){
                        if($directory == "." || $directory == ".."){
                            continue;
                        }
                        $url .= $directory ."/";
                    }

                    // 末尾の/を削除
                    $url = rtrim($url, "/");
                }

                // ここまでで当てはまらなかった場合はスキップ
                if(!$url){
                    continue;
                }

                // 該当リンクが既に設定されている場合は除外
                foreach($this->url_property_list as $url_property_level_list){
                    foreach($url_property_level_list as $url_property){
                        if($url_property->get_url() == $url){
                            continue 3;
                        }
                    }
                }

                array_push($this->url_property_list[$search_level + 1], new UrlProperty(
                    $search_level + 1
                    ,$traced_url
                    ,$url
                ));
            }
        }

    }

    /**
     * 階層とURL情報を保持するクラス
     */
    class UrlProperty{
        // 階層
        private $level = 0;

        // 自分を含む、辿ったURLリスト
        private $traced_url = array();

        // 自URL
        private $url = "";

        /**
         *
         * @param integer $level 基点から見た、自分の階層
         * @param array $traced_url 基点から、自分の階層に至るまでにたどったリンクのリスト
         * @param string $url 自分のURL
         */
        function __construct($level,$traced_url,$url){
            // 階層を設定
            $this->level = $level;

            // 今までたどったURLを設定
            $this->traced_url = $traced_url;

            // 自分のULRをセット
            $this->url = $url;

            // 自分のURLをtracedUrlに追加
            array_push($this->traced_url,$url);
        }

        // 階層を取得
        function get_level(){
            return $this->level;
        }

        // 親URLを取得
        function get_traced_url(){
            return $this->traced_url;
        }

        // 自URLを取得
        function get_url(){
            return $this->url;
        }
    }

    /**
     * 関数呼び出し部分
     */
    // クローラをインスタンス化、URLと検索文字列をセット
    $crawler = new Crawler($url,$search_str);

    // 階層0でクローラを起動
    $url_property = $crawler->crawler(0);

    if(!$url_property){
        echo "<p>一致する文字が見つかりません</p>";
    }else{
        $traced_url_str = "<p>[結果]</p>";
        foreach($url_property->get_traced_url() as $traced_url){
            $traced_url_str .= "\n<p>" . $traced_url . "</p>→";
        }
        // 末尾の/を削除
        $traced_url_str = rtrim($traced_url_str, "→");

        echo $traced_url_str;
    }
}else{
    echo "<p>URLと文字列を指定してください</p>";
}
echo <<< EOM
<p><a href="/testCrawler/html/entrance.html">戻る</a>
</body>
</html>
EOM;
?>