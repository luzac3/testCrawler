window.onload = function(){
    const search = document.getElementById("search");

    // ボタンのクリックイベントを登録
    search.addEventListener("click", function() {
        // フォームの内容を取得
        const url = document.getElementById("url");
        const search_str = document.getElementById("search_str");

        // 検索ページに遷移
        location.href = "/testCrawler/php/searcher.php?url=" + url + "search_str" + search_str;
    }, false);
}