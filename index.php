<?php

use Sunlight\Core;
use Sunlight\Extend;
use Sunlight\GenericTemplates;
use Sunlight\Plugin\TemplatePlugin;
use Sunlight\Plugin\TemplateService;
use Sunlight\Router;
use Sunlight\Template;
use Sunlight\Util\Html;
use Sunlight\Util\Request;
use Sunlight\Util\Response;
use Sunlight\Util\Url;
use Sunlight\Xsrf;

require './system/bootstrap.php';
Core::init('./', [
    'env' => Core::ENV_WEB,
]);

/* ----  priprava  ---- */

// motiv
/** @var TemplatePlugin $_template */
$_template = null;
/** @var string $_template_layout */
$_template_layout = null;

// nacist vychozi motiv
if (!Template::change(TemplateService::composeUid(_default_template, TemplatePlugin::DEFAULT_LAYOUT))) {
    Core::updateSetting('default_template', 'default');

    Core::systemFailure(
        'Motiv "%s" nebyl nalezen.',
        'Template "%s" was not found.',
        [_default_template]
    );
}

// nacist adresy
$_url = Url::current();
$_system_url = Url::parse(Core::$url);
$_url_path = $_url->path;
$_system_url_path = $_system_url->path;

// zkontrolovat aktualni cestu
if (strncmp($_url_path, $_system_url_path, strlen($_system_url_path)) === 0) {
    $_subpath = substr($_url_path, strlen($_system_url_path));

    // presmerovat /index.php na /
    if ($_subpath === '/index.php' && empty($_url->query)) {
        Response::redirect(Core::$url . '/');
        exit;
    }
} else {
    // neplatna cesta
    header('Content-Type: text/plain; charset=UTF-8');
    Response::notFound();

    echo _lang('global.error404.title');
    exit;
}

// konfiguracni pole webu
$_index = [
    // atributy
    'type' => null, // jedna z _index_* konstant
    'id' => null, // ciselne ID
    'slug' => null, // identifikator (string)
    'segment' => null, // cast identifikatoru, ktera byla rozpoznana jako segment (string)
    'url' => Router::generate(''), // zakladni adresa
    'title' => null, // titulek - <title>
    'heading' => null, // nadpis - <h1> (pokud je null, pouzije se title)
    'heading_enabled' => true, // vykreslit nadpis 1/0
    'output' => '', // obsah
    'backlink' => null, // url zpetneho odkazu

    // drobecky spadajici POD aktualni stranku
    // format je: array(array('title' => 'titulek', 'url' => 'url'), ...)
    'crumbs' => [],

    // stav stranky
    'is_rewritten' => false, // skutecny stav prepisu URL

    // presmerovani
    'redirect_to' => null, // adresa, kam presmerovat
    'redirect_to_permanent' => false, // permanentni presmerovani 1/0

    // motiv
    'template_enabled' => true, // pouzit motiv
    'body_classes' => [],
];


/* ---- priprava obsahu ---- */

Extend::call('index.init', ['index' => &$_index]);

$output = &$_index['output'];

if (empty($_POST) || Xsrf::check()) {
    // zjisteni typu
    if (isset($_GET['m'])) {

        // modul
        $_index['slug'] = Request::get('m');
        $_index['is_rewritten'] = !$_url->has('m');
        $_index['type'] = _index_module;

        Extend::call('mod.init');

        require _root . 'system/action/module.php';

    } elseif (!_logged_in && _notpublicsite) {

        // neverejne stranky
        $_index['is_rewritten'] = _pretty_urls;
        $_index['type'] = _index_unauthorized;

    } else do {

        // stranka / plugin
        if (_pretty_urls && isset($_GET['_rwp'])) {
            // hezka adresa
            $_index['slug'] = Request::get('_rwp');
            $_index['is_rewritten'] = true;
        } elseif (isset($_GET['p'])) {
            // parametr
            $_index['slug'] = Request::get('p');
        }

        if ($_index['slug'] !== null) {
            $segments = explode('/', $_index['slug']);
        } else {
            $segments = [];
        }

        if (!empty($segments) && $segments[count($segments) - 1] === '') {
            // presmerovat identifikator/ na identifikator
            $_url->path = rtrim($_url_path, '/');

            $_index['type'] = _index_redir;
            $_index['redirect_to'] = $_url->generateAbsolute();
            break;
        }

        // extend
        Extend::call('index.plugin', [
            'index' => &$_index,
            'segments' => $segments,
        ]);

        Extend::call('page.init');

        if ($_index['type'] === _index_plugin) {
            break;
        }

        // vykreslit stranku
        $_index['type'] = _index_page;
        require _root . 'system/action/page.php';

    } while (false);
} else {
    // spatny XSRF token
    require _root . 'system/action/xsrf_error.php';
}

/* ----  vystup  ---- */

Extend::call('index.prepare', ['index' => &$_index]);

// zpracovani stavu
switch ($_index['type']) {
    case _index_redir:
        // presmerovani
        $_index['template_enabled'] = false;
        Response::redirect($_index['redirect_to'], $_index['redirect_to_permanent']);
        break;

    case _index_not_found:
        // stranka nenelezena
        require _root . 'system/action/not_found.php';
        break;

    case _index_unauthorized:
        // pristup odepren
        require _root . 'system/action/login_required.php';
        break;

    case _index_guest_only:
        // pristup pouze pro neprihl. uziv
        require _root . 'system/action/guest_required.php';
        break;
}

Extend::call('index.ready', ['index' => &$_index]);

// vlozeni motivu
if ($_index['template_enabled']) {
    // nacist prvky motivu
    $_template->begin($_template_layout);
    $_template_boxes = $_template->getBoxes($_template_layout);
    $_template_path = $_template->getTemplate($_template_layout);

    Extend::call('index.template', [
        'path' => &$_template_path,
        'boxes' => &$_template_boxes,
    ]);

    // hlavicka
    echo GenericTemplates::renderHead();
    Template::head();

    ?>
</head>
<body<?php if ($_index['body_classes']): ?> class="<?= implode(' ', Html::escapeArrayItems($_index['body_classes'])) ?>"<?php endif ?><?= Extend::buffer('tpl.body_tag') ?>>

<?php require $_template_path ?>
<?= Extend::buffer('tpl.end') ?>

</body>
</html>
<?php
}

Extend::call('index.finish', ['index' => $_index]);
