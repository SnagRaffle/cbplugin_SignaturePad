<?php
defined('_JEXEC') or die;

$fieldObj = isset($fieldObj) ? $fieldObj : $field;
$userObj  = isset($userObj) ? $userObj : $user;
$valueStr = isset($valueStr) ? $valueStr : (isset($value) ? $value : '');

$showInView = isset($this->params) ? (int) $this->params->get('showInView', 1) : 1;

if (!empty($valueStr) && $showInView === 1) {
  $src = 'index.php?option=com_comprofiler&task=pluginclass&plugin=cbsignaturepad&func=stream&field=' . urlencode($fieldObj->name) . '&uid=' . (int) $userObj->id . '&no_html=1';
  echo '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="Signature" style="max-width:260px;border:1px solid #ddd;padding:4px;background:#fff;" />';
}
