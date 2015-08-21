<?php
/**
 * 莱商银行
 */
set_time_limit(0);
include 'db.class.php';
include 'Snoopy.class.php';

$db = db::getInstance([
    'host' => 'localhost',
    'user' => 'root',
    'password' => '123456',
    'database' => 'fmarket',
    'port' => 3306,
    'charset' => 'utf8'
]);

$snoopy = new Snoopy();

$urls = [
    'http://www.lsbankchina.com/publish/lsbankchina/112/113/20646/index.html',
    'http://www.lsbankchina.com/publish/lsbankchina/112/113/20646/index_2.html',
    'http://www.lsbankchina.com/publish/lsbankchina/112/113/20646/index_3.html'
];
$list = [];
$p = '/<div class="hyxmalb">[\s\S]*<div class="hyxma03">/U';
$p1 = '/<a href="(.*)">/U';
$p2 = '/<table style="[\s\S]*<\/table>/U';
foreach ($urls as $url)
{
    $snoopy->fetch($url);
    $content = $snoopy->getResults();
    if (preg_match_all($p, $content, $matched))
    {
        if (preg_match_all($p1, $matched[0][0], $m))
        {
            foreach ($m[1] as $item)
            {
                $item = 'http://www.lsbankchina.com' . $item;
                array_push($list, $item);
            }
        }
    }
}
foreach ($list as $url)
{
    $snoopy->fetch($url);
    $content = $snoopy->getResults();
    preg_match_all($p2, $content, $matched);
    $content = $matched[0][0];
    $product['CONTENT'] = $content;

    $item = get_td_array(str_clean($content));

    $product['PRODUCT_SN'] = 'LS'.$item[1][3];

    $product['PRODUCT_NAME'] = $item[1][1];
    $product['ORG_ID'] = 'M00000169';
    $product['ORG_NAME'] = '莱商银行历下支行';
    $product['ORG_TYPE'] = 'YHLS';
    $product['PRODUCT_STATUS'] = '-1';
    $fengxian = '';
    $ratetype = $item[2][1];
    echo $ratetype;
    echo '<br/>';
    switch ($item[1][5])
    {
        case '中低级':
            $fengxian = '中低风险';
            break;
        case '低级':
            $fengxian = '低风险';
            break;
        case '中级':
            $fengxian = '中风险';
            break;
        case '中高级':
            $fengxian = '中高风险';
            break;
        case '高级':
            $fengxian = '高风险';
            break;
    }
    if ($ratetype == '保证收益型' || $ratetype == '保本浮动收益型')
    {
        $product['PRODUCT_TYPE'] = ($fengxian == '低风险') ? '030301' : '030302';
    }
    elseif ($ratetype == '非保本浮动收益型')
    {
        $product['PRODUCT_TYPE'] = ($fengxian == '低风险') ? '040401' : '040402';
    }

    $product['ATTR_TYPE'] = '01';
    preg_match('/(([0-9]+\.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*\.[0-9]+)|([0-9]*[1-9][0-9]*))%/U', $item[9][1], $je);
    $product['ITEM1'] = array_pop($je);
    preg_match('/起点金额(.*)万元/U', $item[7][1], $je);
    $product['ITEM2'] = $je[1] * 10000;

    $product['ITEM3'] = str_replace('天', '', $item[3][3]);
    $product['ITEM4'] = $fengxian;
    preg_match_all('/(.*)年(.*)月(.*)日(.*)&mdash;&mdash;(.*)年(.*)月(.*)日/U', $item[3][1], $markdate);

    $product['ITEM5'] = $markdate[1][0] . '-' . datepad($markdate[2][0]) . '-' . datepad($markdate[3][0]);
    $product['ITEM6'] = $markdate[5][0] . '-' . datepad($markdate[6][0]) . '-' . datepad($markdate[7][0]);

    if (!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6']))
    {
        continue;
    }

    $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
    $product['BUY_URL'] = $url;
    $db->insert_product($product);
    sleep(1);
}

