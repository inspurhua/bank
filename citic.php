<?php
/**
 * 中信银行
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
$content = curlpost('https://mall.bank.ecitic.com/fmall/pd/fin-index.htm',
    ['branch_id' => '701100', 'fintype' => '0'],
    'https://mall.bank.ecitic.com/fmall/pd/fin-index.htm'
);

$p = '/<table width="100%" border="0" cellspacing="0" cellpadding="0" id="charttab" class="fund_pleft_table">[\s\S]*<\/table>/U';
$urls = [];

if (preg_match_all($p, $content, $matched))
{

    $a = get_td_array(str_clean($matched[0][0]));
    for ($i = 1, $len = count($a); $i < $len; $i++)
    {
        $a[$i][1] && array_push($urls, 'https://mall.bank.ecitic.com/fmall/finproduct/' . $a[$i][1] . '.html?chcode=5406');
    }
}

$p2 = '/<table width="100%" border="0" cellspacing="0" cellpadding="0">[\s\S]*<\/table>/U';
$p3 = '/<table width="100%" border="0" cellspacing="1" cellpadding="0" class="conduct_table">[\s\S]*<\/table>/U';
function get_type($temp)
{
    $begin = strpos($temp, '非保本');
    if ($begin > -1)
    {
        return '非保本浮动收益类';
    }
    else
    {
        return '保本浮动收益类';
    }
}

foreach ($urls as $url)
{
    $content = curlget($url);
    if (preg_match_all($p2, $content, $matched))
    {
        $item = get_td_array(str_clean($matched[0][0]));
        $product['ITEM5'] = str_replace('募集起始日期：', '', $item[1][0]);
        $product['ITEM6'] = str_replace('募集截止日期：', '', $item[1][1]);
        if (!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6']))
        {
            continue;
        }
    }
    if (preg_match_all($p3, $content, $matched))
    {
        $content = $matched[0][0];
        $product['CONTENT'] = $content;
        $item = get_td_array(str_clean($content));

        if (substr_count($item[1][1], '美元') > 0)
        {
            continue;
        }
        if (!(strpos($item[14][3], '全国') > -1 || strpos($item[14][3], '山东') > -1 || strpos($item[14][3], '济南') > -1))
        {
            continue;
        }

        $product['PRODUCT_SN'] = $item[0][1];
        $product['PRODUCT_NAME'] = $item[0][3];
        $product['ORG_ID'] = 'M00000040';
        $product['ORG_NAME'] = '中信银行济南分行营业部';
        $product['ORG_TYPE'] = 'YHZX';
        $product['PRODUCT_STATUS'] = '-1';

        $fengxian = '';
        $r = '';
        $temp = curlget('https://mall.bank.ecitic.com/fmall/finproduct/' . $item[0][1] . '00.html');
        $temp = toUTF8($temp);
        $r = get_type($temp);

        switch ($item[1][3])
        {
            case '低风险':
                $fengxian = '低风险';
                if ($r == '非保本浮动收益类')
                {
                    $product['PRODUCT_TYPE'] = '040401';
                }
                else
                {
                    $product['PRODUCT_TYPE'] = '030301';
                }

                break;
            case '较低风险':
                $fengxian = '中低风险';
                if ($r == '非保本浮动收益类')
                {
                    $product['PRODUCT_TYPE'] = '040402';
                }
                else
                {
                    $product['PRODUCT_TYPE'] = '030302';
                }
                break;
            case '中等风险':
                $fengxian = '中风险';
                if ($r == '非保本浮动收益类')
                {
                    $product['PRODUCT_TYPE'] = '040403';
                }
                else
                {
                    $product['PRODUCT_TYPE'] = '030302';
                }
                break;
        }

        $product['ATTR_TYPE'] = '01';
        $product['ITEM1'] = get_rate($item[5][1]);
        $product['ITEM2'] = str_replace('万元', '', $item[3][3]) * 10000;
        $product['ITEM3'] = str_replace('天', '', $item[3][1]);
        $product['ITEM4'] = $fengxian;

        $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
        $product['BUY_URL'] = $url;
        $db->insert_product($product);

        sleep(1);
    }
    else
    {
        echo $content;
        die;
    }
}

