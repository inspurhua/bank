<?php
/**
 * 北京银行
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
    'http://www.bankofbeijing.com.cn/licai/bf-licai1.shtml',
    'http://www.bankofbeijing.com.cn/licai/bf-licai1_2.shtml',
    'http://www.bankofbeijing.com.cn/licai/bf-licai1_3.shtml',
    'http://www.bankofbeijing.com.cn/licai/bf-licai1mj.shtml',
    'http://www.bankofbeijing.com.cn/licai/bf-licai1mj_2.shtml',
    'http://www.bankofbeijing.com.cn/licai/bf-licai1mj_3.shtml'
];
$list = [];
$p = '/<ul class="f_000_12">[\s\S]*<\/ul>/U';
$p1 = '/<a href="(.*)"/U';
$p2 = '/<table border="1"[\s\S]*<\/table>/U';
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
                $item = 'http://www.bankofbeijing.com.cn' . $item;
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
    $product['PRODUCT_SN'] = str_replace('产品代码：','',$item[1][0]);
    $product['PRODUCT_NAME'] =str_replace(['产品名称：','(简称&ldquo;本产品&rdquo;)'],'', $item[0][0]);
    $product['ORG_ID'] = 'M00000041';
    $product['ORG_NAME'] = '北京银行大明湖支行';
    $product['ORG_TYPE'] = 'YHBJ';
    $product['PRODUCT_STATUS'] = '-1';

    $fengxian = '';
    $ratetype = str_replace('产品类型：','',$item[1][1]);


    switch (str_replace(['产品风险等级：','★','(',')'],'',$item[5][0]))
    {
        case '谨慎型':
            $fengxian = '低风险';
            break;
        case '稳健型':
            $fengxian = '中低风险';
            break;
        case '平衡型':
            $fengxian = '中风险';
            break;
    }

    if ($ratetype == '保本保证收益型')
    {
        $product['PRODUCT_TYPE'] = ($fengxian == '低风险') ? '030301' : '030302';
    }
    elseif ($ratetype == '非保本浮动收益型')
    {
        $product['PRODUCT_TYPE'] = ($fengxian == '低风险') ? '040401' : '040402';
    }

    $product['ATTR_TYPE'] = '01';

    $product['ITEM1'] =  get_rate($item[10][0]);

    preg_match('/最低(.*)万元起购/U',$item[6][0],$je);
    $product['ITEM2'] = $je[1]*10000 ;

    preg_match('/财期限：(.*)天/U',$item[3][1],$je);
    $product['ITEM3'] =  $je[1];

    $product['ITEM4'] =  $fengxian;

    preg_match_all('/募集期：(.*)年(.*)月(.*)日至(.*)年(.*)月(.*)日/U',$item[3][0],$markdate);
    $product['ITEM5'] =  $markdate[1][0].'-'. datepad($markdate[2][0]).'-'.datepad($markdate[3][0]);
    $product['ITEM6'] =  $markdate[4][0].'-'. datepad($markdate[5][0]).'-'.datepad($markdate[6][0]);

    if (!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6']))
    {
        continue;
    }

    $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
    $product['BUY_URL'] = $url;

    $db->insert_product($product);

    sleep(1);

}
