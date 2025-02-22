<?php

use Sunlight\Database\Database as DB;
use Sunlight\Message;
use Sunlight\Comment\Comment;
use Sunlight\Router;
use Sunlight\Template;
use Sunlight\User;
use Sunlight\Util\Request;
use Sunlight\Xsrf;

defined('_root') or exit;

if (!_logged_in) {
    $_index['type'] = _index_unauthorized;
    return;
}

/* ---  priprava promennych  --- */

$success = false;
$message = '';
$unstick = '';
$id = (int) Request::get('id');
$userQuery = User::createQuery('p.author');
$query = DB::queryRow("SELECT p.id,p.time,p.subject,p.sticky,r.slug forum_slug,r.layout forum_layout," . $userQuery['column_list'] . " FROM " . _comment_table . " p JOIN " . _page_table . " r ON(p.home=r.id) " . $userQuery['joins'] . " WHERE p.id=" . $id . " AND p.type=" . _post_forum_topic . " AND p.xhome=-1");
if ($query !== false) {
    if (isset($query['forum_layout'])) {
        Template::change($query['forum_layout']);
    }

    $_index['backlink'] = Router::topic($query['id'], $query['forum_slug']);
    if ($query['sticky']) {
        $unstick = '2';
    }
    if (!Comment::checkAccess($userQuery, $query) || !_priv_stickytopics) {
        $_index['type'] = _index_unauthorized;
        return;
    }
} else {
    $_index['type'] = _index_not_found;
    return;
}

/* ---  ulozeni  --- */

if (isset($_POST['doit'])) {
    DB::update(_comment_table, 'id=' . DB::val($id), ['sticky' => (($query['sticky'] == 1) ? 0 : 1)]);
    $message = Message::ok(_lang('mod.stickytopic.ok' . $unstick));
    $success = true;
}

/* ---  vystup  --- */

$_index['title'] = _lang('mod.stickytopic' . $unstick);

// zprava
$output .= $message;

// formular
if (!$success) {
    $output .= '
    <form action="' . _e(Router::module('stickytopic', 'id=' . $id)) . '" method="post">
    ' . Message::warning(_lang('mod.stickytopic.text' . $unstick, ['%topic%' => $query['subject']]), true) . '
    <input type="submit" name="doit" value="' . _lang('mod.stickytopic.submit' . $unstick) . '">
    ' . Xsrf::getInput() . '</form>
    ';
}
