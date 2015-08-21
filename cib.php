<?php
/**
 * 兴业银行
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

$content = curlget('http://www.cib.com.cn/cn/Financing_Release/sale/mb22.html');

$p = '/<div class="add">[\s\S]*<p id="toplink">/U';
$p1 = '/<table[\s\S]*<\/table>/U';
$pt = '/<div class="newsTitle">[\s\S]*“(.*)”[\s\S]*<\/div>/U';

preg_match($p, $content, $m);
$links = get_links($m[0]);

$urls = array_filter($links[2], function ($item)
{
    return strrchr($item, '.') == '.html' ? true : false;
});
$urls = array_unique($urls);

foreach ($urls as $url)
{
    $c = curlget($url);
    $name = '';
    if (preg_match($pt, $c, $m))
    {
        $name = $m[1];
    }


    if (preg_match($p1, $c, $m))
    {
        $a = get_td_array(str_clean($m[0]));
        $a = array_filter($a, function ($pra)
        {
            return preg_match('/\d+/', $pra[0]);
        });

        foreach ($a as $item)
        {
            if (!(strpos($item[5], '全国') > -1 || strpos($item[5], '山东') > -1 || strpos($item[5], '济南') > -1))
            {
                continue;
            }
            $product['ITEM5'] = get_date($item[3]);
            $product['ITEM6'] = get_date($item[4]);

            if (!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6']))
            {
                continue;
            }
            $product['PRODUCT_SN'] = $item[1];
            $product['PRODUCT_NAME'] = strpos($item[0], $name) ? $item[0] : $name . $item[0];
            $product['ORG_ID'] = 'M00000034';
            $product['ORG_NAME'] = '福建兴业银行历山路支行';
            $product['ORG_TYPE'] = 'YHXY';
            $product['PRODUCT_STATUS'] = '-1';

            $fengxian = '';
            if ($name == '天天万利宝')
            {
                if (strpos($item[0], 'M') > -1)
                {
                    $fengxian = '低风险';
                    $product['PRODUCT_TYPE'] = '030301';
                }
                else
                {
                    $fengxian = '中低风险';
                    $product['PRODUCT_TYPE'] = '040402';
                }
            }
            else
            {
                $fengxian = '中低风险';
                $product['PRODUCT_TYPE'] = '040402';
            }

            $product['CONTENT'] = $m[0];
            $product['ATTR_TYPE'] = '01';

            if ($name == '天天万利宝')
            {
                $product['ITEM1'] = get_rate($item[12]);
                preg_match('/(.*)万/U', $item[11], $mat);
                $product['ITEM2'] = $mat[1] * 10000;
                $product['ITEM3'] = $item[9];

                if (strpos($item[0], '天天万利宝') > 0)
                {
                    array_splice($item, 0, 3, [$item[0], $item[1], '']);
                    $product['ITEM1'] = get_rate($item[10]);
                    preg_match('/(.*)万/U', $item[9], $mat);
                    $product['ITEM2'] = $mat[1] * 10000;
                    $product['ITEM3'] = $item[7];
                }
            }
            elseif ($name == '天天万汇通')
            {
                $product['ITEM1'] = get_rate($item[13]);
                preg_match('/(.*)美元/U', $item[12], $mat);
                $product['ITEM2'] = str_replace(',', '', $mat[1]);
                $product['ITEM3'] = $item[9];
            }


            $product['ITEM4'] = $fengxian;


            $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
            $product['BUY_URL'] = $url;


            $db->insert_product($product);

            sleep(1);
        }

    }
    else
    {
        echo $name;
    }

}

