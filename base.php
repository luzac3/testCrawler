<?php
echo "<form>URL:<input id='url' type='text'></fomr>\n";
echo "<form>文字列:<input id='str' type='text'></fomr>\n";

echo "<button type='submit' value='submit'>検索</button>";

$url = "https://wolfnet-twei.sakura.ne.jp/party/html/register.html";

$search_str = "参加登録が完了いたしました。";

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
    private $pattern_link = "@(<a [^>]*?href|location\.href|<script [^>]*?src) *?= *?['|\"]( *[^#].*?)['|\"].*?(>.*?[</a>|</script>]|;)@si";

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

    function crawler($search_level){
        // 次の階層のプロパティリストを格納する配列を設定
        $this->url_property_list[$search_level + 1] = array();

        foreach($this->url_property_list[$search_level] as $url_property){
            // プロパティからURLを取得
            $url = $url_property->get_url();

            // ソースを取得
            $source = @file_get_contents($url);

            // 正常に取得できなかったらスキップ
            if(!strpos($http_response_header[0], "200")){
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
            return "no_match";
        }

        return $this->crawler(++$search_level);
    }

    // 文字列の検索を行う
    function search($source){
        if(strpos($source,$this->search_str)){
            return true;
        }
        return false;
    }

    // 受け取ったリンクを含む配列のURLを抽出し、変換したURLのリストを返却する
    function change_link($link_list,$search_level,$now_url,$traced_url){
        // リンクリストのインデックス番号
        $link_list_index = 0;

        // 受け取ったリンクをループしてURLのリストを抽出
        foreach($link_list as $index => $link){
            // 変換のためリンクを分割
            $ex_url = explode("/",$link[2]);

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
            if(preg_match("@^http@",$link[2])){
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
                $url = $ex_url_master[0] . "//" . $this ->domain . $link[2];
            }

            // 相対パスを変換
            if($url == "" && preg_match("@^\.?@",$link[2])){
                // 現在のディレクトリ位置のインデックスを取得
                $ex_now_len = count($ex_now_url) - 2;

                // ./の場合
                //if(preg_match("@^\./@",$link[2])){
                    //$ex_url[0] = $ex_now_url[$ex_now_len];
                //}

                // ../の場合
                if(preg_match("@^\.\./@",$link[2])){
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

// 階層とURL情報を保持するクラス
class UrlProperty{
    // 階層
    private $level = 0;

    // 自分を含む、辿ったURLリスト
    private $traced_url = array();

    // 自URL
    private $url = "";

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

$crawler = new Crawler($url,$search_str);


// 階層0でクローラを起動
$url_property = $crawler->crawler(0);

if($url_property == "no_match"){
    echo "一致する文字が見つかりません";
}else{
    $traced_url_str = "";
    foreach($url_property->get_traced_url() as $traced_url){
        $traced_url_str .= $traced_url . "\n→";
    }
    // 末尾の/を削除
    $traced_url_str = rtrim($traced_url_str, "→");

    echo $traced_url_str;
}

?>