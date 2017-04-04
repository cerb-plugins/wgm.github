<?php
class WgmGitHub_API {
	const BASE_URL = "https://api.github.com/";
	const OAUTH_ACCESS_TOKEN_URL = "https://github.com/login/oauth/access_token";
	const OAUTH_AUTHENTICATE_URL = "https://github.com/login/oauth/authorize";
	
	static $_instance = null;
	private $_oauth = null;
	
	private function __construct() {
		if(false == ($credentials = DevblocksPlatform::getPluginSetting('wgm.github','credentials',false,true,true)))
			return;
		
		if(!isset($credentials['consumer_key']) || !isset($credentials['consumer_secret']))
			return;
		
		$this->_oauth = DevblocksPlatform::getOAuthService($credentials['consumer_key'], $credentials['consumer_secret']);
	}
	
	/**
	 * @return WgmGitHub_API
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new WgmGitHub_API();
		}

		return self::$_instance;
	}
	
	public function setToken($token) {
		$this->_oauth->setTokens($token);
	}
	
	public function post($path, $params) {
		return $this->_fetch($path, 'POST', $params);
	}
	
	public function get($path) {
		return $this->_fetch($path, 'GET');
	}
	
	private function _fetch($path, $method = 'GET', $params = array()) {
		$url = self::BASE_URL . ltrim($path, '/');
		return $this->_oauth->executeRequestWithToken($method, $url, $params, 'token');
	}
};

if(class_exists('Extension_PageMenuItem')):
class WgmGitHub_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const POINT = 'wgmgithub.setup.menu.plugins.github';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:wgm.github::setup/menu_item.tpl');
	}
};
endif;

if(class_exists('Extension_PageSection')):
class WgmGitHub_SetupSection extends Extension_PageSection {
	const ID = 'wgmgithub.setup.github';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'github');
		
		$credentials = DevblocksPlatform::getPluginSetting('wgm.github','credentials',false,true,true);
		$tpl->assign('credentials', $credentials);
		
		$tpl->display('devblocks:wgm.github::setup/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			@$consumer_key = DevblocksPlatform::importGPC($_REQUEST['consumer_key'],'string','');
			@$consumer_secret = DevblocksPlatform::importGPC($_REQUEST['consumer_secret'],'string','');
			
			if(empty($consumer_key) || empty($consumer_secret))
				throw new Exception("Both the 'Client ID' and 'Client Secret' are required.");

			$credentials = [
				'consumer_key' => $consumer_key,
				'consumer_secret' => $consumer_secret,
			];
			
			DevblocksPlatform::setPluginSetting('wgm.github', 'credentials', $credentials, true, true);
			
			echo json_encode(array('status'=>true, 'message'=>'Saved!'));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false, 'error'=>$e->getMessage()));
			return;
		}
	}
	
};
endif;

class ServiceProvider_GitHub extends Extension_ServiceProvider implements IServiceProvider_OAuth, IServiceProvider_HttpRequestSigner {
	const ID = 'wgm.github.service.provider';

	function renderConfigForm(Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('account', $account);
		
		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:wgm.github::provider/github.tpl');
	}
	
	function saveConfigForm(Model_ConnectedAccount $account, array &$params) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		$active_worker = CerberusApplication::getActiveWorker();
		$encrypt = DevblocksPlatform::getEncryptionService();
		
		// Decrypt OAuth params
		if(isset($edit_params['params_json'])) {
			if(false == ($outh_params_json = $encrypt->decrypt($edit_params['params_json'])))
				return "The connected account authentication is invalid.";
				
			if(false == ($oauth_params = json_decode($outh_params_json, true)))
				return "The connected account authentication is malformed.";
			
			if(is_array($oauth_params))
			foreach($oauth_params as $k => $v)
				$params[$k] = $v;
		}
		
		return true;
	}
	
	private function _getAppKeys() {
		$credentials = DevblocksPlatform::getPluginSetting('wgm.github','credentials',false,true,true);
		
		if(!isset($credentials['consumer_key']) || !isset($credentials['consumer_secret']))
			return false;
		
		return array(
			'key' => $credentials['consumer_key'],
			'secret' => $credentials['consumer_secret'],
		);
	}
	
	function oauthRender() {
		@$form_id = DevblocksPlatform::importGPC($_REQUEST['form_id'], 'string', '');
		
		// Store the $form_id in the session
		$_SESSION['oauth_form_id'] = $form_id;
		
		$url_writer = DevblocksPlatform::getUrlService();
		
		// [TODO] Report about missing app keys
		if(false == ($app_keys = $this->_getAppKeys()))
			return false;
		
		$oauth = DevblocksPlatform::getOAuthService($app_keys['key'], $app_keys['secret']);
		
		// Persist the view_id in the session
		$_SESSION['oauth_state'] = CerberusApplication::generatePassword(24);
		
		// OAuth callback
		$redirect_url = $url_writer->write(sprintf('c=oauth&a=callback&ext=%s', ServiceProvider_GitHub::ID), true);

		$url = sprintf("%s?&client_id=%s&redirect_uri=%s&state=%s&scope=%s", 
			WgmGitHub_API::OAUTH_AUTHENTICATE_URL,
			$app_keys['key'],
			rawurlencode($redirect_url),
			$_SESSION['oauth_state'],
			rawurlencode('user public_repo notifications')
		);
		
		header('Location: ' . $url);
	}
	
	function oauthCallback() {
		@$oauth_state = $_SESSION['oauth_state'];
		
		$form_id = $_SESSION['oauth_form_id'];
		unset($_SESSION['oauth_form_id']);
		
		@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
		@$state = DevblocksPlatform::importGPC($_REQUEST['state'], 'string', '');
		@$error = DevblocksPlatform::importGPC($_REQUEST['error'], 'string', '');
		@$error_msg = DevblocksPlatform::importGPC($_REQUEST['error_description'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		$encrypt = DevblocksPlatform::getEncryptionService();
		
		$redirect_url = $url_writer->write(sprintf('c=oauth&a=callback&ext=%s', ServiceProvider_GitHub::ID), true);
		
		if(false == ($app_keys = $this->_getAppKeys()))
			return false;
		
		// [TODO] Check $error state
		// [TODO] Compare $state
		
		$oauth = DevblocksPlatform::getOAuthService($app_keys['key'], $app_keys['secret']);
		$oauth->setTokens($code);
		
		$params = $oauth->getAccessToken(WgmGitHub_API::OAUTH_ACCESS_TOKEN_URL, array(
			'code' => $code,
			'redirect_uri' => $redirect_url,
			'client_id' => $app_keys['key'],
			'client_secret' => $app_keys['secret'],
			'state' => $state,
		));
		
		if(!is_array($params) || !isset($params['access_token'])) {
			return false;
		}
		
		$github = WgmGitHub_API::getInstance();
		$github->setToken($params['access_token']);
		
		// Load their profile
		
		$json = $github->get('user');
		
		// Die with error
		if(!is_array($json) || !isset($json['login']))
			return false;
		
		$params['login'] = $json['login'];
		
		// Output
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('form_id', $form_id);
		$tpl->assign('label', $params['login']);
		$tpl->assign('params_json', $encrypt->encrypt(json_encode($params)));
		$tpl->display('devblocks:cerberusweb.core::internal/connected_account/oauth_callback.tpl');
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, &$ch, &$verb, &$url, &$body, &$headers) {
		$credentials = $account->decryptParams();
		
		if(
			!isset($credentials['access_token'])
		)
			return false;
		
		// Add a bearer token
		$headers[] = sprintf('Authorization: token %s', $credentials['access_token']);
		
		return true;
	}
}
