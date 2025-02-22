<?php

use Sunlight\Core;
use Sunlight\Message;
use Sunlight\Plugin\PluginArchive;
use Sunlight\Util\Form;
use Sunlight\Util\Html;
use Sunlight\Xsrf;

defined('_root') or exit;

$message = '';

if (isset($_FILES['archive']) && is_uploaded_file($_FILES['archive']['tmp_name'])) {
    try {
        $merge = isset($_POST['merge']);
        $archive = new PluginArchive(Core::$pluginManager, $_FILES['archive']['tmp_name']);

        if ($archive->hasPlugins()) {
            $extractedPlugins = $archive->extract($merge, $failedPlugins);

            if (!empty($extractedPlugins)) {
                $message .= Message::ok(Message::renderList(Html::escapeArrayItems($extractedPlugins), _lang('admin.plugins.upload.extracted')), true);

                Core::$pluginManager->purgeCache();
            }
            if (!empty($failedPlugins)) {
                $message .= Message::warning(Message::renderList(Html::escapeArrayItems($failedPlugins), _lang('admin.plugins.upload.failed' . (!$merge ? '.no_merge' : ''))), true);
            }
        } else {
            $message = Message::warning(_lang('admin.plugins.upload.no_plugins'));
        }
    } catch (Throwable $e) {
        $message = Message::error(_lang('global.error')) . Core::renderException($e);
    }
}


$output .= $message . '
<p class="bborder">' . _lang('admin.plugins.upload.p') . '</p>

<form method="post" enctype="multipart/form-data">
    <table>
        <tr>
            <th>' . _lang('admin.plugins.upload.file') . '</th>
            <td><input type="file" name="archive"></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input class="button" name="do_upload" type="submit" value="' . _lang('global.upload') . '">
                <label><input type="checkbox" value="1"' . Form::restoreCheckedAndName('do_upload', 'merge') . '> ' . _lang('admin.plugins.upload.skip_existing') . '</label>
            </td>
        </tr>
    </table>
' . Xsrf::getInput() . '</form>';
