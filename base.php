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
    private $whole_link_list = array();

    // 現在の階層から取得したソースのリスト
    private $source_list = array();

    // 文字列のマッチフラグ
    private $search_matched = false;

    // リンク取得用正規表現のパターン
    // ファイルへのリンク、同ページへのリンクは除外
    private $pattern_link = "@<a [^>]*?href *?= *?['|\"]( *[^#].*?[^.pdf|^.gif|^.jpg|^.png|^.avi|^.mp3|^.bmp].*?)['|\"].*?>.*?</a>@si";

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
    function crawler($url_list){
        // 現在の階層のURLを格納
        array_push($this->$trace_link_list,$url);

        // ソース内のリンクリストを保持する配列
        $link_list = array();

        // ソース取得
        $source = file_get_contents($url);

        // 文字列検索用正規表現のパターン
        $pattern_search ="@".$search_str."@i";

        // 文字列の検索
        preg_match($pattern_search,$source,$match_str);

        // 文字列が見つかったら処理を停止
        if($match_str){
            return 1;
        }

        print_r($match_str);

        // aタグのリンク一覧を配列に格納
        preg_match_all($pattern_link,$source,$link_list,PREG_SET_ORDER);

        // 取得したリンクをループ
        foreach ($link_list as $link){
            // リンク先の検索をする関数呼出

        }

        return $link_list;

    }

}

$link_list = new Crawler($url,$search_str);

print_r($link_list);

// echo $page_str;

?>
<a id="" href=""></a>