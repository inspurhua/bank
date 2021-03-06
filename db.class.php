<?php

class db
{
    public static $_instance;
    public $dbLink;

    public function __construct($config = array())
    {
        if (empty($config['host']) || empty($config['user']) || empty($config['password']))
        {
            return false;
        }

        $this->dbLink = (isset($config['port']) && !empty($config['port'])) ? new mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']) : new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

        if (mysqli_connect_errno())
        {
            return false;
        }
        else
        {
            $this->dbLink->query('SET NAMES ' . $config['charset']);
            mysqli_autocommit($this->dbLink,true);
        }

        return true;
    }

    public function query($sql)
    {
        $result = $this->dbLink->query($sql);
        return $result;
    }

    public function error()
    {

        return $this->dbLink->error;
    }

    public function insertId()
    {
        return ($id = $this->dbLink->insert_id) >= 0 ? $id : $this->query("SELECT last_insert_id()")->fetch_row();
    }

    public function fetchAll($sql)
    {

        $result = $this->query($sql);

        if (!$result)
        {
            return false;
        }

        $rows = array();
        while ($row = $result->fetch_assoc())
        {
            $rows[] = $row;
        }

        $result->free();

        return $rows;
    }

    public function fetchRow($sql)
    {

        $result = $this->query($sql);

        if (!$result)
        {
            return false;
        }

        $row = $result->fetch_assoc();

        $result->free();

        return $row;
    }

    public function fetchColumn($sql)
    {
        //参数判断
        $result = $this->query($sql);

        if (!$result)
        {
            return false;
        }

        $row = $result->fetch_assoc();

        $result->free();

        if (isset($row[0]))
        {
            return $row[0];
        }
        if (count($row) == 1)
        {
            return current($row);
        }
        return false;
    }

    public function close()
    {
        if ($this->dbLink)
        {
            $this->dbLink->close();
        }

        return true;
    }

    public function __destruct()
    {
        $this->close();
    }

    public static function getInstance($params)
    {
        if (!self::$_instance)
        {
            self::$_instance = new self($params);
        }

        return self::$_instance;
    }

    public function insert_product($product)
    {
        $affected = 0;
        $exits = "SELECT COUNT(*) FROM `fs_products` WHERE `PRODUCT_SN`='{$product['PRODUCT_SN']}'
        AND `PRODUCT_NAME`='{$product['PRODUCT_NAME']}'
        AND `ITEM5`='{$product['ITEM5']}'
        AND `ITEM6`='{$product['ITEM6']}'";

        if ($this->fetchColumn($exits))
        {
            return $affected;
        }

        if ($stmt = $this->dbLink->prepare("insert into `fs_products` (`PRODUCT_SN`,`PRODUCT_NAME`,`ORG_ID`,`ORG_NAME`,`ORG_TYPE`,`PRODUCT_STATUS`,`PRODUCT_TYPE`, `CONTENT`,`ATTR_TYPE`,`ITEM1`,`ITEM2`,`ITEM3`,`ITEM4`,`ITEM5`,`ITEM6`,`BUY_WAY`,`BUY_URL`)values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"))
        {
            $stmt->bind_param("sssssssssssssssss", $product['PRODUCT_SN'], $product['PRODUCT_NAME'], $product['ORG_ID'], $product['ORG_NAME'], $product['ORG_TYPE'],
                $product['PRODUCT_STATUS'], $product['PRODUCT_TYPE'], $product['CONTENT'], $product['ATTR_TYPE'],
                $product['ITEM1'], $product['ITEM2'], $product['ITEM3'], $product['ITEM4'], $product['ITEM5'], $product['ITEM6'],
                $product['BUY_WAY'], $product['BUY_URL']);
            $bool = $stmt->execute();
            if (!$bool)
            {
                $a = $this->error();
            }
            $affected = $stmt->affected_rows;
            $stmt->close();
        }
        return $affected;
    }
}

function datepad($num)
{
    $num = strval($num);
    return (strlen($num) == 1) ? '0' . $num : $num;
}

function get_td_array($table)
{
    $td_array = [];
    $table = preg_replace("'<table[^>]*?>'si", "", $table);
    $table = preg_replace("'<tr[^>]*?>'si", "", $table);
    $table = preg_replace("'<td[^>]*?>'si", "", $table);
    $table = str_replace("</tr>", "{tr}", $table);
    $table = str_replace("</td>", "{td}", $table);
    //去掉 HTML 标记
    $table = preg_replace("'<[/!]*?[^<>]*?>'si", "", $table);
    //去掉空白字符
    $table = preg_replace("'([rn])[s]+'", "", $table);
    $table = str_replace(" ", "", $table);
    $table = str_replace(" ", "", $table);

    $table = explode('{tr}', $table);
    array_pop($table);
    foreach ($table as $key => $tr)
    {
        $td = explode('{td}', $tr);
        array_pop($td);
        $td_array[] = $td;
    }
    return $td_array;
}

function str_clean($content)
{
    return preg_replace("/\s/", "", $content);
}

function curlpost($url, $data, $refer = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_REFERER, $refer);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $return = curl_exec($ch);
    curl_close($ch);
    return $return;
}

function curlget($url, $refer = '')
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$refer && curl_setopt($ch, CURLOPT_REFERER, $refer);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch , CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.93 Safari/537.36' ) ;
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);

	while(true)
	{
		$return = curl_exec($ch);
		if($return)
		{
			break;
		}
		else
		{
			sleep(3);
		}
	}
	if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200')
	{
		list($header, $body) = explode("\r\n\r\n", $return, 2);
	}

	curl_close($ch);

	if(preg_match('/charset=(.*)/',$header,$m))
	{
		$m[1] = strtolower(str_replace(array("\n","\r","\r\n"),'',$m[1]));
		if ($m[1] !='utf-8'){
			$body = mb_convert_encoding($body, 'utf-8',$m[1]);
		}
	}else{
		$ok = preg_match('/charset="(.*)"/',$body,$m);
		if($ok)
		{
			echo $m[1];
			$m[1] = strtolower(str_replace(array("\n","\r","\r\n"),'',$m[1]));
			if ($m[1] !='utf-8'){
				$body = mb_convert_encoding($body, 'utf-8',$m[1]);
			}
		}
	}
	return $body;
}

 function move_uploaded_files($file, $target, $url = null)
    {
        $url = isset($url) ? $url : "http://139.129.223.136/vgicfile/fileupload.php";

        $ch   = curl_init();
        $data = ['file' => '@' . $file, 'target' => $target];
        if (class_exists('\CURLFile'))
        {
            $data['file'] = new \CURLFile(realpath($file));
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        }
        else
        {
            $data['file'] = '@' . realpath($file);
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if ($error = curl_error($ch))
        {
            return false;
        }
        curl_close($ch);
        return $result == "yes";
    }
function curlMultiRequest($urls, $options = array())
{
    $ch = array();
    $results = array();
    $mh = curl_multi_init();
    foreach ($urls as $key => $val)
    {
        $ch[$key] = curl_init();
        if ($options)
        {
            curl_setopt_array($ch[$key], $options);
        }
        curl_setopt($ch[$key], CURLOPT_URL, $val);
        curl_multi_add_handle($mh, $ch[$key]);
    }
    $running = null;
    do
    {
        curl_multi_exec($mh, $running);
    } while ($running > 0);
    foreach ($ch as $key => $val)
    {
        $results[$key] = curl_multi_getcontent($val);
        curl_multi_remove_handle($mh, $val);
    }
    curl_multi_close($mh);
    return $results;
}

function findNum($str = '')
{
    $str = trim($str);
    if (empty($str))
    {
        return '';
    }
    $temp = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
    $result = '';
    for ($i = 0; $i < strlen($str); $i++)
    {
        if (in_array($str[$i], $temp))
        {
            $result .= $str[$i];
        }
    }
    return $result;
}

function get_rate($str)
{
    $index = 0;
    $rate = '';
    while (1)
    {
        if (preg_match('/(([0-9]+\.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*\.[0-9]+)|([0-9]*[1-9][0-9]*))[%％]/U', $str, $je, PREG_OFFSET_CAPTURE, $index))
        {
            $first = array_pop($je);
            $rate = intval($first[0]);
            if ($rate > 1 && $rate < 20)
            {
                $rate = $first[0];
                break;
            }
            else
            {
                $index = $first[1] + 1;
                continue;
            }
        }
        else
        {
            $rate = '0';
            break;
        }
    }
    $rate = round($rate, 2);
    return strval($rate);
}

function get_links($content)
{
    $pattern = '/<a(.*?)href="(.*?)"(.*?)>(.*?)<\/a>/i';
    preg_match_all($pattern, $content, $m);
    return $m;
}

function get_date($content)
{
    if (preg_match('/(.*)年(.*)月(.*)日/U', $content, $m))
    {
        return $m[1] . '-' . (strlen($m[2]) == 1 ? '0' . $m[2] : $m[2]) . '-' . (strlen($m[3]) == 1 ? '0' . $m[3] : $m[3]);
    }
    if (preg_match('/(.*)\/(.*)\/(.*)/', $content, $m))
    {
        return $m[1] . '-' . (strlen($m[2]) == 1 ? '0' . $m[2] : $m[2]) . '-' . (strlen($m[3]) == 1 ? '0' . $m[3] : $m[3]);
    }
}

function json2array($content, $jsonpstr = '')
{
    $content = str_clean($content);
    if ($jsonpstr)
    {
        $content = str_replace($jsonpstr . '(', '', $content);
        $content = substr($content, 0, $content - 1);
    }
    return json_decode($content, true);
}

function get_days($content)
{
    if (preg_match('/(\d+)(\x{65e5}|\x{5929})/u', $content, $m))
    {
        return $m[1];
    }
    if (preg_match('/(\d+)(\x{6708}|\x{4e2a}\x{6708})/u', $content, $m))
    {
        return $m[1] * 30;
    }
    if (preg_match('/(\d+)\x{5e74}/u', $content, $m))
    {
        return $m[1] * 365;
    }
}

class rel2Abs
{
    function html($html, $baseurl)
    {
        $this->baseurl = $baseurl;
        return preg_replace_callback('#((href|src)\s*=\s*)("[^\":^\\"]*"|\'[^\":^\\\']*\')#', array($this, 'preg'), $html);
    }

    function url($baseurl, $rel)
    {
        return $this->createUri($baseurl, '', $rel);
    }

    function preg($m)
    {
        return $this->createUri($this->baseurl, $m[1], $m[3]);
    }

    function createUri($base = '', $pre = '', $relational_path = '')
    {
        if (strpos($relational_path, '\'') !== FALSE)
        {
            $quote = '\'';
        }
        else
        {
            $quote = '"';
        }
        $relational_path = trim($relational_path, '\'\"');
        $parse = array(
            'scheme' => null,
            'host' => null,
            'path' => null,
        );
        $parse = parse_url($base);

        if (strpos($parse['path'], '/', (strlen($parse['path']) - 1)) !== FALSE)
        {
            $parse['path'] .= '.';
        }
        if (strpos($relational_path, ':') !== FALSE)
        {
            return $pre . $quote . $relational_path . $quote;
        }
        elseif (strpos($relational_path, '//') !== FALSE)
        {
            return $pre . $quote . 'http:' . $relational_path . $quote;
        }
        elseif (preg_match("#^/.*$#", $relational_path))
        {
            $basePath = explode('/', dirname($parse ['path']));
            $path = str_replace("\\", "", implode("/", $basePath));
            return $pre . $quote . $parse['scheme'] . '://' . $parse ['host'] . $path . $relational_path . $quote;
        }
        else
        {
            $basePath = explode('/', dirname($parse ['path']));
            $relPath = explode('/', $relational_path);
            foreach ($relPath as $relDirName)
            {
                if ($relDirName == '.')
                {
                    array_shift($basePath);
                    array_unshift($basePath, '');
                }
                elseif ($relDirName == '..')
                {
                    array_pop($basePath);
                    if (count($basePath) == 0)
                    {
                        $basePath = array('');
                    }
                }
                else
                {
                    array_push($basePath, $relDirName);
                }
            }
            $path = str_replace("\\", "", implode("/", $basePath));
            return $pre . $quote . $parse ['scheme'] . '://' . $parse ['host'] . $path . $quote;
        }
    }
}

function rel2abs($content, $base)
{
    $rel2abs = new rel2Abs();
    return $rel2abs->html($content, $base);
}

function findtext($patten, $subject, $find)
{
    $i = 0;
    $m = array();
    while (1)
    {
        if (preg_match($patten, $subject, $m, PREG_OFFSET_CAPTURE, $i))
        {
            if (strpos($m[0][0], $find))
            {
                return $m[0][0];
            }
            else
            {
                $i = $m[0][1] + 5;
            }
        }
        else
        {
            return '';
        }
    }
}

function html2text($document)
{

    // I didn't use preg eval (//e) since that is only available in PHP 4.0.
    // so, list your entities one by one here. I included some of the
    // more common ones.

    $search = array("'<script[^>]*?>.*?</script>'si", // strip out javascript
        "'{.*?}'si",
        "'<[\/\!]*?[^<>]*?>'si", // strip out html tags
        "'([\r\n])[\s]+'", // strip out white space
        "'&(quot|#34|#034|#x22);'i", // replace html entities
        "'&(amp|#38|#038|#x26);'i", // added hexadecimal values
        "'&(lt|#60|#060|#x3c);'i",
        "'&(gt|#62|#062|#x3e);'i",
        "'&(nbsp|#160|#xa0);'i",
        "'&(iexcl|#161);'i",
        "'&(cent|#162);'i",
        "'&(pound|#163);'i",
        "'&(copy|#169);'i",
        "'&(reg|#174);'i",
        "'&(deg|#176);'i",
        "'&(#39|#039|#x27);'",
        "'&(euro|#8364);'i", // europe
        "'&a(uml|UML);'", // german
        "'&o(uml|UML);'",
        "'&u(uml|UML);'",
        "'&A(uml|UML);'",
        "'&O(uml|UML);'",
        "'&U(uml|UML);'",
        "'&szlig;'i",
    );
    $replace = array("",
        "", "",
        "\\1",
        "\"",
        "&",
        "<",
        ">",
        " ",
        chr(161),
        chr(162),
        chr(163),
        chr(169),
        chr(174),
        chr(176),
        chr(39),
        chr(128),
        "ä",
        "ö",
        "ü",
        "Ä",
        "Ö",
        "Ü",
        "ß",
    );

    $text = preg_replace($search, $replace, $document);

    return $text;
}

function toUTF8($data)
{
    if (!empty($data))
    {
        $filetype = mb_detect_encoding($data, array('UTF-8', 'GBK', 'CP936', 'BIG5'));
        if ($filetype != 'UTF-8')
        {
            $data = mb_convert_encoding($data, 'UTF-8', $filetype);
        }
    }
    return $data;
}
