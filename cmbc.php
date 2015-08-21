<?php
/**
 * 民生银行
 */
set_time_limit(0);
include 'db.class.php';

$db = db::getInstance([
    'host' => 'localhost',
    'user' => 'root',
    'password' => '123456',
    'database' => 'fmarket',
    'port' => 3306,
    'charset' => 'utf8'
]);

$pcode = [];

$url = 'https://service.cmbc.com.cn/pai_ms/cft/queryTssPrdInScreenfoForJson.gsp?rd=0.';
$url .= strval(mt_rand(1000, 9999)) . strval(mt_rand(1000, 9999)) . strval(mt_rand(1000, 9999)) . strval(mt_rand(1000, 9999));
$url .= '&page=%u&rows=%u&jsonpcallback=jQuery&_=' . strval(time() * 1000 + mt_rand(100, 999));

$detailurl = 'http://www.cmbc.com.cn/pai_ms/cft/queryForObject.gsp?jsonpcallback=jQuery&rd=0.';
$detailurl .= strval(mt_rand(1000, 9999)) . strval(mt_rand(1000, 9999)) . strval(mt_rand(1000, 9999)) . strval(mt_rand(1000, 9999));
$detailurl .= '&params.prd_code=%s&params.type=TSS&_=1' . strval(time() * 1000 + mt_rand(100, 999));

$content = curlget(sprintf($url, 1, 10),
    'http://www.cmbc.com.cn/cs/Satellite?c=Page&cid=1356495590851&currentId=1356495507925&pagename=cmbc%2FPage%2FTP_PersonalProductSelLayOut&rendermode=preview');

$a = json2array($content, 'jQuery');
array_map(function ($item) use (& $pcode)
{
    $pcode[] = $item['prd_code'];
}, $a['list']);

for ($i = 2; $i <= $a['pageCount']; $i++)
{
    $content = curlget(sprintf($url, $i, 10), sprintf($url, $i - 1, 10));
    $a = json2array($content, 'jQuery');

    array_map(function ($item) use (& $pcode)
    {
        $pcode[] = $item['prd_code'];
    }, $a['list']);
}

foreach ($pcode as $it)
{
    $urlD = sprintf($detailurl, $it);
    $content = curlget($urlD);
    $item = json2array($content, 'jQuery');

    $product['PRODUCT_SN'] = $item['prd_code'];
    $product['PRODUCT_NAME'] = $item['prd_name'];
    $product['ORG_ID'] = 'M00000035';
    $product['ORG_NAME'] = '民生银行历山支行';
    $product['ORG_TYPE'] = 'YHMS';
    $product['PRODUCT_STATUS'] = '-1';

//    $fengxian = $item['risk_level_name'];//较低风险(二级)
    if ($item['prd_type_name'] == '净值型')
    {
        continue;
    }

    if (strpos($item['prd_name'], '安盈') > -1 || strpos($item['prd_name'], '安赢') > -1)
    {
        $product['PRODUCT_TYPE'] = '030301';
        $fengxian = '低风险';
    }
    elseif (strpos($item['prd_name'], '翠竹') > -1 ||
        strpos($item['prd_name'], '增利') > -1 ||
        strpos($item['prd_name'], '智赢') > -1 || strpos($item['prd_name'], '天溢金') > -1
    )
    {
        $product['PRODUCT_TYPE'] = '030302';
        $fengxian = '中低风险';
    }

    $product['CONTENT'] = '';
    $product['ATTR_TYPE'] = '01';

    $product['ITEM1'] = get_rate($item['income_rate']);
    $product['ITEM2'] = str_replace(['，', ',', '.00'], '', $item['pfirst_amt']);
    $product['ITEM3'] = get_days($item['liv_time_unit_name']);
    $product['ITEM4'] = $fengxian;
    $product['ITEM5'] = $item['ipo_start_date'];
    $product['ITEM6'] = $item['ipo_end_date'];
    if (!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6']))
    {
        continue;
    }

    $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
    $product['BUY_URL'] = 'http://www.cmbc.com.cn/cs/Satellite?c=Page&cid=1356495590851&currentId=1356495507925&pagename=cmbc%2FPage%2FTP_PersonalProductSelLayOut&rendermode=preview';

    $db->insert_product($product);
}


