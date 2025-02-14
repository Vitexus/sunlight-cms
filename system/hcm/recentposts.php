<?php

use Sunlight\Comment\CommentService;
use Sunlight\Database\Database as DB;
use Sunlight\GenericTemplates;
use Sunlight\Comment\Comment;
use Sunlight\Router;
use Sunlight\User;
use Sunlight\Util\Arr;
use Sunlight\Util\StringManipulator;

return function ($limit = null, $stranky = "", $typ = null) {
    // priprava
    $result = "";
    if (isset($limit) && (int) $limit >= 1) {
        $limit = abs((int) $limit);
    } else {
        $limit = 10;
    }
    $post_types =  [
        'section' => _post_section_comment,
        'article' => _post_article_comment,
        'book' => _post_book_entry,
        'topic' => _post_forum_topic,
        'plugin' => _post_plugin,
    ];

    // nastaveni filtru
    if (!empty($stranky)) {
        $homes = Arr::removeValue(explode('-', $stranky), '');
    } else {
        $homes = [];
    }

    if (!empty($typ)) {
        if (isset($post_types[$typ])) {
            $typ = $post_types[$typ];
        } elseif (!in_array($typ, $post_types)) {
            $typ = _post_section_comment;
        }
        $types = [$typ];
    } else {
        $types = $post_types;
    }

    // dotaz
    [$columns, $joins, $cond] = Comment::createFilter('post', $types, $homes);
    $userQuery = User::createQuery('post.author');
    $columns .= ',' . $userQuery['column_list'];
    $joins .= ' ' . $userQuery['joins'];
    $query = DB::query("SELECT " . $columns . " FROM " . _comment_table . " post " . $joins . " WHERE " . $cond . " ORDER BY id DESC LIMIT " . $limit);

    while ($item = DB::row($query)) {
        [$homelink, $hometitle] = Router::post($item);

        if ($item['author'] != -1) {
            $authorname = Router::userFromQuery($userQuery, $item);
        } else {
            $authorname = CommentService::renderGuestName($item['guest']);
        }

        $result .= "
<div class='list-item'>
<h2 class='list-title'><a href='" . _e($homelink) . "'>" . $hometitle . "</a></h2>
<p class='list-perex'>" . StringManipulator::ellipsis(strip_tags(Comment::render($item['text'])), 255) . "</p>
" . GenericTemplates::renderInfos([
    [_lang('global.postauthor'), $authorname],
    [_lang('global.time'), GenericTemplates::renderTime($item['time'], 'post')],
]) . "</div>\n";
    }

    return $result;
};
