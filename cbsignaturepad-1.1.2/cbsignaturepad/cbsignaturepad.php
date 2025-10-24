<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class cbsignaturepad
{
    public $params = null;

    public function getFieldTypes() { return array('signaturepad' => 'Signature Pad'); }

    public function drawField( $field, $user, $ui, $postdata, $reason, $list_compare_types )
    {
        if ( $reason == 'edit' ) {
            return $this->drawField_edit($field, $user, $ui, $postdata);
        } else {
            return $this->drawField_view($field, $user, $ui);
        }
    }

    public function drawField_edit( $field, $user, $ui, $postdata )
    {
        $value = isset($postdata[$field->name]) ? $postdata[$field->name] : $field->getValue($user, $ui);
        $doc = Factory::getApplication()->getDocument();
        $base = Uri::base(true) . '/components/com_comprofiler/plugin/user/cbsignaturepad/';
        $doc->addStyleSheet( $base . 'media/sigpad.css' );
        $doc->addScript( $base . 'media/sigpad.js', [], ['defer' => true] );
        $fieldObj = $field; $userObj = $user; $valueStr = $value;
        ob_start(); include __DIR__ . '/tmpl/edit.php'; return ob_get_clean();
    }

    public function drawField_view( $field, $user, $ui )
    {
        $value = $field->getValue($user, $ui);
        $fieldObj = $field; $userObj = $user; $valueStr = $value;
        ob_start(); include __DIR__ . '/tmpl/view.php'; return ob_get_clean();
    }

    public function save()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        header('Content-Type: application/json');
        if ($user->guest) { echo json_encode(['success'=>false,'message'=>'Not logged in']); $app->close(); }

        $in = json_decode(file_get_contents('php://input'), true) ?: array();
        $field = isset($in['field']) ? preg_replace('/[^a-zA-Z0-9_]/','', $in['field']) : '';
        $imgB64 = isset($in['image']) ? $in['image'] : '';

        if (!$field || !$imgB64) { echo json_encode(['success'=>false,'message'=>'Missing field or image']); $app->close(); }

        $decoded = base64_decode($imgB64, true);
        if ($decoded === false) { echo json_encode(['success'=>false,'message'=>'Invalid base64']); $app->close(); }

        $storage = $this->getStoragePath();
        if (!is_dir($storage)) { @mkdir($storage, 0750, true); }
        if (!is_dir($storage) || !is_writable($storage)) { echo json_encode(['success'=>false,'message'=>'Storage not writable']); $app->close(); }

        $userId = (int) $user->id;
        $filename = 'user' . $userId . '-' . $field . '.png';
        $path = rtrim($storage, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (@file_put_contents($path, $decoded) === false) {
            echo json_encode(['success'=>false,'message'=>'Write failed']); $app->close();
        }

        try {
            $db = Factory::getDbo();
            $q = $db->getQuery(true)
                ->update($db->quoteName('#__comprofiler'))
                ->set($db->quoteName($field) . ' = ' . $db->quote($filename))
                ->where($db->quoteName('id') . ' = ' . (int) $userId);
            $db->setQuery($q)->execute();
        } catch (\Throwable $e) {}

        echo json_encode(['success'=>true,'file'=>$filename]);
        $app->close();
    }

    public function delete()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        header('Content-Type: application/json');
        if ($user->guest) { echo json_encode(['success'=>false,'message'=>'Not logged in']); $app->close(); }

        $in = json_decode(file_get_contents('php://input'), true) ?: array();
        $field = isset($in['field']) ? preg_replace('/[^a-zA-Z0-9_]/','', $in['field']) : '';

        if (!$field) { echo json_encode(['success'=>false,'message'=>'Missing field']); $app->close(); }

        $storage = $this->getStoragePath();
        $userId = (int) $user->id;
        $filename = 'user' . $userId . '-' . $field . '.png';
        $path = rtrim($storage, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (is_file($path)) { @unlink($path); }

        try {
            $db = Factory::getDbo();
            $q = $db->getQuery(true)
                ->update($db->quoteName('#__comprofiler'))
                ->set($db->quoteName($field) . ' = ' . $db->quote(''))
                ->where($db->quoteName('id') . ' = ' . (int) $userId);
            $db->setQuery($q)->execute();
        } catch (\Throwable $e) {}

        echo json_encode(['success'=>true]);
        $app->close();
    }

    public function stream()
    {
        $app = Factory::getApplication();
        $viewer = $app->getIdentity();
        $field = preg_replace('/[^a-zA-Z0-9_]/','', $app->input->getCmd('field',''));
        $uid   = (int) $app->input->getInt('uid', 0);
        if (!$field) { header('HTTP/1.1 400 Bad Request'); echo 'Missing field'; $app->close(); }
        if ($viewer->guest) { header('HTTP/1.1 403 Forbidden'); echo 'Not logged in'; $app->close(); }

        $target = $uid ?: (int) $viewer->id;
        $allowed = ((int)$viewer->id === (int)$target) ? true : $this->userInAllowedGroups($viewer);
        if (!$allowed) { header('HTTP/1.1 403 Forbidden'); echo 'Not authorized'; $app->close(); }

        $storage = $this->getStoragePath();
        $filename = 'user' . $target . '-' . $field . '.png';
        $path = rtrim($storage, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($path)) { header('HTTP/1.1 404 Not Found'); echo 'No signature'; $app->close(); }
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        readfile($path);
        $app->close();
    }

    private function getStoragePath(): string
    {
        $configured = $this->getParam('storagePath', '');
        if ($configured) return $configured;
        return rtrim(JPATH_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'private_signatures';
    }

    private function userInAllowedGroups($user): bool
    {
        $csv = $this->getParam('allowedGroups', '8');
        $ids = array_filter(array_map('intval', explode(',', $csv)));
        if (!$ids) return false;
        $groups = (array) $user->getAuthorisedGroups();
        foreach ($groups as $g) { if (in_array((int)$g, $ids, true)) return true; }
        return false;
    }

    private function getParam($name, $default=null)
    {
        if (isset($this->params) && is_object($this->params) && method_exists($this->params,'get')) {
            return $this->params->get($name, $default);
        }
        return $default;
    }
}
