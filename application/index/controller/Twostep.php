<?php

namespace app\index\controller;

use addons\twostep\library\GoogleAuthenticator;
use addons\twostep\library\WebAuthn;
use app\common\library\Token;
use app\common\model\User;
use fast\Random;
use think\Config;
use think\Cookie;
use think\Db;
use think\Session;
use app\common\controller\Frontend;
use think\Exception;
use app\common\library\Auth;
use addons\twostep\library\SafeCode;

/**
 * 两步验证前台
 */
class TwoStep extends Frontend
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
            ->where('isadmin', '=', 0)
            ->find();
        if ($check) {
            $keys = json_decode($check['webauthndata'], true);//调取安全密钥数据 循环显示
        } else {
            $keys = '';
        }

        $this->view->assign('title', "两步验证");
        $this->view->assign('check', $check);
        $this->view->assign('keys', $keys);
        return $this->view->fetch('index');
    }

    /*
     * 两步验证设置
     */
    public function set()
    {
        return $this->index();//调用index 避免会员中心高亮冲突
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
            ->where('isadmin', '=', 0)
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
            $url = $ga->getQRCodeGoogleUrl($this->auth->username . '@' . $_SERVER['HTTP_HOST'], $secret);
            $this->view->assign('url', $url);
            $this->view->assign('secret', $secret);

        } else {
            $this->error('您已经配置过动态口令了', url("index/twostep/set"));
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
                    ->where('isadmin', '=', 0)
                    ->find();
                $params = [];
                $params['secret'] = $secret;
                if ($check) {
                    Db::name('twostep')->where('user_id', '=', $this->auth->id)->where('isadmin', '=', 0)->update($params);
                } else {
                    $params['user_id'] = $this->auth->id;
                    $params['isadmin'] = 0;
                    $params['createtime'] = time();
                    $params['updatetime'] = time();
                    Db::name('twostep')->strict(false)->insert($params);
                }
                if (Session::get('twostep_secret')) {
                    Session::delete('twostep_secret');//设置完毕清除存储的动态密钥
                }
                $this->success('动态口令设置成功', url("index/twostep/set"));
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
            ->where('isadmin', '=', 0)
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
                    ->where('isadmin', '=', 0)
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
                    Db::name('twostep')->where('user_id', '=', $this->auth->id)->where('isadmin', '=', 0)->update($params);
                } else {
                    $params['user_id'] = $this->auth->id;
                    $params['isadmin'] = 0;
                    $params['createtime'] = time();
                    Db::name('twostep')->strict(false)->insert($params);
                }
                $this->success('安全密钥设置成功', url("index/twostep/set"));
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
            ->where('isadmin', '=', 0)
            ->find();//检测用户是否设置过安全密钥
        if (!$check) {
            $this->error('无数据');
        }
        if ($check && !$check['webauthndata']) {
            $this->error('没有配置过安全密钥', url("index/twostep/set"));
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
        $del = Db::name('twostep')->where('user_id', '=', $this->auth->id)->where('isadmin', '=', 0)->update($params);
        if ($del) {
            $this->success("安全密钥移除成功", url("index/twostep/set"));
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
            ->where('isadmin', '=', 0)
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
        $del = Db::name('twostep')->where('user_id', '=', $this->auth->id)->where('isadmin', '=', 0)->update($params);
        if ($del) {
            $this->success($msg, url("index/twostep/set"));
        }
    }

    /**
     * 用户登录验证
     */
    public function login()
    {
        $this->view->assign('title', '两步验证-登录');
        $key = Config::get('token.key');
        Cookie::set('twostep_login', SafeCode::authCode(2, 'ENCODE', $key));
        $uid = Cookie::get('twostep_uid');//用户uid
        $user = User::get($uid);
        $login_failure_retry = Config::get('fastadmin.login_failure_retry');
        if ($login_failure_retry && $user->loginfailure >= 10 && time() - $user->updatetime < 86400) {
            $this->error('错误次数过多,请24小时以后再试', url("user/login"));
        }
        $twostep = Db::name('twostep')
            ->where('user_id', $uid)
            ->where('isadmin', '=', 0)
            ->find(); //获取用户 无数据的不会跳转到这里
        if (!$twostep) {
            $this->error('无数据');
        }
        if ($twostep['webauthndata']) {//默认硬件优先 页面可以自由切换验证类型
            $this->redirect(url("index/twostep/loginwebauthn"));//安全密钥
        } else {
            if ($twostep['secret']) {
                $this->redirect(url("index/twostep/logintotp"));//动态口令
            }
        }
    }

    /**
     * 动态口令登录
     */
    public function logintotp()
    {
        $key = Config::get('token.key');
        Cookie::set('twostep_login', SafeCode::authCode(2, 'ENCODE', $key));
        $uid = Cookie::get('twostep_uid');//用户uid
        $twostep = Db::name('twostep')
            ->where('user_id', $uid)
            ->where('isadmin', '=', 0)
            ->find(); //获取用户
        if (!$twostep) {
            $this->error('无数据');
        }
        if (!$twostep['secret']) {
            $this->error("未启用 动态口令");
        }
        $other = 0;
        if ($twostep['webauthndata']) {
            $other = 1;
        }
        $user = User::get($uid);
        $login_failure_retry = Config::get('fastadmin.login_failure_retry');
        if ($login_failure_retry && $user->loginfailure >= 10 && time() - $user->updatetime < 86400) {
            $this->error('错误次数过多,请24小时以后再试', url("user/login"));
        }
        $this->view->assign('other', $other);
        $this->view->assign('title', '两步验证-动态口令登录');
        return $this->view->fetch();
    }

    /**
     * 安全密钥登录
     */
    public function loginwebauthn()
    {
        $uid = Cookie::get('twostep_uid');//用户uid
        $twostep = Db::name('twostep')
            ->where('user_id', $uid)
            ->where('isadmin', '=', 0)
            ->find();//获取用户
        if (!$twostep) {
            $this->error('无数据');
        }
        if (!$twostep['webauthndata']) {
            $this->error("未启用安全密钥");
        }
        $other = 0;
        if ($twostep['secret']) {
            $other = 1;
        }
        $user = User::get($uid);
        $login_failure_retry = Config::get('fastadmin.login_failure_retry');
        if ($login_failure_retry && $user->loginfailure >= 10 && time() - $user->updatetime < 86400) {
            $this->error('错误次数过多,请24小时以后再试', url("user/login"));
        }
        $webauthn = new WebAuthn($_SERVER['HTTP_HOST']);
        $j['challenge'] = $webauthn->prepareForLogin($twostep['webauthndata']);
        $j = json_encode($j);
        Session::set('original', $j);//设置密钥源
        $this->view->assign('j', $j);
        $this->view->assign('other', $other);
        $this->view->assign('title', '两步验证-安全密钥登录');
        return $this->view->fetch();
    }

    /**
     * 登录验证处理
     */
    public function logincheck()
    {
        $type = $this->request->request('type');//验证类型 totp(动态口令)  webauthn(安全密钥)
        $uid = Cookie::get('twostep_uid');
        $user = User::get($uid);
        $login_failure_retry = Config::get('fastadmin.login_failure_retry');
        if ($login_failure_retry && $user->loginfailure >= 10 && time() - $user->updatetime < 86400) {
            $this->error('错误次数过多,请24小时以后再试', url("user/login"));
        }
        $key = Config::get('token.key');
        $twostep = Db::name('twostep')
            ->where('user_id', $uid)
            ->where('isadmin', '=', 0)
            ->find();
        if (!$twostep) {
            $this->error('无数据');
        }
        if ($type == "totp") {
            $code = $this->request->request('twostep_code');
            $ga = new GoogleAuthenticator();
            $check_code = $ga->verifyCode($twostep['secret'], $code, 2);
            if ($check_code) {
                $ret = $this->direct($uid);
                if ($ret) {
                    Cookie::set('twostep_login', SafeCode::authCode(1, 'ENCODE', $key));
                    $this->redirect(url("user/index"));
                } else {
                    $user->loginfailure++;//错误次数统计
                    $user->save();
                    $this->error('动态口令验证失败,请重新输入', url("index/twostep/logintotp"));
                }
            } else {
                $user->loginfailure++;//错误次数统计
                $user->save();
                $this->error('动态口令验证失败,请重新输入', url("index/twostep/logintotp"));
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
                ->where('isadmin', '=', 0)
                ->find();
            if ($original->challenge !== $authenticate2->originalChallenge) {
                $this->error('密钥认证信息错误,请重新登录', url("user/login"));
            }
            $webauthn_login = $webauthn->authenticate($authenticate, $user['webauthndata']);
            if ($webauthn_login) {
                $ret = $this->direct($uid);
                if ($ret) {
                    Session::delete('original');//删除密钥源
                    Cookie::set('twostep_login', SafeCode::authCode(1, 'ENCODE', $key));
                    $this->redirect(url("user/index"));
                } else {
                    $user->loginfailure++;//错误次数统计
                    $user->save();
                    $this->error('安全密钥验证失败,请重新验证', url("index/twostep/loginwebauthn"));
                }
            } else {
                $user->loginfailure++;//错误次数统计
                $user->save();
                $this->error('等待安全密钥超时,请重新验证', url("index/twostep/loginwebauthn"));
            }
        }
    }

    /**
     * 直接登录账号
     */
    public function direct($user_id)
    {
        $user = User::get($user_id);
        if ($user) {
            Db::startTrans();
            try {
                $ip = request()->ip();
                $time = time();
                $keeptime = 2592000;
                //判断连续登录和最大连续登录
                if ($user->logintime < \fast\Date::unixtime('day')) {
                    $user->successions = $user->logintime < \fast\Date::unixtime('day', -1) ? 1 : $user->successions + 1;
                    $user->maxsuccessions = max($user->successions, $user->maxsuccessions);
                }
                $user->prevtime = $user->logintime;
                //记录本次登录的IP和时间
                $user->loginip = $ip;
                $user->logintime = $time;
                //重置登录失败次数
                $user->loginfailure = 0;
                $user->save();
                $this->_user = $user;
                $this->_token = Random::uuid();
                Token::set($this->_token, $user->id, $keeptime);
                $this->_logined = true;
                //登录成功的事件
                $expire = 30 * 86400;
                Cookie::set('uid', $user->id, $expire);
                Cookie::set('token', $this->_token, $expire);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->setError($e->getMessage());
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置错误信息
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
