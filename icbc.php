<?php
/**
 *工商银行
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
function rate($a)
{
    $t = explode('-', str_replace(array('%', '％'), '', $a));
    return (count($t) == 1) ? rtrim($t[0], '0') : rtrim($t[0], '0') . '-' . rtrim($t[1], '0');
}

$snoopy = new Snoopy();

$url = ['http://www.icbc.com.cn/ICBCDynamicSite2/money/services/MoenyListService.ashx?ctl1=2&ctl2=3&keyword=',
    'http://www.icbc.com.cn/ICBCDynamicSite2/money/services/MoenyListService.ashx?ctl1=2&ctl2=4&keyword=',
    'http://www.icbc.com.cn/ICBCDynamicSite2/money/services/MoenyListService.ashx?ctl1=3&ctl2=5&keyword=',
    'http://www.icbc.com.cn/ICBCDynamicSite2/money/services/MoenyListService.ashx?ctl1=3&ctl2=8&keyword=',
    'http://www.icbc.com.cn/ICBCDynamicSite2/money/services/MoenyListService.ashx?ctl1=7&ctl2=10&keyword=',
    'http://www.icbc.com.cn/ICBCDynamicSite2/money/services/MoenyListService.ashx?ctl1=1&ctl2=1&keyword=',
    'http://www.icbc.com.cn/ICBCDynamicSite2/money/services/MoenyListService.ashx?ctl1=1&ctl2=2&keyword='
];

$detailp = '/<table border=1 cellpadding=0 cellspacing=0><tr><td>产品名称[\s\S]*<\/table>/U';

for ($i = 0, $j = count($url); $i < $j; $i++)
{
    $snoopy->fetch($url[$i]);
    $content = $snoopy->getResults();
    $list = json_decode($content, true);

    for ($k = 0, $m = count($list); $k < $m; $k++)
    {
        $product['PRODUCT_SN'] = $list[$k]['prodID'];
        $product['PRODUCT_NAME'] = $list[$k]['productName'];
        $product['ORG_ID'] = 'M00000026';
        $product['ORG_NAME'] = '中国工商银行山东分行营业部';
        $product['ORG_TYPE'] = 'YHGS';
        $product['PRODUCT_STATUS'] = '-1';
        $product['ATTR_TYPE'] = '01';
        $product['ITEM1'] = rate($list[$k]['intendYield']);
        $product['ITEM2'] = intval($list[$k]['buyPaamt']);
        $product['ITEM3'] = str_replace(['最短持有', '天', '最短投资'], '', $list[$k]['productTerm']);
        $product['ITEM5'] = substr($list[$k]['offerPeriod'], 0, 4) . '-' . substr($list[$k]['offerPeriod'], 4, 2) . '-' . substr($list[$k]['offerPeriod'], 6, 2);
        $product['ITEM6'] = substr($list[$k]['offerPeriod'], 9, 4) . '-' . substr($list[$k]['offerPeriod'], 13, 2) . '-' . substr($list[$k]['offerPeriod'], 15, 2);

         if(!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6'] ))
         {
             continue;
         }

        $detailpage = "http://www.icbc.com.cn/ICBCDynamicSite2/money/production_explain.aspx?addStr={$product['PRODUCT_SN'] }.html&productId={$product['PRODUCT_SN'] }&buyflag=1";
        $snoopy->referer = 'http://www.icbc.com.cn/ICBC/%E7%BD%91%E4%B8%8A%E7%90%86%E8%B4%A2/';
        $snoopy->fetch($detailpage);
        $detailpagecontent = $snoopy->getResults();

        preg_match('/<table[\s\S]*/U', $detailpagecontent, $temp, PREG_OFFSET_CAPTURE, 0);
        preg_match('/<table[\s\S]*<\/table>/U', $detailpagecontent, $matched, PREG_OFFSET_CAPTURE, $temp[0][1] + 10);


        if (strpos($matched[0][0], 'PR') < 1)
        {
            preg_match('/<table[\s\S]*<\/table>/U', $detailpagecontent, $old, PREG_OFFSET_CAPTURE, $matched[0][1] + 10);
            $detail = $old[0][0];
        }
        else
        {
            $detail = $matched[0][0];
        }

        $product['CONTENT'] = $detail;


        $a = get_td_array(($detail));


        $fengxian = '';
        $feng = '';

        $shiyi = '';
        for ($n = 2; $n < 5; $n++)
        {
            $a[$n][1] = str_clean($a[$n][1]);
            $fx = strpos($a[$n][1], '收益');
            if ($fx > -1)
            {
                $shiyi = $a[$n][1];
                break;
            }
            else
            {
                continue;
            }
        }

        for ($n = 2; $n < 5; $n++)
        {
            $fx = substr(str_clean($a[$n][1]), 0, 3);
            if (substr($fx, 0, 2) == 'PR')
            {
                $feng = $fx;
                break;
            }
            else
            {
                continue;
            }
        }
        $product['PRODUCT_TYPE'] = '';
        switch ($feng)
        {
            case 'PR1':
                $fengxian = '低风险';
                if ($shiyi == '非保本浮动收益型')
                {
                    $product['PRODUCT_TYPE'] = '040401';
                }
                else
                {
                    $product['PRODUCT_TYPE'] = '030301';
                }
                break;
            case 'PR2':
                $fengxian = '中低风险';
                if ($shiyi == '非保本浮动收益型')
                {
                    $product['PRODUCT_TYPE'] = '040402';
                }
                else
                {
                    $product['PRODUCT_TYPE'] = '030302';
                }
                break;
            case 'PR3':
                $fengxian = '中风险';
                if ($shiyi == '非保本浮动收益型')
                {
                    $product['PRODUCT_TYPE'] = '040403';
                }
                break;
            case 'PR4':
                $fengxian = '中高风险';
                if ($shiyi == '非保本浮动收益型')
                {
                    $product['PRODUCT_TYPE'] = '040404';
                }
                break;
            case 'PR5':
                $fengxian = '高风险';
                if ($shiyi == '非保本浮动收益型')
                {
                    $product['PRODUCT_TYPE'] = '040405';
                }
                break;
            default:
        }
        $product['ITEM4'] = $fengxian;
        $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
        $product['BUY_URL'] = $detailpage;

        $db->insert_product($product);
        sleep(1);
    }
}
