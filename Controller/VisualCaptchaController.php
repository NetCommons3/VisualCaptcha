<?php
/**
 * VisualCaptcha Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Allcreator <info@allcreator.net>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppController', 'Controller');

/**
 * VisualCaptchaController
 *
 * @author Allcreator <info@allcreator.net>
 * @package NetCommons\VisualCaptcha\Controller
 */
class VisualCaptchaController extends VisualCaptchaAppController {

/**
 * use components
 *
 * @var array
 */
	public $components = array(
		'Pages.PageLayout',
		'VisualCaptcha.VisualCaptcha' => array(
			'operationType' => 'none',
		),
	);

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('captcha', 'captcha_image', 'captcha_audio');
	}

/**
 * view method
 * Display the VisualCaptcha auto redirect screen
 *
 * @return void
 */
	public function view() {
		if ($this->request->is('post')) {
			if ($this->VisualCaptcha->check()) {
				// リダイレクト先はセッションクリアか
				return $this->redirect($this->VisualCaptcha->getReturnUrl());
			}
		}
		$this->request->data['Frame'] = Current::read('Frame');
		$this->request->data['Block'] = Current::read('Block');
	}
/**
 * captcha
 * return to Client captcha data
 *
 * @return string
 */
	public function captcha() {
		$this->autoRender = false;

		// もしも表示数を変えたいときは引数に数値を設定
		echo $this->VisualCaptcha->generate();
	}

/**
 * captcha_image
 * return to Client captcha data
 *
 * @param int $index captcha image number
 * @return string
 */
	public function captcha_image($index) {
		$this->autoRender = false;
		return $this->VisualCaptcha->image($index);
	}

/**
 * captcha_audio
 * return to Client captcha audio data
 *
 * @return string
 */
	public function captcha_audio() {
		$this->autoRender = false;
		echo $this->VisualCaptcha->audio();
	}
}
