<?php
/**
 *　                  oooooooooooo
 *
 *                  ooooooooooooooooo
 *                       o
 *                      o
 *                     o        o
 *                    oooooooooooo
 *
 *         ～～         ～～         　　～～
 *       ~~　　　　　~~　　　　　　　　~~
 * ~~～~~～~~　　　~~~～~~～~~～　　　~~~～~~～~~～
 * ·······              ~~XYHCMS~~            ·······
 * ·······  闲看庭前花开花落 漫随天外云卷云舒 ·······
 * ·············     www.xyhcms.com     ·············
 * ··················································
 * ··················································
 *
 * @Author: gosea <gosea199@gmail.com>
 * @Date:   2014-06-21 10:00:00
 * @Last Modified by:   gosea
 * @Last Modified time: 2016-09-27 11:46:40
 */
namespace Api\Controller;

class MkClientController extends ApiCommonController {

	protected $app_secret; //申请应用时分配密钥

	protected $client_user; //用户账号
	protected $client_token; //账号登录后返回给客户端授权码access_token

	protected $account = array(); //当前账号信息

	/**
	 * 初始化
	 */
	public function _initialize() {
		$this->app_secret  = I('app_secret', '');
		$this->client_user = I('username', '');
		$result            = $this->chkLogin();
		if (!$result['status']) {
			$this->RetrunMsg($result['msg']);
		}

	}

	/*
		         *   检测账号及新版本及通知--需要第三方网站登录
	*/
	public function addArticle() {
		if (!IS_POST) {
			$this->RetrunMsg('[240]请求失败');
		}

		$data                = I('post.');
		$data['cid']         = I('cid', 0, 'intval');
		$data['title']       = I('title', '', 'htmlspecialchars,rtrim');
		$data['shorttitle']  = I('shorttitle', '', 'htmlspecialchars,rtrim');
		$data['keywords']    = trim($data['keywords']);
		$data['content']     = I('content', '', '');
		$data['publishtime'] = I('publishtime', time(), 'strtotime');
		$data['updatetime']  = I('updatetime', time(), 'intval');
		$data['click']       = I('click', 0, 'intval');
		$data['status']      = I('status', 0, 'intval');
		$data['aid']         = $this->uid;
		$data['content']     = preg_replace("@<script(.*?)</script>@is", '', $data['content']);

		$pid   = intval($data['pid']);
		$flags = I('flags', array(), 'intval');
		$pic   = $data['litpic'];

		if (empty($data['title'])) {
			$this->RetrunMsg('[241]标题不能为空');
		}
		if (!$data['cid']) {
			$this->RetrunMsg('[242]请选择栏目');
		}
		if (empty($data['description'])) {
			$data['description'] = str2sub(strip_tags($data['content']), 120);
		}

		if (M('article')->where(array('title' => $data['title']))->find()) {
			$this->RetrunMsg('[243]文章重复');
		}

		if ($id = M('article')->add($data)) {
			//更新静态缓存
			del_cache_html('List/index_' . $data['cid'], false, 'list:index');
			del_cache_html('Index_index', false, 'index:index');

			//Delete blog archive
			get_datelist($modelid, 2);

			$this->RetrunMsg('[200]添加文章成功');
		} else {
			$this->RetrunMsg('[244]添加文章失败');
		}
	}

	public function chkLogin() {
		$result = array('status' => 0, 'msg' => '', 'data' => '');

		$username = I('username', '', 'htmlspecialchars,trim');
		$password = I('pss', '', 'base64_decode'); //md5的密码

		//安全验证
		$sign      = I('sign', ''); //md5签名=md5(参数+软件的key)
		$sign_type = I('sign_type', 'md5');

		if (empty($username)) {
			$result['msg'] = '[141]用户名不能为空';
			return $result;
		}

		if (strlen($password) != 32) {
			$result['msg'] = '[142]密码不正确';
			return $result;
		}

		$user = M('admin')->where(array('username' => $username))->find();
		if (!$user) {
			$result['msg'] = '[143]您输入的账号不存在，请确认后输入';
			return $result;
		}
		if ($user['password'] != get_password_md5($password, $user['encrypt'])) {
			$result['msg'] = '[144]您输入的密码不正确';
			return $result;
		}

		if ($user['islock']) {
			$result['msg'] = '[145]用户被锁定!';
			return $result;
		}

		$this->uid = $user['id'];

		$result['status'] = 1;
		$result['msg']    = '[100]登录成功';
		return $result;

	}

	public function RetrunMsg($str) {
		header("Content-type: text/html; charset=utf-8");
		echo $str;
		exit();
	}

}
