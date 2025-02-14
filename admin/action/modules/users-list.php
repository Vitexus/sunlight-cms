<?php

use Sunlight\Admin\Admin;
use Sunlight\Database\Database as DB;
use Sunlight\Message;
use Sunlight\Paginator;
use Sunlight\Router;
use Sunlight\User;
use Sunlight\Util\Form;
use Sunlight\Util\Request;
use Sunlight\Xsrf;

defined('_root') or exit;

$message = '';

/* --- hromadne akce --- */

if (isset($_POST['bulk_action'])) {
    switch (Request::post('bulk_action')) {
        // smazani
        case 'del':
            $user_ids = (array) Request::post('user', [], true);
            $user_delete_counter = 0;
            foreach ($user_ids as $user_id) {
                $user_id = (int) $user_id;
                if ($user_id !== _super_admin_id && $user_id != _user_id && User::delete($user_id)) {
                    ++$user_delete_counter;
                }
            }

            $message = Message::render(
                $user_delete_counter === count($user_ids) ? Message::OK : Message::WARNING,
                str_replace(
                    ['%done%', '%total%'],
                    [$user_delete_counter, count($user_ids)],
                    _lang('admin.users.list.bulkdelete.msg')
                )
            );
            break;
    }
}

/* ---  vystup  --- */

// filtr skupiny
$grouplimit = "";
$list_conds = [];
if (isset($_GET['group_id'])) {
    $group = (int) Request::get('group_id');
    if ($group != -1) {
        $list_conds[] = 'u.group_id=' . $group;
    }
} else {
    $group = -1;
}

// aktivace vyhledavani
$search = trim(Request::get('search'));
if ($search !== '') {
    $wildcard = DB::val('%' . $search . '%');
    $list_conds[] = "(u.id=" . DB::val($search) . " OR u.username LIKE {$wildcard} OR u.publicname LIKE {$wildcard} OR u.email LIKE {$wildcard} OR u.ip LIKE {$wildcard})";
} else {
    $search = false;
}

// priprava podminek vypisu
$list_conds_sql = empty($list_conds) ? '1' : implode(' AND ', $list_conds);

// filtry - vyber skupiny, vyhledavani
$output .= '
<table class="two-columns">
<tr>

<td>
<form class="cform" action="index.php" method="get">
<input type="hidden" name="p" value="users-list">
<input type="hidden" name="search"' . Form::restoreGetValue('search', '') . '>
<strong>' . _lang('admin.users.list.groupfilter') . ':</strong> ' . Admin::userSelect("group_id", $group, "id!=" . _group_guests, null, _lang('global.all'), true) . '
<input class="button" type="submit" value="' . _lang('global.apply') . '">
</form>
</td>

<td>
<form class="cform" action="index.php" method="get">
<input type="hidden" name="p" value="users-list">
<input type="hidden" name="group_id" value="' . $group . '">
<strong>' . _lang('admin.users.list.search') . ':</strong> <input type="text" name="search" class="inputsmall"' . Form::restoreGetValue('search') . '> <input class="button" type="submit" value="' . _lang('mod.search.submit') . '">
' . ($search ? ' <a href="index.php?p=users-list&amp;group=' . $group . '">' . _lang('global.cancel') . '</a>' : '') . '
</form>
</td>

</tr>
</table>
';

// priprava strankovani
$paging = Paginator::render("index.php?p=users-list&group=" . $group . ($search !== false ? '&search=' . rawurlencode($search) : ''), 50, _user_table . ':u', $list_conds_sql);
$output .= $paging['paging'];

// tabulka
$output .= $message . "
<form method='post'>
<table id='user-list' class='list list-hover list-max'>
<thead><tr>
    <td><input type='checkbox' onclick='Sunlight.checkAll(event, this.checked, \"#user-list\")'></td>
    <td>ID</td><td>" . _lang('login.username') . "</td>
    <td>" . _lang('global.email') . "</td>
    <td>" . _lang('mod.settings.publicname') . "</td>
    <td>" . _lang('global.group') . "</td>
    <td>" . _lang('global.action') . "</td>
</tr></thead>
<tbody>
";

// dotaz na db
$userQuery = User::createQuery();
$query = DB::query('SELECT ' . $userQuery['column_list'] . ',u.email user_email FROM ' . _user_table . ' u ' . $userQuery['joins'] . ' WHERE ' . $list_conds_sql . ' ORDER BY ug.level DESC ' . $paging['sql_limit']);

// vypis
if (DB::size($query) != 0) {
    while ($item = DB::row($query)) {
        $output .= "<tr>
            <td><input type='checkbox' name='user[]' value='" . $item['user_id'] . "'></td>
            <td>" . $item['user_id'] . "</td>
            <td>" . Router::userFromQuery($userQuery, $item, ['new_window' => true, 'publicname' => false]) . "</td>
            <td>" . $item['user_email'] . "</td><td>" . (($item['user_publicname'] != '') ? $item['user_publicname'] : "-") . "</td>
            <td>" . $item['user_group_title'] . "</td>
            <td class='actions'>
                <a class='button' href='index.php?p=users-edit&amp;id=" . $item['user_username'] . "'><img src='images/icons/edit.png' alt='edit' class='icon'>" . _lang('global.edit') . "</a>
                <a class='button' href='index.php?p=users-delete&amp;id=" . $item['user_username'] . "'><img src='images/icons/delete.png' alt='del' class='icon'>" . _lang('global.delete') . "</a>
            </td>
        </tr>\n";
    }
} else {
    $output .= "<tr><td colspan='5'>" . _lang('global.nokit') . "</td></tr>\n";
}

$output .= "</tbody></table>\n";

// pocet uzivatelu
$totalusers = DB::count(_user_table);
$output .= '<p class="right">' . _lang('admin.users.list.totalusers') . ": " . $totalusers . '</p>';

// hromadna akce
$output .= "
    <p class='left'>
        " . _lang('global.bulk') . ":
        <select name='bulk_action'>
            <option value=''></option>
            <option value='del'>" . _lang('global.delete') . "</option>
        </select>
        <input class='button' type='submit' onclick='return Sunlight.confirm()' value='" . _lang('global.do') . "'>
    </p>

" . Xsrf::getInput() . "</form>";

// strankovani
$output .= $paging['paging'];
