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
 * 画像認証プラグイン 処理イメージ
 * デフォルトは埋め込み方式とする
 * このプラグインの振る舞いを変更したい場合は。ControllerでComponentを組み込むときに配列引数を与えて設定するか
 * ControllerのbeforeFilterでこの属性値を変更することで行える
 *
 * @var string
 */
    public $operationType = VisualCaptchaComponent::OPERATION_REDIRECT;

/**
 * call controller w/ associations
 *
 * @var object
 */
    public $controller = null;

/**
 * visual captcha redirect target controller
 *
 * @var string
 */
    public $targetController = null;

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
 * 認証後戻るURL
 * 切り替え型の時しか使わない
 * 切り替え型で、画像認証成功時戻る先のURL
 *
 * @var array
 */
    public $returnUrl = array();


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

        // 記録されている正解データを保持
        $this->imageField = $this->controller->Session->read('visualcaptcha.frontendData.imageFieldName');
        $this->audioField = $this->controller->Session->read('visualcaptcha.frontendData.audioFieldName');

        // セキュリティコンポーネントを使用されている場合は
        // 画像認証フィールドをUnlockにしておく
        if (array_key_exists('Security', $this->controller->components)) {
            if ($this->imageField && $this->audioField) {
                $this->controller->Security->unlockedFields = array(
                    $this->imageField,
                    $this->audioField
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

        // 戻り先URL準備
        $this->returnUrl = array(
            'plugin' => Inflector::underscore(Current::read('Plugin.key')),
            'controller' => Inflector::underscore($this->controller->name),
            'action' => Inflector::underscore($this->controller->action)
        ) + $this->controller->request->query;

        // リダイレクトURL準備
        $this->visualCaptchaAction['frame_id'] = Current::read('Frame.id');
        // リファラが自分自身でないことが必須（無限ループになる
        if ($this->operationType == VisualCaptchaComponent::OPERATION_REDIRECT
            && $controller->referer('', true) != NetCommonsUrl::actionUrl($this->visualCaptchaAction)
            && $controller->name == $this->targetController
            && $controller->action == $this->targetAction) {
            // 切り替え後、認証成功時のURLを取り出す
            $returnUrl = $controller->here;
            $controller->Session->write('VisualCaptcha.returnUrl', $returnUrl . '?' . http_build_query($this->controller->request->query));
            $controller->redirect(NetCommonsUrl::actionUrl($this->visualCaptchaAction));
        }
        return;
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
        $session = new Session();
        $captcha = new Captcha($session, $this->assetPath);

        $ret = false;
        $errorMessage = '';
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
        $session = new Session();
        $lang = Configure::read('Config.language');
        $imageJsonPath = $this->assetPath . DS . $lang . DS . 'images.json';
        $audioJsonPath = $this->assetPath . DS . $lang . DS . 'audios.json';

        $captcha = new Captcha($session, $this->assetPath, $this->__utilReadJSON($imageJsonPath), $this->__utilReadJSON($audioJsonPath));
        $captcha->generate($count);
        return json_encode($captcha->getFrontEndData());
    }

/**
 * generate visual captcha image data and return it
 *
 * @param int $index display image index
 * @return string
 */
    public function image($index) {
        $session = new Session();
        $captcha = new Captcha($session, $this->assetPath);

        return $captcha->streamImage(array(), $index, 0);
    }

/**
 * generate audio captcha data and return it
 *
 * @return streaming data
 */
    public function audio() {
        $session = new Session();
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