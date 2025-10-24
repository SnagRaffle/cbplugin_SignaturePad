<?php
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;

$fieldObj = isset($fieldObj) ? $fieldObj : $field;
$userObj  = isset($userObj) ? $userObj : $user;
$valueStr = isset($valueStr) ? $valueStr : (isset($value) ? $value : '');

$params = isset($this->params) ? $this->params : null;
$penColor = $params ? $params->get('penColor', '#000000') : '#000000';
$penWidth = (int) ($params ? $params->get('penWidth', 2) : 2);
$canvasHeight = (int) ($params ? $params->get('canvasHeight', 200) : 200);
$bgColor = $params ? $params->get('bgColor', '#FFFFFF') : '#FFFFFF';

$buttonSaveText = $params ? $params->get('buttonSaveText', 'Accept &amp; Save') : 'Accept &amp; Save';
$buttonClearText = $params ? $params->get('buttonClearText', 'Clear') : 'Clear';
$buttonDeleteText = $params ? $params->get('buttonDeleteText', 'Delete') : 'Delete';

$base = Uri::base(true) . '/components/com_comprofiler/plugin/user/cbsignaturepad/';
?>
<div class="cb-sigpad" data-field="<?php echo htmlspecialchars($fieldObj->name, ENT_QUOTES); ?>"
     data-color="<?php echo htmlspecialchars($penColor, ENT_QUOTES); ?>"
     data-width="<?php echo (int) $penWidth; ?>"
     data-bg="<?php echo htmlspecialchars($bgColor, ENT_QUOTES); ?>"
     data-height="<?php echo (int) $canvasHeight; ?>">
  <div class="cb-sigpad-label"><?php echo htmlspecialchars($fieldObj->title); ?></div>
  <div class="cb-sigpad-canvaswrap">
    <canvas class="cb-sigpad-canvas" width="500" height="<?php echo (int) $canvasHeight; ?>"
      style="touch-action:none;width:100%;height:<?php echo (int) $canvasHeight; ?>px;display:block;background:#fff;border:1px solid #ccc;border-radius:6px;"></canvas>
  </div>
  <div class="cb-sigpad-actions" style="margin-top:8px;">
    <button type="button" class="cb-sigpad-clear"><?php echo $buttonClearText; ?></button>
    <button type="button" class="cb-sigpad-save"><?php echo $buttonSaveText; ?></button>
    <button type="button" class="cb-sigpad-delete"><?php echo $buttonDeleteText; ?></button>
    <a class="cb-sigpad-download" href="<?php echo htmlspecialchars('index.php?option=com_comprofiler&task=pluginclass&plugin=cbsignaturepad&func=stream&field=' . urlencode($fieldObj->name) . '&uid=' . (int) $userObj->id . '&no_html=1', ENT_QUOTES); ?>" download style="margin-left:8px;">Download</a>
    <span class="cb-sigpad-status" style="margin-left:10px;"></span>
  </div>
  <div class="cb-sigpad-preview" style="margin-top:10px;">
    <?php if (!empty($valueStr)) : ?>
      <img src="<?php echo htmlspecialchars('index.php?option=com_comprofiler&task=pluginclass&plugin=cbsignaturepad&func=stream&field=' . urlencode($fieldObj->name) . '&uid=' . (int) $userObj->id . '&no_html=1', ENT_QUOTES); ?>"
           alt="Signature" style="max-width:260px;border:1px solid #ddd;padding:4px;background:#fff;" />
    <?php endif; ?>
  </div>
  <input type="hidden" name="<?php echo htmlspecialchars($fieldObj->name, ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($valueStr, ENT_QUOTES); ?>" />
</div>
