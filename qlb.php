<?php
/**
 * 齐鲁银行
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
    'http://www.qlbchina.com/Channel/1445138?_tp_infos=1',
    'http://www.qlbchina.com/Channel/1445138?_tp_infos=2',
    'http://www.qlbchina.com/Channel/1445138?_tp_infos=3'
];
$list = [];
$p = '/<div class="module7_body_bg"[\s\S]*<div class="list_page11"/U';
$p1 = '/<a href="(.*)" title="(.*)"/U';
$p2 = '/<table border="1"[\s\S]*<\/table>/U';
$p3 = '/<TABLE[\s\S]*<\/TABLE>/U';
$p4 = '/src="(.*)" width="100%"><\/iframe>/U';
foreach ($urls as $url)
{
    $snoopy->fetch($url);
    $content = iconv("GB2312", "UTF-8//IGNORE", $snoopy->results);

    if (preg_match_all($p, $content, $matched))
    {
        if (preg_match_all($p1, $matched[0][0], $m))
        {
            for ($i = 0, $len = count($m[2]); $i < $len; $i++)
            {
                if (strpos($m[2][$i], '理财产品说明书') > -1)
                {
                    array_push($list, 'http://www.qlbchina.com' . $m[1][$i]);
                }
            }
        }
    }
}

foreach ($list as & $url)
{
    $snoopy->fetch($url);
    $content = iconv("GB2312", "UTF-8//IGNORE", $snoopy->getResults());
    preg_match_all($p4, $content, $matched);
    $url =  'http://www.qlbchina.com'.$matched[1][0];
}


foreach ($list as $url)
{
    $snoopy->fetch($url);
    $content =  iconv("GB2312", "UTF-8//IGNORE", $snoopy->getResults());

    preg_match_all($p3, $content, $matched);
    $content = $matched[0][1];
    $product['CONTENT'] = $content;

    preg_match_all('/style="(.*)"/U',$content,$style);
    $content = str_replace($style[0],'',$content);
    $content = str_replace(['<TABLE>','<TR>','<TD>','</TR>','</TD>','&nbsp;'],['<table>','<tr>','<td>','</tr>','</td>',''],$content);
    $item =  get_td_array(str_clean($content));

    $product['PRODUCT_SN'] = str_replace('编号：', '', $item[1][0]);
    $product['PRODUCT_NAME'] = $item[0][1];
    $product['ORG_ID'] = 'M00000038';
    $product['ORG_NAME'] = '齐鲁银行解放路支行';
    $product['ORG_TYPE'] = 'YHQL';
    $product['PRODUCT_STATUS'] = '-1';

    $fengxian = '';
    switch(substr_count($item[8][1],'★')){
        case 1:
            $fengxian = '低风险';
            break;
        case 2:
            $fengxian = '中低风险';
            break;
        case 3:
            $fengxian = '中风险';
            break;
        case 4:
            $fengxian = '中高风险';
            break;
    }

    if($item[6][1]=='保本浮动收益类'){
        switch($fengxian){
            case '低风险':
                $product['PRODUCT_TYPE'] ='030301';
                break;
            case '中低风险':
                $product['PRODUCT_TYPE'] ='030302';
                break;
        }
    }else{
        switch($fengxian){
            case '低风险':
                $product['PRODUCT_TYPE'] ='040401';
                break;
            case '中低风险':
                $product['PRODUCT_TYPE'] ='040402';
                break;
            case '中风险':
                $product['PRODUCT_TYPE'] ='040403';
                break;
            case '中高风险':
                $product['PRODUCT_TYPE'] ='040404';
                break;
        }
    }

    $product['ATTR_TYPE'] = '01';

    $product['ITEM1'] = get_rate($item[15][1]);

    preg_match('/认购起点(.*)万元/U', $item[17][1], $je);
    $product['ITEM2'] = $je[1] * 10000;

    $product['ITEM3'] = str_replace('天','', $item[4][1]);
    $product['ITEM4'] = $fengxian;

    preg_match_all('/(.*)年(.*)月(.*)日－(.*)年(.*)月(.*)日/U', $item[9][1], $markdate);
    $product['ITEM5'] = $markdate[1][0] . '-' . datepad($markdate[2][0]) . '-' . datepad($markdate[3][0]);
    $product['ITEM6'] = $markdate[4][0] . '-' . datepad($markdate[5][0]) . '-' . datepad($markdate[6][0]);

    if (!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6']))
    {
        continue;
    }

    $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
    $product['BUY_URL'] = $url;
    $db->insert_product($product);

    sleep(1);

}
