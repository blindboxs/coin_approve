<?php

namespace app\admin\controller\general;

use app\admin\model\Admin;
use app\common\controller\Backend;
use addons\twostep\library\GoogleAuthenticator;
use addons\twostep\library\WebAuthn;
use think\Config;
use think\Db;
use think\Exception;
use think\Session;
use app\admin\library\Auth;

/**
 * 两步验证后台
 */
class TwoStep extends Backend
{
    protected $layout = 'default';
    protected $noNeedLogin = ['login', 'logintotp', 'loginwebauthn', 'logincheck'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $https = 0;
        if (isset($_SERVER['HTTPS'])) {
            $https = 1;
        }
        $conf = get_addon_config('twostep');
        $this->view->assign('https', $https);
        $this->view->assign('twostep_config', $conf);
    }

    /**
     * 两步验证首页
     */
    public function index()
    {
        if (!$this->auth->id) {
            $this->error(__('You are not logged in'));
        }
        $check = Db::name('twostep')
            ->where('user_id', $this->auth->id)
            ->where('isadmin', '=', 1)
            ->find();
        if ($check) {
            $keys = json_decode($check['webauthndata'], true);//调取安全密钥数据 循环显示
        } else {
            $keys = '';
        }

        $this->view->assign('title', "两步验证");
        $this->view->assign('check', $check);
        $this->view->assign('keys', $keys);
        return $this->view->fetch();
    }

    /**
     *  动态口令设置
     */
    public function totp()
    {
        if (!$this->auth->id) {
            $this->error(__('You are not logged in'));
        }
        $check = Db::name('twostep')
            ->where('user_id', $this->auth->id)
            ->where('isadmin', '=', 1)
            ->find(); //检测用户是否设置过动态口令
        if (!$check || empty($check['secret'])) {
            $ga = new GoogleAuthenticator();
            $secret = $ga->createSecret();
            if (Session::get('twostep_secret')) {
                $secret = Session::get('twostep_secret');
            } else {
                $secret = $ga->createSecret();
                Session::set('twostep_secret', $secret);
            }
            $url = $ga->getQRCodeGoogleUrl($this->auth->username . '_ADMIN_@' . $_SERVER['HTTP_HOST'], $secret);
            $this->view->assign('url', $url);
            $this->view->assign('secret', $secret);
        } else {
            $this->error('您已经配置过动态口令了', url("general/twostep/index"));
        }
        $this->view->assign('check', $check);
        $this->view->assign('title', '两步验证-动态口令');
        return $this->view->fetch();
    }

    /**
     * 动态口令确认
     */
    public function totpreg()
    {
        if (!$this->auth->id) {
            $this->error(__('You are not logged in'));
        }
        $code = $this->request->request('twostep_code');
        $secret = Session::get('twostep_secret');
        if (empty($secret)) {
            $this->error('动态密钥错误');
            exit();
        }
        $ga = new GoogleAuthenticator();
        $check_code = $ga->verifyCode($secret, $code, 2);
        if ($check_code) {
            try {
                $check = Db::name('twostep')
                    ->where('user_id', $this->auth->id)
                    ->where('isadmin', '=', 1)
                    ->find();
                $params = [];
                $params['secret'] = $secret;
                if ($check) {
                    Db::name('twostep')->where('user_id', '=', $this->auth->id)->where('isadmin', '=', 1)->update($params);
                } else {
                    $params['user_id'] = $this->auth->id;
                    $params['isadmin'] = 1;
                    $params['createtime'] = time();
                    $params['updatetime'] = time();
                    Db::name('twostep')->strict(false)->insert($params);
                }
                if (Session::get('twostep_secret')) {
                    Session::delete('twostep_secret');//设置完毕清除存储的动态密钥
                }
                $this->success('动态口令设置成功', url("general/twostep/index"));
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        } else {
            $this->error('动态口令验证错误');
            exit();
        }
        return;
    }

    /**
     * 安全密钥设置
     */
    public function webauthn()
    {
        if (!$this->auth->id) {
            $this->error(__('You are not logged in'));
        }
        $check = Db::name('twostep')
            ->where('user_id', $this->auth->id)
            ->where('isadmin', '=', 1)
            ->find();//检测用户是否设置过 安全密钥
        $username = $this->auth->username;
        Session::set('webauthn_username', $username);
        $exclude = array();
        $denies = array();
        if ($check && $check['webauthndata']) {
            $exclude = $check['webauthndata'];
        }

        if (!empty($exclude)) {
            $deny = (object)array();
            $deny->type = 'public-key';
            $deny->transports = array('usb', 'nfc', 'ble', 'internal');
            foreach (json_decode($exclude) as $key) {
                $deny->id = $key->id;
                $denies[] = clone $deny;
            }
        }
        $deny_key = $denies;
        $webauthn = new WebAuthn($_SERVER['HTTP_HOST']);
        $j = ['challenge' => $webauthn->prepareChallengeForRegistration($username, $this->auth->id, $deny_key, true)];
        $j = json_encode($j);
        $this->view->assign('username', $username);
        $this->view->assign('j', $j);
        $this->view->assign('title', '两步验证-添加安全密钥');
        return $this->view->fetch();
    }

    /**
     * 安全密钥注册到系统
     */
    public function webauthnreg()
    {
        if (!$this->auth->id) {
            $this->error(__('You are not logged in'));
        }
        $webauthn = new WebAuthn($_SERVER['HTTP_HOST']);
        if ($this->request->isPost()) {
            $register = $this->request->request('register');
            if ($register) {
                $register = htmlspecialchars_decode($register);
                $register = json_decode($register);
                $check = Db::name('twostep')
                    ->where('user_id', $this->auth->id)
                    ->where('isadmin', '=', 1)
                    ->find();
                $keyname = $this->request->request('keyname');//安全密钥别名
                $keyid = "key" . time();//安全密钥注册序号 根据时间戳生成
                if (empty($keyname)) {
                    $keyname = $keyid;//如果没密钥别名 使用$keyid
                }
                $info = json_decode($register);
                $userwebauthn = json_encode(array('id' => $webauthn->arrayToString($info->rawId), 'key' => ''));
                $webauthnkeys = $webauthn->register($register, $userwebauthn);
                $params = [];
                $params['updatetime'] = time();
                if ($webauthnkeys) {
                    $new_keys = json_decode($webauthnkeys, true);
                    $new_keys['0']['keyid'] = $keyid;
                    $new_keys['0']['name'] = $keyname;
                    $new_keys['0']['time'] = time();
                    $mykeys[] = $new_keys['0'];
                    $params['webauthndata'] = json_encode($mykeys, JSON_UNESCAPED_UNICODE);
                }
                if ($check) {
                    if ($check['webauthndata']) {
                        $keys = json_decode($check['webauthndata'], true);
                        $keys[] = $new_keys['0'];//如果已存在安全密钥记录 追加安全密钥数据
                        $params['webauthndata'] = json_encode($keys, JSON_UNESCAPED_UNICODE);
                    }
                    Db::name('twostep')->where('user_id', '=', $this->auth->id)->where('isadmin', '=', 1)->update($params);
                } else {
                    $params['user_id'] = $this->auth->id;
                    $params['isadmin'] = 1;
                    $params['createtime'] = time();
                    Db::name('twostep')->strict(false)->insert($params);
                }
                $this->success('安全密钥设置成功', url("general/twostep/index"));
            }
        }
    }

    /**
     * 移除安全密钥
     */
    public function remove()
    {
        if (!$this->auth->id) {
            $this->error(__('You are not logged in'));
        }
        $check = Db::name('twostep')
            ->where('user_id', $this->auth->id)
            ->where('isadmin', '=', 1)
            ->find();//检测用户是否设置过安全密钥
        if (!$check) {
            $this->error('无数据');
        }
        if ($check && !$check['webauthndata']) {
            $this->error('没有配置过安全密钥', url("general/twostep/index"));
        }
        $keyid = $this->request->request('keyid');
        $keys = json_decode($check['webauthndata'], true);
        foreach ($keys as $k => $v) {
            if ($v['keyid'] == $keyid) {
                unset($keys[$k]);
                continue;
            }
        }
        sort($keys);
        if (empty($keys)) {
            $params['webauthndata'] = '';
        } else {
            $params['webauthndata'] = json_encode($keys, JSON_UNESCAPED_UNICODE);
        }
        $del = Db::name('twostep')->where('user_id', '=', $this->auth->id)->where('isadmin', '=', 1)->update($params);
        if ($del) {
            $this->success("安全密钥移除成功", url("general/twostep/index"));
        }
    }

    /**
     * 删除两步验证数据
     */
    public function cancel()
    {
        $type = $this->request->request('type');
        $check = Db::name('twostep')
            ->where('user_id', $this->auth->id)
            ->where('isadmin', '=', 1)
            ->find();
        if (!$check) {
            $this->error('无数据');
        }
        $params = [];
        if ($type == "totp") {
            if (empty($check['secret'])) {
                $this->error('未设置动态口令');
            }
            $params['secret'] = '';
            $msg = '取消动态口令成功';
        }
        $params['updatetime'] = time();
        $del = Db::name('twostep')->where('user_id', '=', $this->auth->id)->where('isadmin', '=', 1)->update($params);
        if ($del) {
            $this->success($msg, url("general/twostep/index"));
        }
    }

    /**
     * 用户登录验证
     */
    public function login()
    {
        $this->view->assign('title', '两步验证-登录');
        Session::set('twostep_admin_login', 2);
        $this->auth->logout();//先退出进入验证页
        $uid = Session::get('twostep_adminid');//管理员id
        $username = Session::get('twostep_username');
        $admin = Admin::get(['username' => $username]);
        if (!$admin) {
            $this->error('用户信息错误', url("index/login"));
        }
        if (Config::get('fastadmin.login_failure_retry') && $admin->loginfailure >= 10 && time() - $admin->updatetime < 86400) {
            $this->error('错误次数过多,请24小时以后再试', url("index/login"));
        }
        $twostep = Db::name('twostep')
            ->where('user_id', $uid)
            ->where('isadmin', '=', 1)
            ->find(); //获取用户数据 无数据的不会跳转到这里
        if (!$twostep) {
            $this->error('无数据');
        }
        if ($twostep['webauthndata']) {//默认硬件优先 页面可以自由切换验证类型
            $this->redirect(url("general/twostep/loginwebauthn"));//安全密钥
        } elseif ($twostep['secret']) {
            $this->redirect(url("general/twostep/logintotp"));//动态口令
        }
    }

    /**
     * 动态口令登录
     */
    public function logintotp()
    {
        Session::set('twostep_admin_login', 2);
        $uid = Session::get('twostep_adminid');//用户uid
        $twostep = Db::name('twostep')
            ->where('user_id', $uid)
            ->where('isadmin', '=', 1)
            ->find(); //获取用户
        if (!$twostep) {
            $this->error('无数据');
        }
        if (!$twostep['secret']) {
            $this->error("未启用动态口令");
        }
        $other = 0;
        if ($twostep['webauthndata']) {
            $other = 1;
        }
        $username = Session::get('twostep_username');
        $admin = Admin::get(['username' => $username]);
        if (!$admin) {
            $this->error('用户信息错误', url("index/login"));
        }
        if (Config::get('fastadmin.login_failure_retry') && $admin->loginfailure >= 10 && time() - $admin->updatetime < 86400) {
            $this->error('错误次数过多,请24小时以后再试', url("index/login"));
        }
        $this->view->assign('other', $other);
        $this->view->assign('title', '两步验证-动态口令登录');
        $this->view->engine->layout(false);
        return $this->view->fetch();
    }

    /**
     * 安全密钥登录
     */
    public function loginwebauthn()
    {
        $uid = Session::get('twostep_adminid');//用户uid
        $twostep = Db::name('twostep')
            ->where('user_id', $uid)
            ->where('isadmin', '=', 1)
            ->find();//获取用户
        if (!$twostep) {
            $this->error('无数据');
        }
        if (!$twostep['webauthndata']) {
            $this->error("未启用 安全密钥");
        }
        $other = 0;
        if ($twostep['secret']) {
            $other = 1;
        }
        $username = Session::get('twostep_username');
        $admin = Admin::get(['username' => $username]);
        if (!$admin) {
            $this->error('用户信息错误', url("index/login"));
        }
        if (Config::get('fastadmin.login_failure_retry') && $admin->loginfailure >= 10 && time() - $admin->updatetime < 86400) {
            $this->error('错误次数过多,请24小时以后再试', url("index/login"));
        }
        $webauthn = new WebAuthn($_SERVER['HTTP_HOST']);
        $j['challenge'] = $webauthn->prepareForLogin($twostep['webauthndata']);
        $j = json_encode($j);
        Session::set('original', $j);//设置密钥源
        $this->view->assign('j', $j);
        $this->view->assign('other', $other);
        $this->view->assign('title', '两步验证-安全密钥登录');
        $this->view->engine->layout(false);
        return $this->view->fetch();
    }

    /**
     * 登录验证处理
     */
    public function logincheck()
    {
        $type = $this->request->request('type');//验证类型 totp(动态口令)  webauthn(安全密钥)
        $uid = Session::get('twostep_adminid');
        $twostep = Db::name('twostep')
            ->where('user_id', $uid)
            ->where('isadmin', '=', 1)
            ->find();
        if (!$twostep) {
            $this->error('无数据');
        }
        $username = Session::get('twostep_username');
        $password = Session::get('twostep_password');
        $keeplogin = Session::get('twostep_keeplogin');
        $admin = Admin::get(['username' => $username]);
        if (!$admin) {
            $this->error('用户信息错误', url("index/login"));
        }
        if (Config::get('fastadmin.login_failure_retry') && $admin->loginfailure >= 10 && time() - $admin->updatetime < 86400) {
            $this->error('错误次数过多,请24小时以后再试', url("index/login"));
        }
        $key = Config::get('token.key');
        if ($type == "totp") {
            $code = $this->request->request('twostep_code');
            $ga = new GoogleAuthenticator();
            $check_code = $ga->verifyCode($twostep['secret'], $code, 2);
            if ($check_code) {
                Session::set('twostep_admin_login', 1);
                $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
                if ($result === true) {
                    Session::delete('twostep_username');
                    Session::delete('twostep_password');
                    Session::delete('twostep_keeplogin');
                    $this->redirect(url("index/index"));
                } else {
                    $admin->loginfailure++;//错误次数统计
                    $admin->save();
                    $this->error('动态口令验证失败,请重新输入', url("general/twostep/logintotp"));
                }
            } else {
                $admin->loginfailure++;//错误次数统计
                $admin->save();
                $this->error('动态口令验证失败,请重新输入', url("general/twostep/logintotp"));
            }
        }
        if ($type == "webauthn") {
            $webauthn = new WebAuthn($_SERVER['HTTP_HOST']);
            $authenticate = $this->request->request('authenticate');
            $authenticate = htmlspecialchars_decode($authenticate);
            $authenticate = json_decode($authenticate);
            $original = Session::get('original');
            $original = json_decode($original);
            $original = json_decode($original->challenge);
            $authenticate2 = json_decode($authenticate);
            $user = Db::name('twostep')
                ->where('user_id', $uid)
                ->where('isadmin', '=', 1)
                ->find();
            if ($original->challenge !== $authenticate2->originalChallenge) {
                $this->error('密钥认证信息错误,请重新登录', url("user/login"));
            }
            $webauthn_login = $webauthn->authenticate($authenticate, $user['webauthndata']);
            if ($webauthn_login) {
                Session::delete('original');//删除密钥源
                Session::set('twostep_admin_login', 1);
                $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
                if ($result === true) {
                    Session::delete('twostep_username');
                    Session::delete('twostep_password');
                    Session::delete('twostep_keeplogin');
                    $this->redirect(url("index/index"));
                } else {
                    $admin->loginfailure++;
                    $admin->save();
                    $this->error('安全密钥验证失败,请重新验证', url("general/twostep/loginwebauthn"));
                }
            } else {
                $admin->loginfailure++;
                $admin->save();
                $this->error('等待安全密钥超时,请重新验证', url("general/twostep/loginwebauthn"));
            }
        }
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return string
     */
    private function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    private function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }

}
