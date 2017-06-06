<?php
/**
 * VisualCaptcha Component
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author AllCreator <info@allcreator.net>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * 名前空間の定義
 * VisualCaptchaの中でSessionクラスを定義している
 */
use visualCaptcha\Captcha;
use visualCaptcha\Session;

App::uses('Component', 'Controller');

App::import('Vendor', 'Captcha', array(
	'file' => 'emotionloop' . DS . 'visualcaptcha' . DS . 'src' . DS . 'visualCaptcha' . DS . 'Captcha'
));
App::import('Vendor', 'Session', array(
	'file' => 'emotionloop' . DS . 'visualcaptcha' . DS . 'src' . DS . 'visualCaptcha' . DS . 'Session'
));

/**
 * VisualCaptcha Component
 *
 * 画像認証画面へのリダイレクト、認証処理を行います。<br>
 * 利用方式、対象アクション、認証要素key名称を指定してください。
 *
 * [利用方式](#operationtype)<br>
 * [対象アクション](#operationtype)
 *
 *
 *
 * @author AllCreator <info@allcreator.net>
 * @package NetCommons\VisualCaptcha\Controller\Component
 */
class VisualCaptchaComponent extends Component {

/**
 * captcha operation type
 *
 * @var string
 */
	const OPERATION_REDIRECT = 'redirect';
	const OPERATION_EMBEDDING = 'embedding';
	const OPERATION_NONE = 'none';

/**
 * 利用方式
 *
 * * OPERATION_REDIRECT<br>
 * 切り替わり方式<br>
 * 認証が必要な画面を表示する前に、画像認証画面が自動的に表示される方式です。<br>
 * 画像認証に成功した後、認証が必要な画面を表示します。<br>
 * この場合、画像認証画面、認証Postを当プラグインが処理するため、、
 * 利用プラグインは、VisualCaptchaComponentを設定するのみです。<br>
 * 対象アクション名も指定してください。
 *
 * #### サンプルコード
 * ```
 * public $components = array(
 * 	'VisualCaptcha.VisualCaptcha' => array(
 * 		'operationType' => VisualCaptchaComponent::OPERATION_REDIRECT,
 * 		'targetAction' => 'answer',
 * 		'identifyKey' => 'Questionnaire'？？？
 * 	)
 * )
 * ```
 *
 * * OPERATION_EMBEDDING<br>
 * 埋め込み方式(デフォルト)<br>
 * 認証が必要な画面に、画像認証パーツを埋め込む方式です。<br>
 * 切り替わり方式だと画像認証画面だけが表示されることになるが、埋め込み方式は認証が必要な画面の任意の場所に埋め込めます。<br>
 * この場合は、VisualCaptchaComponentを設定、viewファイルへのvisual_captcha.ctpの埋め込み、
 * 正しい回答がされたかのチェックを行う必要があります。<br>
 *
 *
 * #### サンプルコード
 * ##### Controller
 * ```
 * public $components = array(
 * 	'VisualCaptcha.VisualCaptcha' => array(
 * 		'operationType' => VisualCaptchaComponent::OPERATION_EMBEDDING,
 * 		'identifyKey' => 'VisualCaptcha'
 * 	)
 * )
 * ```
 * ##### View
 * ```
 * <?php
 * 	echo $this->element(
 * 		'VisualCaptcha.visual_captcha', array(
 * 			'identifyKey' => 'VisualCaptcha'
 * 		)
 * ); ?>
 * ```
 *
 * @var string
 */
	public $operationType = VisualCaptchaComponent::OPERATION_EMBEDDING;

/**
 * call controller w/ associations
 *
 * @var object
 */
	public $controller = null;

/**
 * visual captcha redirect target controller action
 *
 * @var string
 */
	public $targetAction = null;

/**
 * assetPath /r associations
 *
 * @var string
 */
	public $assetPath = null;

/**
 * imageField Answer /r associations
 *
 * @var string
 */
	public $imageField = null;

/**
 * audioField Answer /r associations
 *
 * @var string
 */
	public $audioField = null;

/**
 * 切り替えタイプのときの切り替え先画面のURLデフォルト値
 * デフォルトの画像認証画面では困る場合はこの構造データを変更してください
 *
 * @var array
 */
	public $visualCaptchaAction = array(
		'plugin' => 'visual_captcha',
		'controller' => 'visual_captcha',
		'action' => 'view',
	);

/**
 * Called before the Controller::beforeFilter().
 *
 * @param Controller $controller Controller with components to initialize
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::initialize
 */
	public function initialize(Controller $controller) {
		$this->controller = $controller;

		// 画像認証リソースファイルへのパス
		$this->assetPath = App::pluginPath('VisualCaptcha') . 'Resource' . DS . 'visual_captcha';

		// フレームID別に記録されている正解データを保持
		$frameId = Hash::get($this->controller->request->query, 'frame_id');
		$session = new Session('visual_captcha_' . $frameId);
		$frontendData = $session->get('frontendData');
		$this->imageField = $frontendData['imageFieldName'];
		$this->audioField = $frontendData['audioFieldName'];
		// セキュリティコンポーネントを使用されている場合は
		// 画像認証フィールドをUnlockにしておく
		if (array_key_exists('Security', $this->controller->components)) {
			if ($this->imageField && $this->audioField) {
				$this->controller->Security->unlockedFields = array(
					$this->imageField,
					$this->audioField,
					'frame_id'
				);
			}
		}
	}

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param Controller $controller Controller with components to startup
 * @return void
 * @throws ForbiddenException
 */
	public function startup(Controller $controller) {
		// 何もしないでと指示されている場合
		if ($this->operationType == VisualCaptchaComponent::OPERATION_NONE) {
			return;
		}

		// 埋め込み型の時
		if ($this->operationType == VisualCaptchaComponent::OPERATION_EMBEDDING) {
			// 埋め込み型のときは判断・処理は利用側のプラグインに移譲する
			return;
		}

		// 切り替え型のとき
		// リダイレクトURL準備
		$this->visualCaptchaAction['frame_id'] = Current::read('Frame.id');
		$this->visualCaptchaAction['block_id'] = Current::read('Block.id');
		// リファラが自分自身でないことが必須（無限ループになる
		if ($this->operationType == VisualCaptchaComponent::OPERATION_REDIRECT) {
			if ($controller->action == $this->targetAction) {
				// OK判定が出ているか出てないならばリダイレクト
				if (! $controller->Session->check('VisualCaptcha.judgement')) {
					// 切り替え後、認証成功時のURLを取り出す
					$returnUrl = $controller->request->here(false);
					$controller->Session->write('VisualCaptcha.returnUrl', $returnUrl);
					$controller->redirect(NetCommonsUrl::actionUrl($this->visualCaptchaAction));
				} else {
					// 出ているときはリダイレクトない
					// そのままガード外して目的の画面へ行かせるので、ここでOK判定を消しておく
					$controller->Session->delete('VisualCaptcha.judgement');
				}
			}
		}
	}
/**
 * getReturnUrl get return screen url
 *
 * @return string
 */
	public function getReturnUrl() {
		return $this->controller->Session->read('VisualCaptcha.returnUrl');
	}
/**
 * check input response
 *
 * @return bool
 */
	public function check() {
		$reqData = $this->controller->request->data;
		$session = new Session('visual_captcha_' . (Current::read('Frame.id')));
		$captcha = new Captcha($session, $this->assetPath);

		$ret = false;
		$errorMessage = __d('visual_captcha', 'No answer! Please try again.');
		if (isset($reqData[$this->imageField])) {
			$ret = $captcha->validateImage($reqData[$this->imageField]);
			$errorMessage = __d('visual_captcha', 'Image was NOT valid! Please try again.');
		} elseif (isset($reqData[$this->audioField])) {
			$ret = $captcha->validateAudio($reqData[$this->audioField]);
			$errorMessage = __d('visual_captcha', 'Accessibility answer was NOT valid! Please try again.');
		}
		if ($ret === false) {
			//$this->controller->NetCommons->setFlashNotification($errorMessage);
			$this->controller->set('visualCaptchaErrorMessage', $errorMessage);
		} else {
			// 判定セッション情報はリダイレクトの処理専用
			if ($this->controller->name == 'VisualCaptcha') {
				$this->controller->Session->write('VisualCaptcha.judgement', 'OK');
			}
		}
		return $ret;
	}
/**
 * generate visual captcha data and return it
 *
 * @param int $count display image count
 * @return string
 */
	public function generate($count = 5) {
		$session = new Session('visual_captcha_' . (Current::read('Frame.id')));
		$lang = Configure::read('Config.language');
		$imageJsonPath = $this->assetPath . DS . $lang . DS . 'images.json';
		$audioJsonPath = $this->assetPath . DS . $lang . DS . 'audios.json';

		$imageJson = $this->__utilReadJSON($imageJsonPath);
		$audioJson = $this->__utilReadJSON($audioJsonPath);

		if (! $imageJson || ! $audioJson) {
			return '';
		}

		$captcha = new Captcha(
			$session,
			$this->assetPath,
			$this->__utilReadJSON($imageJsonPath),
			$this->__utilReadJSON($audioJsonPath)
		);
		$captcha->generate($count);
		$ret = $captcha->getFrontEndData();
		return json_encode($ret);
	}

/**
 * generate visual captcha image data and return it
 *
 * @param int $index display image index
 * @return string
 */
	public function image($index) {
		$session = new Session('visual_captcha_' . (Current::read('Frame.id')));
		$captcha = new Captcha($session, $this->assetPath);

		return $captcha->streamImage(array(), $index, 0);
	}

/**
 * generate audio captcha data and return it
 *
 * @return streaming data
 */
	public function audio() {
		$session = new Session('visual_captcha_' . (Current::read('Frame.id')));
		$captcha = new Captcha($session, $this->assetPath);
		return $captcha->streamAudio(array(), 'mp3');
	}
/**
 * Read input file as JSON
 *
 * @param string $filePath json file path
 * @return object
 */
	private function __utilReadJSON($filePath) {
		if (!file_exists($filePath)) {
			return null;
		}
		return json_decode(file_get_contents($filePath), true);
	}
}
