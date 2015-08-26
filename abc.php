<?php
/**
 * 农业银行
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
$snoopy->referer = 'http://ewealth.abchina.com/fs/filter/?saleSate=%E5%BD%93%E5%89%8D%E5%9C%A8%E5%94%AE&prodLimit=&prodYildType=';
$snoopy->fetch('http://ewealth.abchina.com/app/data/api/DataService/BoeProductV2?i=1&s=1500&o=0&w=%25E5%258F%25AF%25E5%2594%25AE%257C%257C%257C%25E4%25BA%25BA%25E6%25B0%2591%25E5%25B8%2581%257C%257C%257C%257C1%257C%257C0%257C%257C0');
$content = $snoopy->getResults();
$all = json_decode($content,true);
$urls = array();
$product_sn=array();
foreach($all['Data']['Table'] as $item)
{
    array_push($product_sn,$item['ProductNo']);
    array_push($urls,'http://ewealth.abchina.com/fs/'.$item['ProductNo'].'.htm');
}

$p = '/<table class="list_cp"[\s\S]*<\/table>/U';
for ($i = 0, $j = count($urls); $i < $j; $i++)
{

    $snoopy->fetch($urls[$i]);
    $content = $snoopy->getResults();
    preg_match_all($p, $content, $matched);
    $detail = $matched[0][0];
    $table = get_td_array(str_clean($detail));
    unset($mat3);
    $patt = '/<span titlle=([\s\S]*)?>/U';
    preg_match_all($patt,$detail,$mat3);
    if(!(strstr($table[15][0],"全国"))&&!(strstr($mat3[1][0],"山东"))) continue;

    $product['PRODUCT_SN'] = $product_sn[$i];
    $product['PRODUCT_NAME'] = str_replace('产品名称','',$table[1][0]);
    $product['ORG_ID'] = 'M00000030';
    $product['ORG_NAME'] = '中国农业银行历山路支行';
    $product['ORG_TYPE'] = 'YHNY';
    $product['PRODUCT_STATUS'] = '-1';
    $ratetype = str_replace('收益类型','',$table[13][1]);

    if($ratetype == '保本浮动收益' || $ratetype == '保本收益')
    {
        $product['PRODUCT_TYPE'] = ($table[13][0] == '风险等级中低' || $table[6][3] == '风险等级低') ? '030301' : '030302';
    }elseif($ratetype == '非保本浮动收益'){
        $product['PRODUCT_TYPE'] = ($table[13][0] == '风险等级中低' || $table[6][3] == '风险等级低') ? '040401' : '040402';
    }

    $product['CONTENT'] = rel2abs($detail,'http://ewealth.abchina.com/fs/');

    $product['ATTR_TYPE'] = '01';
    $product['ITEM1'] = str_replace(['预期年化收益率','%','％'],'',$table[9][1]);
    preg_match('/起购金额varm=(.*);/U',$table[15][1],$mat);
    $product['ITEM2'] =$mat[1];
    $product['ITEM3'] = str_replace(['投资期限','天'],['',''],$table[9][0]);
    $product['ITEM4'] = str_replace('风险等级','',$table[13][0]).'风险';
    $product['ITEM5'] = str_replace(['认购起始日期','/'],['','-'],$table[5][0]);
    $product['ITEM6'] = str_replace(['认购结束日期','/'],['','-'],$table[5][1]);

    if(!($product['ITEM5'] <= date('Y-m-d') && date('Y-m-d') <= $product['ITEM6'] ))
    {
        continue;
    }
    $product['BUY_WAY'] = '山东省济南市历下区解放路159号：山东金融超市 电话：0531-66571966';
    $product['BUY_URL'] = $urls[$i];

    $db->insert_product($product);

    sleep(1);
}
