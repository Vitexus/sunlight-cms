<?php

use Sunlight\Admin\Admin;
use Sunlight\Article;
use Sunlight\Database\Database as DB;
use Sunlight\Extend;
use Sunlight\GenericTemplates;
use Sunlight\Message;
use Sunlight\Router;
use Sunlight\User;
use Sunlight\Util\Form;
use Sunlight\Util\Html;
use Sunlight\Util\Request;
use Sunlight\Util\StringManipulator;
use Sunlight\Xsrf;

defined('_root') or exit;

/* ---  nacteni promennych  --- */

$message = "";
$continue = false;
if (isset($_GET['id'], $_GET['returnid'], $_GET['returnpage'])) {
    $id = (int) Request::get('id');
    $returnid = Request::get('returnid');
    if ($returnid != "load") {
        $returnid = (int) $returnid;
    }
    $returnpage = (int) Request::get('returnpage');
    $query = DB::queryRow("SELECT art.*,cat.slug AS cat_slug FROM " . _article_table . " AS art JOIN " . _page_table . " AS cat ON(cat.id=art.home1) WHERE art.id=" . $id . Admin::articleAccess('art'));
    if ($query !== false) {
        $read_counter = $query['readnum'];
        if ($returnid == "load") {
            $returnid = $query['home1'];
        }
        $backlink = "index.php?p=content-articles-list&cat=" . $returnid . "&page=" . $returnpage;
        $actionplus = "&amp;id=" . $id . "&amp;returnid=" . $returnid . "&amp;returnpage=" . $returnpage;
        $submittext = "global.savechanges";
        $artlink = " <a href='" . Router::article($query['id'], $query['slug'], $query['cat_slug']) . "' target='_blank'><img src='images/icons/loupe.png' alt='prev'></a>";
        $new = false;
        $continue = true;
    }
} else {
    $backlink = "index.php?p=content-articles";
    $actionplus = "";
    $submittext = "global.create";
    $artlink = "";
    $new = true;
    $id = -1;
    $read_counter = 0;
    $query = [
        'id' => -1,
        'title' => '',
        'slug' => '',
        'description' => '',
        'perex' => '<p></p>',
        'picture_uid' => null,
        'content' => '',
        'author' => _user_id,
        'home1' => -2,
        'home2' => -1,
        'home3' => -1,
        'time' => time(),
        'visible' => 1,
        'public' => 1,
        'comments' => 1,
        'commentslocked' => 0,
        'showinfo' => 1,
        'confirmed' => 0,
        'rateon' => 1,
        'readnum' => 0,
    ];
    Extend::call('admin.article.default', ['data' => &$query]);
    if (isset($_GET['new_cat'])) {
        $query['home1'] = (int) Request::get('new_cat');
    }
    $continue = true;
}

/* ---  ulozeni  --- */

if (isset($_POST['title'])) {

    // nacteni promennych
    $slug = Request::post('slug', '');

    if ($slug === '') {
        $slug = Request::post('title', '');
    }

    $newdata['title'] = Html::cut(_e(Request::post('title')), 255);
    $newdata['slug'] = StringManipulator::slugify($slug);
    $newdata['description'] = Html::cut(_e(trim(Request::post('description'))), 255);
    $newdata['home1'] = (int) Request::post('home1');
    $newdata['home2'] = (int) Request::post('home2');
    $newdata['home3'] = (int) Request::post('home3');
    if (_priv_adminchangeartauthor) {
        $newdata['author'] = (int) Request::post('author');
    } else {
        $newdata['author'] = $query['author'];
    }
    $newdata['perex'] = Request::post('perex');
    $newdata['content'] = User::filterContent(Request::post('content'));
    $newdata['public'] = Form::loadCheckbox('public');
    $newdata['visible'] = Form::loadCheckbox('visible');
    if (_priv_adminconfirm || (_priv_adminautoconfirm && $newdata['author'] == _user_id)) {
        $newdata['confirmed'] = Form::loadCheckbox('confirmed');
    } else {
        $newdata['confirmed'] = $query['confirmed'];
    }
    $newdata['comments'] = Form::loadCheckbox('comments');
    $newdata['commentslocked'] = Form::loadCheckbox('commentslocked');
    $newdata['rateon'] = Form::loadCheckbox('rateon');
    $newdata['showinfo'] = Form::loadCheckbox('showinfo');
    $newdata['resetrate'] = Form::loadCheckbox('resetrate');
    $newdata['delcomments'] = Form::loadCheckbox('delcomments');
    $newdata['resetread'] = Form::loadCheckbox('resetread');
    $newdata['time'] = Form::loadTime('time', $query['time']);

    // kontrola promennych
    $error_log = [];

    // titulek
    if ($newdata['title'] === '') {
        $error_log[] = _lang('admin.content.articles.edit.error1');
    }

    // slug
    if ($newdata['slug'] === '') {
        $error_log[] = _lang('admin.content.form.slug.empty');
    }

    // kategorie
    $homechecks = ["home1", "home2", "home2"];
    foreach ($homechecks as $homecheck) {
        if ($newdata[$homecheck] != -1 || $homecheck == "home1") {
            if (DB::count(_page_table, 'type=' . _page_category . ' AND id=' . DB::val($newdata[$homecheck])) === 0) {
                $error_log[] = _lang('admin.content.articles.edit.error2');
            }
        }
    }

    // zruseni duplikatu
    if ($newdata['home1'] == $newdata['home2']) {
        $newdata['home2'] = -1;
    }
    if ($newdata['home2'] == $newdata['home3'] || $newdata['home1'] == $newdata['home3']) {
        $newdata['home3'] = -1;
    }

    // autor
    if (DB::result(DB::query("SELECT COUNT(*) FROM " . _user_table . " WHERE id=" . DB::val($newdata['author']) . " AND (id=" . _user_id . " OR (SELECT level FROM " . _user_group_table . " WHERE id=" . _user_table . ".group_id)<" . _priv_level . ")")) == 0) {
        $error_log[] = _lang('admin.content.articles.edit.error3');
    }

    // obrazek
    $newdata['picture_uid'] = $query['picture_uid'];
    if (empty($error_log) && isset($_FILES['picture']) && is_uploaded_file($_FILES['picture']['tmp_name'])) {

        // priprava moznosti zmeny velikosti
        $picOpts = [
            'file_path' => $_FILES['picture']['tmp_name'],
            'file_name' => $_FILES['picture']['name'],
            'target_dir' => 'images/articles/',
            'target_format' => 'jpg',
            'target_partitions' => 1,
            'resize' => [
                'mode' => 'fit',
                'keep_smaller' => true,
                'x' => _article_pic_w,
                'y' => _article_pic_h,
            ],
        ];
        Extend::call('admin.article.picture', ['opts' => &$picOpts]);

        // zpracovani
        $pic_uid = Article::uploadImage( $_FILES['picture']['tmp_name'], $_FILES['picture']['name'], $pic_err);

        if ($pic_uid !== null) {
            // uspech
            if (isset($query['picture_uid'])) {
                // odstraneni stareho
                Article::removeImage($query['picture_uid']);
            }
            $newdata['picture_uid'] = $pic_uid;
        } else {
            // chyba
            $error_log[] = Message::prefix(_lang('admin.content.form.picture'), $pic_err->getUserFriendlyMessage());
        }

    } elseif (isset($query['picture_uid']) && Form::loadCheckbox('picture-delete')) {
        // smazani obrazku
        Article::removeImage($query['picture_uid']);
        $newdata['picture_uid'] = null;
    }

    // ulozeni
    if (count($error_log) == 0) {

        // changeset
        $changeset = [
            'title' => $newdata['title'],
            'slug' => $newdata['slug'],
            'description' => $newdata['description'],
            'home1' => $newdata['home1'],
            'home2' => $newdata['home2'],
            'home3' => $newdata['home3'],
            'author' => $newdata['author'],
            'perex' => $newdata['perex'],
            'picture_uid' => $newdata['picture_uid'],
            'content' => $newdata['content'],
            'public' => $newdata['public'],
            'visible' => $newdata['visible'],
            'confirmed' => $newdata['confirmed'],
            'comments' => $newdata['comments'],
            'commentslocked' => $newdata['commentslocked'],
            'rateon' => $newdata['rateon'],
            'showinfo' => $newdata['showinfo'],
            'time' => $newdata['time'],
        ];

        if ($new) {
            $action = 'new';
            $changeset += [
                'readnum' => 0,
                'ratenum' => 0,
                'ratesum' => 0,
            ];
        } else {
            $action = 'edit';
        }

        Extend::call('admin.article.' . $action . '.before', [
            'id' => $id,
            'article' => $new ? null : $query,
            'changeset' => &$changeset,
        ]);

        if (!$new) {

            // update
            DB::update(_article_table, 'id=' . $id, $changeset);

            // smazani komentaru
            if ($newdata['delcomments'] == 1) {
                DB::delete(_comment_table, 'type=' . _post_article_comment . ' AND home=' . $id);
            }

            // vynulovani poctu precteni
            if ($newdata['resetread'] == 1) {
                DB::update(_article_table, 'id=' . $id, ['readnum' => 0]);
            }

            // vynulovani hodnoceni
            if ($newdata['resetrate'] == 1) {
                DB::update(_article_table, 'id=' . $id, [
                    'ratenum' => 0,
                    'ratesum' => 0
                ]);
                DB::delete(_iplog_table, 'type=' . _iplog_article_rated . ' AND var=' . $id);
            }

            // presmerovani
            $admin_redirect_to = 'index.php?p=content-articles-edit&id=' . $id . '&saved&returnid=' . $returnid . '&returnpage=' . $returnpage;

        } else {

            // vlozeni
            $id = DB::insert(_article_table, $changeset, true);

            // presmerovani
            $admin_redirect_to = 'index.php?p=content-articles-edit&id=' . $id . '&created&returnid=' . $newdata['home1'] . '&returnpage=1';

        }

        Extend::call('admin.article.' . $action, [
            'id' => $id,
            'article' => $query,
            'changeset' => &$changeset,
        ]);

        return;

    }

    $message = Message::warning(Message::renderList($error_log, 'errors'), true);
    $query = $newdata + $query;

}

/* ---  vystup  --- */

if ($continue) {

    // vyber autora
    if (_priv_adminchangeartauthor) {
        $author_select = Admin::userSelect("author", $query['author'], "adminart=1", "selectmedium");
    } else {
        $author_select = "";
    }

    // zprava
    if (isset($_GET['saved'])) {
        $message = Message::ok(_lang('global.saved') . " <small>(" . GenericTemplates::renderTime(time()) . ")</small>", true);
    }
    if (isset($_GET['created'])) {
        $message = Message::ok(_lang('global.created'));
    }

    // vypocet hodnoceni
    if (!$new) {
        if ($query['ratenum'] != 0) {
            $rate = DB::result(DB::query("SELECT ROUND(ratesum/ratenum) FROM " . _article_table . " WHERE id=" . $query['id'])) . "%, " . $query['ratenum'] . "x";
        } else {
            $rate = _lang('article.rate.nodata');
        }
    } else {
        $rate = "";
    }

    // slug
    $seo_input = "<input type='text' name='slug' value='" . $query['slug'] . "' maxlength='255' class='input" . (($author_select != '') ? 'medium' : 'big') . "'>";

    // obrazek
    $picture = '';
    if (isset($query['picture_uid'])) {
        $picture .= "<img src='" . _e(Router::file(Article::getImagePath($query['picture_uid']))) . "' alt='article picture' id='is-picture-file'>
<label id='is-picture-delete'><input type='checkbox' name='picture-delete' value='1'> <img src='images/icons/delete3.png' class='icon' alt='" . _lang('global.delete') . "'></label>";
    } else {
        $picture .= "<img src='images/art-no-pic.png' alt='no picture'>\n";
    }
    $picture .= "<input type='file' name='picture' id='is-picture-upload'>\n";

    // editacni pole
    $editor = Extend::buffer('admin.article.editor');

    if ($editor === '') {
        // vychozi implementace
        $editor = "<textarea name='content' rows='25' cols='68' class='editor'>" . _e($query['content']) . "</textarea>";
    }

    // formular
    $output .= Admin::backlink($backlink) . "
<h1>" . _lang('admin.content.articles.edit.title') . "</h1>
" . $message . "

" . (($new && !_priv_adminautoconfirm) ? Admin::note(_lang('admin.content.articles.edit.newconfnote')) : '') . "
" . ((!$new && $query['confirmed'] != 1) ? Admin::note(_lang('admin.content.articles.edit.confnote')) : '') . "

" . ((!$new && DB::count(_article_table, 'id!=' . DB::val($query['id']) . ' AND home1=' . DB::val($query['home1']) . ' AND slug=' . DB::val($query['slug'])) !== 0) ? Message::warning(_lang('admin.content.form.slug.collision')) : '') . "

<form class='cform' action='index.php?p=content-articles-edit" . $actionplus . "' method='post' enctype='multipart/form-data' name='artform'>

<table class='formtable'>

<tr>
<th>" . _lang('article.category') . "</th>
<td>"
    . Admin::pageSelect("home1", ['type' => _page_category, 'selected' => $query['home1']])
    . Admin::pageSelect("home2", ['type' => _page_category, 'selected' => $query['home2'], 'empty_item' => _lang('admin.content.form.category.none')])
    . Admin::pageSelect("home3", ['type' => _page_category, 'selected' => $query['home3'], 'empty_item' => _lang('admin.content.form.category.none')])
    . "
</td>
</tr>

<tr>
<th>" . _lang('admin.content.form.title') . "</th>
<td><input type='text' name='title' value='" . $query['title'] . "' class='inputmax'></td>
</tr>

<tr>
<th>" . _lang('admin.content.form.slug') . "</th>
<td>" . (($author_select == '' ? $seo_input : "
    <table class='ae-twoi'><tr>
    <td>" . $seo_input . "</td>
    <th>" . _lang('article.author') . "</th>
    <td>" . $author_select . "</td>
    </tr></table>
")) . "</td>
</tr>

<tr>
<th>" . _lang('admin.content.form.description') . "</th>
<td>
    <input type='text' name='description' value='" . $query['description'] . "' maxlength='255' class='inputmax'>
</td>
</tr>

<tr class='valign-top'>
<th>" . _lang('admin.content.form.perex') . "</th>
<td><textarea name='perex' rows='9' cols='94' class='areabigperex editor' data-editor-mode='lite'>" . _e($query['perex']) . "</textarea></td>
</tr>

<tr class='valign-top'>
<th>" . _lang('admin.content.form.content') . $artlink . "</th>
<td>

  <table id='ae-table'>
  <tr class='valign-top'>
    <td id='content-cell'>
      " . $editor . "
    </td>
    <td id='is-cell'>
      <div id='is-cell-wrapper'>
      <div id='is-cell-content'>

      <h2>" . _lang('admin.content.form.picture') . "</h2>
      <div id='is-picture'>" . $picture . "</div>

      <h2>" . _lang('admin.content.form.settings') . "</h2>
      <p id='is-settings'>
      <label><input type='checkbox' name='public' value='1'" . Form::activateCheckbox($query['public']) . "> " . _lang('admin.content.form.public') . "</label>
      <label><input type='checkbox' name='visible' value='1'" . Form::activateCheckbox($query['visible']) . "> " . _lang('admin.content.form.visible') . "</label>
      " . ((_priv_adminconfirm || (_priv_adminautoconfirm && $query['author'] == _user_id)) ? "<label><input type='checkbox' name='confirmed' value='1'" . Form::activateCheckbox($query['confirmed']) . "> " . _lang('admin.content.form.confirmed') . "</label>" : '') . "
      <label><input type='checkbox' name='comments' value='1'" . Form::activateCheckbox($query['comments']) . "> " . _lang('admin.content.form.comments') . "</label>
      <label><input type='checkbox' name='commentslocked' value='1'" . Form::activateCheckbox($query['commentslocked']) . "> " . _lang('admin.content.form.commentslocked') . "</label>
      <label><input type='checkbox' name='rateon' value='1'" . Form::activateCheckbox($query['rateon']) . "> " . _lang('admin.content.form.artrate') . "</label>
      <label><input type='checkbox' name='showinfo' value='1'" . Form::activateCheckbox($query['showinfo']) . "> " . _lang('admin.content.form.showinfo') . "</label>
      " . (!$new ? "<label><input type='checkbox' name='resetrate' value='1'> " . _lang('admin.content.form.resetartrate') . " <small>(" . $rate . ")</small></label>" : '') . "
      " . (!$new ? "<label><input type='checkbox' name='delcomments' value='1'> " . _lang('admin.content.form.delcomments') . " <small>(" . DB::count(_comment_table, 'home=' . DB::val($query['id']) . ' AND type=' . _post_article_comment) . ")</small></label>" : '') . "
      " . (!$new ? "<label><input type='checkbox' name='resetread' value='1'> " . _lang('admin.content.form.resetartread') . " <small>(" . $read_counter . ")</small></label>" : '') . "
      </p>

      </div>
      </div>
    </td>
  </tr>
  </table>

</td>
</tr>

<tr id='time-cell'>
<th>" . _lang('article.posted') . "</th>
<td>" . Form::editTime('time', $query['time'], true, $new) . "</td>
</tr>

" . Extend::buffer('admin.article.form', ['article' => $query]) . "

<tr>
<td></td>
<td id='ae-lastrow'><br><input type='submit' class='button bigger' value='" . _lang($submittext) . "' accesskey='s'>
" . (!$new ? "
<span class='customsettings'><a href='index.php?p=content-articles-delete&amp;id=" . $query['id'] . "&amp;returnid=" . $query['home1'] . "&amp;returnpage=1'><span><img src='images/icons/delete.png' alt='del' class='icon'>" . _lang('global.delete') . "</span></a></span>
<span class='customsettings'><small>" . _lang('admin.content.form.thisid') . " " . $query['id'] . "</small></span>
" : '') . "

</td>
</tr>

</table>

" . Xsrf::getInput() . "</form>

";

} else {
    $output .=
        Admin::backlink('index.php?p=content-articles')
        . "<h1>" . _lang('admin.content.articles.edit.title') . "</h1>\n"
        . Message::error(_lang('global.badinput'));
}
