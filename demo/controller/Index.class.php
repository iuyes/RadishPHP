<?php
include ('MyBaseController.class.php');
/**
 * Home controller class object.
 *
 * @author Lei Lee
 */
class IndexController extends MyBaseController implements IController {

	/**
	 * Execute initialization.
	 *
	 */
	function initialize() {
		parent::initialize();

		/*$this->scope->de('Imgur');

		$options = array(
			'authorize' => 'http://api.imgur.com/oauth/authorize',
			'request' => 'http://api.imgur.com/oauth/request_token',
			'access' => 'http://api.imgur.com/oauth/access_token',
			//'callback' => 'http://www.demo.com/',
			'success' => 'http://www.demo.com/?do=index&status=ok',
			'key' => 'Imgur.Api'
		);

		$api = Imgur::instance('f9776b1b3018cfbb73efb339bd2080fa04f3b9e23', '640a8b4e0e174105d89b922102d49868', $this->scope, $this->cache, $this->http);
		$api->setParameters($options);

		if ('ok' == $_GET['status']) {
			$dResult = $api->call('/account/images');
			$this->debug($dResult);
		} else {
			$api->authorize();
		}*/

	/*$this->de('Photobucket');
		$options = array(
			'authorize' => 'http://photobucket.com/apilogin/login',
			'request' => 'http://api.photobucket.com/login/request',
			'access' => 'http://api.photobucket.com/login/access',
			'callback' => 'http://www.demo.com/',
			'success' => 'http://www.demo.com/?do=index&status=ok',
			'key' => 'Photobucket.Api'
		);

		$api = Photobucket::instance('149832229', '69864cdcfd395c10d83f988fe472f29a', $this->cache, $this->http);
		$api->setParameters($options);
		if ('ok' == $_GET['status']) {
			$dResult = $api->call('/album/wuxuexuan', NULL, true, array(
				'name' => 'thenewalbum'
			));
			$this->debug($dResult);
		} else {
			$api->authorize();
		}*/
	}

	/**
	 * The default entry function.
	 *
	 */
	function index() {
		if ($this->isPost) {
			/*$files = $this->http->upload('file', SITE_ROOT . 'files', true, WebClient::FILE_UPLOAD_MULTIPLE);
			print_r($files); exit(0);*/
		} else {
			//$this->cache->setAdapterType(Cache::CACHE_ADAPTER_DB);
			//$this->cache->set('test1', array('Uid'=>1001,'UserName'=>'Lei Lee','Age'=>30));
			//$this->cache->delete('test1');
			//$this->thumb->crop(SITE_ROOT . '001.jpg', 0, 160, 160, 80, SITE_ROOT . '001-T-2.jpg');
			//echo 'haha';

			$this->assign('data', array(
				array('a' => 1, 'b' => 11, 'c' => array(array('cv' => 'c1-v'))), 
				array('a' => 2, 'b' => 22, 'c' => array(array('cv' => 'c2-v'))), 
				array('a' => 3, 'b' => 33, 'c' => array(array('cv' => 'c3-v'))), 
			));
			$this->assign('title', 'haha!');
			$this->display('tpl_test');
			exit(0);
			//$this->fso->deleteRecursive('E:\Developer\PHP\RadishPHP\demo\tmp');
		//$this->fso->decompress(SITE_ROOT . 'lilei_20110602_01_en.zip', SITE_ROOT, true);
		/*$this->thumb->load(SITE_ROOT . '21039349C5-1.jpg')
						->setWidth(180)
						->create(SITE_ROOT . 'demo.jpg');*/
		/*$this->load('ImgurApi');
			$api = new ImgurApi();
			$api->setHttp($this->http)
				->setKey('iooogle_imgur')
				->setUsername('wuxuexuan@msn.com')
				->setPassword('888888')
				->debug(true);
			$d = $api->send('http://www.china016.com/tu2/Remoteupfile/2011-5/6/2010101407550440.jpg');
			echo '<pre>';
			print_r($d);
			echo '</pre>';
			exit(0);

			$this->assign('name', 'Li Lei');

			$this->db->useDb('toolkit');

			$adapter = PageAdapter::instance();
			$adapter->setPageSize(1)->setOffset('offset')->setCurrentPageIndex(( int ) $this->data->gets('offset', 1))
					->setQueryCount("SELECT COUNT(*) FROM `bto_keywords`")
					->setQueryResult("SELECT * FROM `bto_keywords`");
			$this->db->exec($adapter);

			$this->assign('keywords', $adapter->getResult());
			$this->assign('keywords_stat', $adapter->toArray(2));*/

		// $this->display('index.tpl.php');
		}
	}

	function debug($data) {
		if (is_array($data)) {
			echo '<pre>', var_export($data, true), '</pre>';
		} else {
			echo $data;
		}
		exit(0);
	}
}