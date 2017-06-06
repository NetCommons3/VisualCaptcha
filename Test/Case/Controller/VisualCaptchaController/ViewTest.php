<?php
/**
 * VisualCaptchaController Test Case
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author AllCreator <info@allcreator.net>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * VisualCaptchaController Test Case
 *
 * @author AllCreator <info@allcreator.net>
 * @package NetCommons\VisualCaptcha\Test\Case\Controller
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class VisualCaptchaControllerViewTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
	);

/**
 * Plugin name
 *
 * @var array
 */
	public $plugin = 'visual_captcha';

/**
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'visual_captcha';

/**
 * アクションのGETテスト
 *
 * @param array $urlOptions URLオプション
 * @param array $assert テストの期待値
 * @param string|null $exception Exception
 * @param string $return testActionの実行後の結果
 * @dataProvider dataProviderGet
 * @return void
 */
	public function testViewGet($urlOptions, $assert, $exception = null, $return = 'view') {
		//テスト実施
		$url = Hash::merge(array(
			'plugin' => $this->plugin,
			'controller' => $this->_controller,
			'action' => 'view',
			'frame_id' => 6,
			'block_id' => 2,
		), $urlOptions);

		$this->_testGetAction($url, $assert, $exception, $return);
	}

/**
 * アクションのGETテスト用DataProvider
 *
 * ### 戻り値
 *  - urlOptions: URLオプション
 *  - assert: テストの期待値
 *  - exception: Exception
 *  - return: testActionの実行後の結果
 *
 * @return array
 */
	public function dataProviderGet() {
		$results = array();
		$results[0] = array(
			'urlOptions' => array(),
			'assert' => array('method' => 'assertTextContains', 'expected' => 'visual_captcha/visual_captcha/captcha_image'),
		);
		return $results;
	}

/**
 * testCaptcha
 *
 * captchaのGETテスト
 *
 * @return void
 */
	public function testCaptcha() {
		$url = array(
			'plugin' => 'visual_captcha',
			'controller' => 'visual_captcha',
			'action' => 'captcha',
			'block_id' => 2,
			'frame_id' => 6
		);
		ob_start();
		$this->_testNcAction($url, array('method' => 'get'), null, 'view');
		$actual = ob_get_clean();
		$this->assertTextContains('imageFieldName', $actual);
	}

/**
 * captcha_audioのGETテスト
 *
 * @return void
 */
	public function testCaptchaAudio() {
		ob_start();
		// generateさせてからでないと
		$url = array(
			'plugin' => 'visual_captcha',
			'controller' => 'visual_captcha',
			'action' => 'captcha',
			'block_id' => 2,
			'frame_id' => 6
		);
		$this->_testNcAction($url, array('method' => 'get'), null, 'view');
		// 認証用の音声データは取り出せません
		$url = array(
			'plugin' => 'visual_captcha',
			'controller' => 'visual_captcha',
			'action' => 'captcha_audio',
			'block_id' => 2,
			'frame_id' => 6
		);
		$this->_testNcAction($url, array('method' => 'get'), null, 'view');
		$actual = ob_get_clean();
		// FUJI Streaming応答のときのアサーション方法がわからない
		$this->assertNotEmpty($actual);
	}

/**
 * captcha_imageのGETテスト
 *
 * @return void
 */
	public function testCaptchaImage() {
		ob_start();
		// generateさせてからでないと
		$url = array(
			'plugin' => 'visual_captcha',
			'controller' => 'visual_captcha',
			'action' => 'captcha',
			'block_id' => 2,
			'frame_id' => 6
		);
		$this->_testNcAction($url, array('method' => 'get'), null, 'view');
		// 認証用の画像データは取り出せません
		$url = array(
			'plugin' => 'visual_captcha',
			'controller' => 'visual_captcha',
			'action' => 'captcha_image',
			'block_id' => 2,
			'frame_id' => 6
		);
		$this->_testNcAction($url, array('method' => 'get'), null, 'view');
		$actual = ob_get_clean();
		//$result = $this->_testNcAction($url, array('method' => 'get'), null, 'view');
		// FUJI 画像ファイルのダウンロード応答のときのアサーション方法がわからない
		$this->assertNotEmpty($actual);
	}
}