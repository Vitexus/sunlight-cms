<?php

use Sunlight\Database\Database as DB;
use Sunlight\Message;
use Sunlight\Page\PageManager;
use Sunlight\Page\PageManipulator;
use Sunlight\User;
use Sunlight\Util\Request;
use Sunlight\Xsrf;

defined('_root') or exit;

$type_array = PageManager::getTypes();

/* ---  priprava promennych  --- */

$continue = false;
if (isset($_GET['id'])) {
    $id = (int) Request::get('id');
    $query = DB::queryRow("SELECT id,node_level,node_depth,node_parent,title,type,type_idt,ord FROM " . _page_table . " WHERE id=" . $id);
    if ($query !== false && User::hasPrivilege('admin' . $type_array[$query['type']])) {
        $continue = true;
    }
}

if ($continue) {

    // opravneni k mazani podstranek = pravo na vsechny typy
    $recursive = true;
    foreach (PageManager::getTypes() as $type) {
        if (!User::hasPrivilege('admin' . $type)) {
            $recursive = false;
            break;
        }
    }

    /* ---  odstraneni  --- */
    if (isset($_POST['confirm'])) {

        // smazani
        $error = null;
        if (!PageManipulator::delete($query, $recursive, $error)) {
            // selhani
            $output .= Message::error($error);

            return;
        }

        // redirect
        $admin_redirect_to = 'index.php?p=content&done';

        return;

    }

    /* ---  vystup  --- */

    // pole souvisejicich polozek
    $content_array = PageManipulator::listDependencies($query, $recursive);

    $output .= "
    <p class='bborder'>" . _lang('admin.content.delete.p') . "</p>
    <h2>" . _lang('global.item') . " <em>" . $query['title'] . "</em></h2><br>
    " . (!empty($content_array) ? "<p>" . _lang('admin.content.delete.contentlist') . ":</p>" . Message::renderList($content_array) . "<div class='hr'><hr></div>" : '') . "

    <form class='cform' action='index.php?p=content-delete&amp;id=" . $id . "' method='post'>
    <input type='hidden' name='confirm' value='1'>
    <input type='submit' value='" . _lang('admin.content.delete.confirm') . "'>
    " . Xsrf::getInput() . "</form>
    ";

} else {
    $output .= Message::error(_lang('global.badinput'));
}
