<?php
/**
 * 光大银行
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
//中国银行
$snoopy = new Snoopy();
$snoopy->submit('http://www.cebbank.com/eportal/ui?pageId=478550&currentPage=1&moduleId=12218',
    ['filter_combinedQuery_SFZS' => '1', 'filter_EQ_SFWSYHXS' => '1', 'filter_EQ_TZBZMC' => '人民币']);
$content = $snoopy->getResults();

$p = '/<font color="red">[\s\S]*<span/U';
preg_match_all($p, $content, $matched);
$str = str_clean($matched[0][0]);

$page = intval(str_replace('<span', '', substr($str, strrpos($str, '/') + 1)));

$p = '/<table class="zslccp"[\s\S]*<\/table>/U';
preg_match_all($p, $content, $matched);
$table = $matched[0][0];

$p = '/href="(\/site\/gryw\/yglc\/lccpsj.*index.html)"/U';
preg_match_all($p, $content, $matched);

$urls = [];
$urls = array_merge($urls, $matched[1]);
for ($i = 2; $i <= $page; $i++)
{
    $snoopy->submit('http://www.cebbank.com/site/gryw/yglc/lcss/12218-' . $i . '.html',
        ['filter_combinedQuery_SFZS' => '1', 'filter_EQ_SFWSYHXS' => '1', 'filter_EQ_TZBZMC' => '人民币']);
    $content = $snoopy->getResults();

    $p = '/<table class="zslccp"[\s\S]*<\/table>/U';
    preg_match_all($p, $content, $matched);
    $table = $matched[0][0];

    $p = '/href="(\/site\/gryw\/yglc\/lccpsj.*index.html)"/U';
    preg_match_all($p, $content, $matched);

    $urls = array_merge($urls, $matched[1]);
}

foreach ($urls as & $item)
{
    $item = 'http://www.cebbank.com' . $item;
}

$p = '/<table class="table-infor"[\s\S]*<\/table>/U';

for ($i = 0, $j = count($urls); $i < $j; $i++)
{
    $snoopy->fetch($urls[$i]);
    $content = $snoopy->getResults();
    preg_match_all($p, $content, $matched);
    $content = str_clean(($matched[0][0]));
    $table = get_td_array($content);

    $product['PRODUCT_SN'] = $table[1][1];
    $product['PRODUCT_NAME'] = $table[0][0];
    $product['ORG_ID'] = 'M00000024';
    $product['ORG_NAME'] = '中国光大银行历山路支行';
    $product['ORG_TYPE'] = 'YHGD';
    $product['PRODUCT_STATUS'] = '-1';

    $product['CONTENT'] = $content;

    $product['ATTR_TYPE'] = '01';
    $product['ITEM1'] = get_rate($table[9][1]);
    $product['ITEM2'] = intval(str_replace(',', '', $table[6][1]));
    $product['ITEM3'] = get_days($table[5][1]);
    switch ($table[6][3])
    {
        case '低':
            $product['ITEM4'] = '低风险';
            if (strpos($product['PRODUCT_NAME'], '安存宝') > -1 || strpos($product['PRODUCT_NAME'], '多利宝') > -1)
            {
                $product['PRODUCT_TYPE'] = '030301';
            }
            else
            {
                $product['PRODUCT_TYPE'] = '040401';
            }
            break;
        case '较低':
            $product['ITEM4'] = '中低风险';
            if (strpos($product['PRODUCT_NAME'], '安存宝') > -1 || strpos($product['PRODUCT_NAME'], '多利宝') > -1)
            {
                $product['PRODUCT_TYPE'] = '030302';
            }
            else
            {
                $product['PRODUCT_TYPE'] = '040402';
            }
            break;
    }


    $product['ITEM5'] = $table[2][1];
    $product['ITEM6'] = $table[3][1];

    if ($product['ITEM5'] != '--')
    {
        if (!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6']))
        {
            continue;
        }
    }

    $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
    $product['BUY_URL'] = $urls[$i];

    $db->insert_product($product);

    sleep(1);
}
