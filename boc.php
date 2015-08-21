<?php
/**
 * 中国银行
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
$snoopy->fetch('http://www.boc.cn/fimarkets/cs8/201109/t20110922_1532694.html');
$content = $snoopy->getResults();

$p = '/<table class="sort-table"[\s\S]*<\/table>/U';
$ok = preg_match_all($p, $content, $matched);
$a = get_td_array(str_clean($matched[0][0]));

function baoben($s)
{
    return strpos($s, '搏·弈') > -1;
}

foreach ($a as $item)
{
    if ($item)
    {
        $product['PRODUCT_SN'] = $item[0];
        $product['PRODUCT_NAME'] = $item[1];
        $product['ORG_ID'] = 'M00000010';
        $product['ORG_NAME'] = '中国银行股份有限公司府东支行';
        $product['ORG_TYPE'] = 'YHZG';
        $product['PRODUCT_STATUS'] = '-1';

        $product['CONTENT'] = '';
        $product['ATTR_TYPE'] = '01';
        $product['ITEM1'] = get_rate($item[3]);
        $product['ITEM2'] = str_replace('万', '0000', $item[4]);
        $product['ITEM3'] = $item[2];
        $product['ITEM4'] = $item[10] . '风险';
        $product['ITEM5'] = get_date($item[11]);
        $product['ITEM6'] = get_date($item[12]);

        switch ($item[10])
        {
            case '低':
                if (baoben($item[1]))
                {
                    $product['PRODUCT_TYPE'] = '030301';
                }
                else
                {
                    $product['PRODUCT_TYPE'] = '040401';
                }

                break;
            case '中低':
                if (baoben($item[1]))
                {
                    $product['PRODUCT_TYPE'] = '030302';
                }
                else
                {
                    $product['PRODUCT_TYPE'] = '040402';
                }
                break;
            case '中':
                $product['PRODUCT_TYPE'] = '040403';
                break;
            case '中高':
                $product['PRODUCT_TYPE'] = '040404';
                break;
            case '高':
                $product['PRODUCT_TYPE'] = '040405';
                break;

        }

       if(!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6'] ))
       {
           continue;
       }
        $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
        $product['BUY_URL'] = 'http://www.boc.cn/fimarkets/cs8/201109/t20110922_1532694.html';
        $db->insert_product($product);
    }

}


