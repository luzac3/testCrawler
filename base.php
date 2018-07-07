<?php
echo "<form>URL:<input id='url' type='text'></fomr>\n";
echo "<form>文字列:<input id='str' type='text'></fomr>\n";

echo "<button type='submit' value='submit'>検索</button>";

$url = "https://qiita.com/shinkuFencer/items/d7546c8cbf3bbe86dab8";

// たどったページのリンクを保存する配列
$link_list = array($url);


function search_str($url){
    // ソース内のリンクリストを保持する配列
    $link_list = array();

    // ソース取得
    $source = file_get_contents($url);

    // 正規表現のパターン
    $pattern = "@<a ([^>]*?)href.*?= *?['|\"](.*?)['|\"](.*?)>(.*?)</a>@si";

    // aタグのリンク一覧を配列に格納
    preg_match_all($pattern,$source,$link_list,PREG_SET_ORDER);

    return $link_list;

}

$link_list = search_str($url);

print_r($link_list);

// echo $page_str;
$search_str = "テスト";



?>
<a id="" href=""></a>