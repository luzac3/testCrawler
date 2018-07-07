<?php
echo "<form>URL:<input id='url' type='text'></fomr>\n";
echo "<form>文字列:<input id='str' type='text'></fomr>\n";

echo "<button type='submit' value='submit'>検索</button>";

$url = "https://qiita.com/shinkuFencer/items/d7546c8cbf3bbe86dab8";

// たどったページのリンクを保存する配列
$link_list = array();

$search_str = "ステータスコード";

class Crawler{
    // ドメイン
    private $domain;

    // 検索文字列
    private $search_str;

    // 文字列がマッチするまでにたどったページのリンクを格納する配列
    private $trace_link_list = array();

    // アクセスするすべてのページのリンクを格納する配列
    private $whole_url_list = array();

    // 現在の階層から取得したソースのリスト
    private $source_list = array();

    // 文字列のマッチフラグ
    private $search_matched = false;

    // サーチする階層
    private $search_level = 0;

    // リンク取得用正規表現のパターン
    // ファイルへのリンク、同ページへのリンクは除外
    // 調査
    private $pattern_link = "@<a [^>]*?href *?= *?['|\"]( *[^#].*?[.php|.htm..|/].*?)['|\"].*?>.*?</a>@si";

    function __construct($url, $search_str){
        if(!preg_match("@^http@",$url)){
            die("正しいURLではありません");
            return;
        }

        // ドメインの取得
        preg_match_all("@^http.*://(.*?)/@",$url,$domain_arr);
        $this->$domain = $domain_arr[1][0];

        // アクセスする最初のURLを設定
        // array_push($this->$trace_link_list, $url);
        array_push($this->$whole_link_list, $url);

        // 検索用の文字列を設定
        $this->$search_str = $search_str;

        // URLを配列として渡す
        $this->crawler(array($url));
    }

    function crawler($url_list,$search_level){
        // マッチングに成功していたら処理を中止
        if($this->$search_matched){
            return;
        }

        // ソースのリストを削除
        rect($this->$source_list);

        // 現在の階層のURLを格納
        // array_push($this->$trace_link_list,$url);

        foreach($url_list -> $index as $url){
            // すでに検索したページならスキップ
            if($this->check_same_url($url)){

            }

            // サーチ先の階層を保存
            $this->trace_link_list[$search_level] = $url;

            // ここでは保存しない
            // ここで見つけた場合、サーチする階層はすでにできているので見つけたらそのまま返すだけ
            // なおサーチ方法は変わる

            // ソースを取得
            $source = file_get_contents($url);

            // サーチするリンクを格納
            array_push($this->whole_url_list,$url);

            // リンク先と自URL(親URL)をセットしたインスタンスを生成

            // リンク先のサーチ
            // メモ　サーチ関数において全リンクとのマッチをして、存在したら戻す
            if($this->search($source)){
                // マッチングしたら終了
                return;
            }

            // 取得したソースをリストに格納
            // ソースリストがメンバ変数である理由がない。メモリの無駄遣いなので毎回定義に変更
            array_push($this->source_list,$source);
        }

        foreach($this->source_list as $source){
            // リンク先を検索


            // aタグのリンク一覧を配列に格納
            preg_match_all($this->$pattern_link,$source,$link_list,PREG_SET_ORDER);

            // リンクリストをアクセス可能なURLリストに変換
            // さらにマップ(プロパティ)に格納する
            $url_list = $this->change_link($link_list);

            // 再起関数
            $this->crawler($url_list, $search_level++);
        }

        // 文字列検索用正規表現のパターン
        $pattern_search ="@".$search_str."@i";

        // 文字列が見つかったら処理を停止
        if($match_str){
            return 1;
        }

        print_r($match_str);


        // 取得したリンクをループ
        foreach ($link_list as $link){
            // リンク先の検索をする関数呼出

        }

        return $link_list;

    }

    // 受け取ったURLと、これまでに確認した全てのURLを比較し、同じURLが存在したらtrueを返す
    function check_same_url($url){
        foreach($whole_url_list as $checked_url){
            if($checked_url == $url){
                return true;
            }
        }
        return false;
    }

    // 受け取ったリンクを含む配列のURLを抽出し、変換したURLのリストを返却する
    function change_link($link_list){
        // 受け取ったリンクをループしてURLのリストを抽出

        // リンクをそれぞれのインスタンスに設定
    }

}

// インスタンス


$link_list = new Crawler($url,$search_str);

print_r($link_list);

// echo $page_str;

?>
<a id="" href=""></a>