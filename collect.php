<?php

/**
 * 电子书单采集脚本
 *
 * 源自：http://www.xiaoshuotxt.org/
 *
 * @author weilong <github.com/wilon>
 */

use DiDom\Document;

// 设置 Exception，防止终断
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new Exception(
        "errno: $errno "
        . "errstr: $errstr "
        . "errfile: $errfile "
        . "errline: $errline "
    );
});

// init
require_once __DIR__ . '/vendor/autoload.php';

// 设置
$baseUri = 'http://www.xiaoshuotxt.org/';
$logFile = path(__DIR__, 'error.log');
$resFile = path(__DIR__, 'result.log');
$fp = file($resFile);
$classifyArr = [
    'mingzhu',    // 文学名著
    'dangdai',    // 现代小说
    'waiwen',    // 世界名著
    'ertong',    // 儿童文学
    'gudian',    // 古典名著
    'sanwen',    // 散文随笔
    'qingchun',    // 青春校园
    'pinglun',    // 文学评论
    'xuanhuan',    // 玄幻仙侠
    'yanqing',    // 言情小说
    'wuxia',    // 武侠小说
    'chuanyue',    // 穿越小说
    'xuanyi',    // 侦探悬疑
    'kehuan',    // 科幻小说
    'wangyou',    // 网游小说
    'renwen',    // 人文社科
    'zhuanji',    // 人物传记
    'lishi',    // 历史小说
    'junshi',    // 军事小说
    'lizhi',    // 励志书籍
    'shenghuo',    // 生活科普
];

// 标记
$lastCk = $lastI = $lastDk = -1;
if (($c = count($fp)) > 1) {
    $mark = $fp[$c-1];
    list($_, $lastCk, $lastI, $lastDk) = @explode(' ', $mark);
}

// 采集
foreach ($classifyArr as $ck => $classify) {
    if ($ck < $lastCk) continue;
    $uri = path($baseUri, $classify);
    for ($i = 1; $i < 9999; $i++) {
        if ($ck == $lastCk && $i < $lastI) continue;
        // url
        if ($i == 1) {
            $url = $uri;
        } else {
            $url = path($uri, "index_$i.html");
        }
        // 异常
        try {
            $indexDoc = new Document($url, true);
        } catch(Exception $e) {
            simpleLog($logFile, $url, $e->getMessage());
            break;
        }
        // 解析页面
        $divList = $indexDoc->find('#zuo .bbox');
        foreach($divList as $dk => $div) {
            if ($ck == $lastCk && $i == $lastI && $dk <= $lastDk) continue;
            // 解析dom
            $dom = $div->find('.bintro')[0];
            try {
                $bookName = $dom->find('h3 a')[0]->text();
                $bookUri = $dom->find('h3 a')[0]->attr('href');
                $bookUrl = path($baseUri, $bookUri);
                $author = $dom->find('.ex p')[1]->find('a')[0]->text();
            } catch (Exception $e) {
                simpleLog($logFile, $e->getMessage());
                break 2;
            }
            // 存储
            echo $newLine = "$classify $ck $i $dk $bookName $author $bookUrl" . PHP_EOL;
            file_put_contents($resFile, $newLine, FILE_APPEND);
        }
        // 避免采集频率太快
        sleep(2);
    }
}