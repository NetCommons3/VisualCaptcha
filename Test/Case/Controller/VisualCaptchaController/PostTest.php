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
class VisualCaptchaControllerPostTest extends NetCommonsControllerTestCase {

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
 * POPUPタイプのPOS - OK パターン
 *
 * @return void
 */
	public function testPost() {
		$controller = $this->generate('VisualCaptcha.VisualCaptcha', array(
			'components' => array(
				//'Auth' => array('user'),
				'Session',
				'Security',
				'VisualCaptcha.VisualCaptcha'
			)
		));
		$controller->VisualCaptcha->expects($this->any())
			->method('check')
			->will(
				$this->returnValue(true));
		$controller->VisualCaptcha->expects($this->any())
			->method('getReturnUrl')
			->will(
				$this->returnValue('http://netcommons.org'));
		$data = array(
			'Frame' => array('id' => 6),
			'Block' => array('id' => 2),
			'testImage' => 'testImage'
		);

		$this->_testPostAction('post', $data,
			array(
				'plugin' => 'visual_captcha',
				'controller' => 'visual_captcha',
				'action' => 'view', 'block_id' => 2, 'frame_id' => 6));
		$result = $this->headers['Location'];

		$this->assertTextContains('netcommons', $result);
	}
/**
 * アクションのGETテスト
 * POPUPタイプのPOST - NG パターン
 *
 * @return void
 */
	public function testPostNG() {
		$data = array(
			'Frame' => array('id' => 6),
			'Block' => array('id' => 2),
			'testImage' => 'testImage'
		);

		$result = $this->_testPostAction('post', $data,
			array(
				'plugin' => 'visual_captcha',
				'controller' => 'visual_captcha',
				'action' => 'view', 'block_id' => 2, 'frame_id' => 6));
		$this->assertTextContains(
			__d('visual_captcha', 'No answer! Please try again.'),
			$result);
	}

}