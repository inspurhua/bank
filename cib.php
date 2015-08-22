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
        $order = array();

        for ($i = 0, $count = count($a[0]); $i < $count; $i++)
        {
            if (strpos($a[0][$i], '期次款数') > -1)
            {
                $order['PRODUCT_NAME'] = $i;
            }
            elseif (strpos($a[0][$i], '销售编号') > -1 ||strpos($a[0][$i], '销售编码') > -1)
            {
                $order['PRODUCT_SN'] = $i;
            }
            elseif (strpos($a[0][$i], '申购起始日') > -1 || strpos($a[0][$i], '认购起始日') > -1)
            {
                $order['ITEM5'] = $i;
            }
            elseif (strpos($a[0][$i], '申购结束日') > -1 || strpos($a[0][$i], '认购结束日') > -1)
            {
                $order['ITEM6'] = $i;
            }
            elseif (strpos($a[0][$i], '期限') > -1)
            {
                $order['ITEM3'] = $i;
            }
            elseif (strpos($a[0][$i], '起购金额') > -1)
            {
                $order['ITEM2'] = $i;
            }
            elseif (strpos($a[0][$i], '收益率') > -1)
            {
                $order['ITEM1'] = $i;
            }
            elseif (strpos($a[0][$i], '产品类型') > -1)
            {
                $order['保本吗'] = $i;
            }
        }

        $a = array_filter($a, function ($pra)
        {
            return preg_match('/\d+/', $pra[0]);
        });

        foreach ($a as $item)
        {
            $product['ITEM5'] = get_date($item[$order['ITEM5']]);
            $product['ITEM6'] = get_date($item[$order['ITEM6']]);

            if (!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6']))
            {
                continue;
            }
            $product['PRODUCT_SN'] = $item[$order['PRODUCT_SN']];
            $product['PRODUCT_NAME'] = strpos($item[$order['PRODUCT_NAME']], $name) ? $item[$order['PRODUCT_NAME']] : $name . $item[$order['PRODUCT_NAME']];
            $product['ORG_ID'] = 'M00000034';
            $product['ORG_NAME'] = '福建兴业银行历山路支行';
            $product['ORG_TYPE'] = 'YHXY';
            $product['PRODUCT_STATUS'] = '-1';

            $fengxian = '';
            if ($name == '天天万利宝')
            {
                if (strpos($product['PRODUCT_NAME'], 'M') > -1)
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

            $product['ITEM1'] = get_rate($item[$order['ITEM1']]);
            if (preg_match('/(.*)万/U', $item[$order['ITEM2']], $mat))
            {
                $product['ITEM2'] = $mat[1] * 10000;
            }
            else
            {
                preg_match('/(.*)美元/U', $item[$order['ITEM2']], $mat);
                $product['ITEM2'] = str_replace(',', '', $mat[1]);
            }

            $product['ITEM3'] = $item[$order['ITEM3']];

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

