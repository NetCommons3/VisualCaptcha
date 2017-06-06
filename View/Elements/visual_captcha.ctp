<?php
/**
 * Element of common javascript
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

	echo $this->Html->script(
		array('/components/visualcaptcha.jquery/visualcaptcha.jquery.js'),
		array(
			'plugin' => false,
			'inline' => false
	));
	echo $this->Html->css(
		'/components/visualcaptcha.jquery/visualcaptcha.css',
		array(
			'plugin' => false,
			'inline' => false
	));
	$frameId = Current::read('Frame.id');
	if (! isset($identifyKey)) {
		$identifyKey = 'VisualCaptcha';
	}
	$elementId = $identifyKey . '-' . $frameId;

	$basePath = 'visual_captcha/visual_captcha/';
	$imagePath = $basePath . 'captcha_image';
	$audioPath = $basePath . 'captcha_audio';
	$startPath = $basePath . 'captcha';
?>

<div id="<?php echo $elementId; ?>"></div>
<?php if (isset($visualCaptchaErrorMessage)): ?>
	<div class="has-error">
		<div class="help-block">
			<?php echo $visualCaptchaErrorMessage ?>
		</div>
	</div>
<?php endif ?>

<?php $imageDisplayCount = empty($imageDisplayCount) ? 5 : $imageDisplayCount; ?>

<script>
	$(document).ready(function() {
		var el = $('#<?php echo $elementId; ?>').visualCaptcha({
			imgPath: '<?php echo $this->webroot; ?>components/visualcaptcha.jquery/img/',
			captcha: {
				numberOfImages: <?php echo $imageDisplayCount; ?>,
				url: '<?php echo $this->webroot; ?>',
				namespaceFieldName: 'frame_id',
				namespace: '<?php echo Current::read('Frame.id'); ?>',
				routes: {
					image : '<?php echo $imagePath; ?>',
					audio : '<?php echo $audioPath; ?>',
					start : '<?php echo $startPath; ?>'
				}
			},
			language: {
				accessibilityAlt: "<?php echo __d('visual_captcha', 'Sound icon'); ?>",
				accessibilityTitle: "<?php echo __d('visual_captcha', 'Accessibility option: listen to a question and answer it!'); ?>",
				accessibilityDescription: "<?php echo __d('visual_captcha', 'Type below the <strong>answer</strong> to what you hear. Numbers or words:'); ?>",
				explanation: "<?php echo __d('visual_captcha', 'Click or touch the <strong>ANSWER</strong>'); ?>",
				refreshAlt: "<?php echo __d('visual_captcha', 'Refresh/reload icon'); ?>",
				refreshTitle: "<?php echo __d('visual_captcha', 'Refresh/reload: get new images and accessibility option!'); ?>",
			}
		});
		var captcha = el.data('captcha');
	});
</script>