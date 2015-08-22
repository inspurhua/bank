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

    $product['CONTENT'] = "<table width='500px' class='tableboder' cellpadding='3' cellspacing='0' style='border: 1px solid #ccc;' rules='rows'><tbody><tr><td width='150px'><span class='fontbold12'>产品代码</span></td><td>{$item['prd_code']}</td></tr><tr><td><span>产品名称</span></td><td>{$item['prd_name']}</td></tr><tr><td><span>产品类别</span></td><td>{$item['prd_attr_name']}</td></tr><tr><td><span>产品类型</span></td><td>{$item['prd_type_name']}</td></tr><tr><td><span>销售对象</span></td><td>{$item['selldir']}</td></tr><tr><td><span>产品状态</span></td><td>{$item['status_name']}</td></tr><tr><td><span>产品币种</span></td><td>{$item['curr_type_name']}</td></tr><tr><td><span>钞汇标志</span></td><td>{$item['crflagname']}</td></tr><tr id='navId' style='display:none;'><td><span>最新净值</span></td><td>{$item['nav']}</td></tr><tr><td><span>募集开始日</span></td><td>{$item['ipo_start_date']}</td></tr><tr><td><span>募集截止日</span></td><td>{$item['ipo_end_date']}</td></tr><tr><td><span>产品成立日</span></td><td>{$item['start_date']}</td></tr><tr id='opdateId' style='display:none;'><td><span>下一开放日</span></td><td>--</td></tr><tr id='eddateId' style='display:none;'><td><span>下下开放日</span></td><td>--</td></tr><tr><td><span>产品到期日</span></td><td>{$item['curr_type_name']}</td></tr><tr><td><span>投资周期</span></td><td>{$item['liv_time_unit_name']}</td></tr><tr id='income_rateId' style=''><td><span>本期预期收益率</span></td><td>{$item['income_rate']}</td></tr><tr id='next_income_rateId' style='display:none;'><td><span>下期预期收益率</span></td><td>未定</td></tr><tr id='interest_type_nameId' style=''><td><span>计息基准</span></td><td>ACT/365             </td></tr><tr id='cash_dayId' style='display:none;'><td><span>收益兑付日</span></td><td>每月{$item['cash_day']}日</td></tr><tr id='force_modeId' style='display:none;'><td><span>赎回到账天数</span></td><td>---个证券市场工作日</td></tr><tr><td><span>产品风险等级</span></td><td>{$item['risk_level_name']}</td></tr><tr><td><span>开市时间</span></td><td>{$item['open_time']}</td></tr><tr><td><span>闭市时间</span></td><td>{$item['close_time']}</td></tr><tr id='red_close_time' style='display:none;'><td><span>实时赎回闭市时间</span></td><td>{$item['red_close_time']}</td></tr><tr><td><span>首次最低投资</span></td><td>{$item['pfirst_amt']}</td></tr><tr><td><span>最小认购单位</span></td><td>{$item['psub_unit']}</td></tr><tr><td><span>最小赎回单位</span></td><td>{$item['pred_unit']}</td></tr><tr><td><span>单笔购买上限</span></td><td>{$item['pmax_red']}</td></tr><tr><td><span>单笔赎回上限</span></td><td>{$item['pmax_amt']}</td></tr><tr><td><span>当日购买上限</span></td><td>不限制</td></tr><tr><td><span>最低持仓份额</span></td><td>{$item['pmin_hold']}</td></tr></tbody></table>";
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


