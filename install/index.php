<?php

namespace Sunlight\Installer;

use Kuria\Debug\Output;
use Sunlight\Core;
use Sunlight\Database\Database as DB;
use Sunlight\Database\DatabaseException;
use Sunlight\Database\DatabaseLoader;
use Sunlight\Database\SqlReader;
use Sunlight\Email;
use Sunlight\Util\Form;
use Sunlight\Util\Password;
use Sunlight\Util\PhpTemplate;
use Sunlight\Util\Request;
use Sunlight\Util\StringGenerator;
use Sunlight\Util\StringManipulator;
use Sunlight\Util\Url;

const CONFIG_PATH = __DIR__ . '/../config.php';

// bootstrap
require __DIR__ . '/../system/bootstrap.php';
Core::init('../', [
    'minimal_mode' => true,
    'config_file' => false,
    'debug' => true,
]);

/**
 * Configuration
 */
class Config
{
    /** @var array|null */
    static $config;

    /**
     * This is a static class
     */
    private function __construct()
    {
    }

    /**
     * Attempt to load the configuration file
     */
    static function load(): void
    {
        if (is_file(CONFIG_PATH)) {
            static::$config = require CONFIG_PATH;
        }
    }

    /**
     * See whether the configuration file is loaded
     *
     * @return bool
     */
    static function isLoaded(): bool
    {
        return static::$config !== null;
    }
}

/**
 * Installer labels
 */
class Labels
{
    /** @var string */
    private static $language = '_none';
    /** @var string[][] */
    private static $labels = [
        // no language set
        '_none' => [
            'step.submit' => 'Pokračovat / Continue',
            
            'language.title' => 'Jazyk / Language',
            'language.text' => 'Choose a language / zvolte jazyk:',
        ],

        // czech
        'cs' => [
            'step.submit' => 'Pokračovat',
            'step.reset' => 'Začít znovu',
            'step.exception' => 'Chyba',

            'config.title' => 'Konfigurace systému',
            'config.text' => 'Tento krok vygeneruje / přepíše soubor config.php.',
            'config.error.db.port.invalid' => 'neplatný port',
            'config.error.db.prefix.empty' => 'prefix nesmí být prázdný',
            'config.error.db.prefix.invalid' => 'prefix obsahuje nepovolené znaky',
            'config.error.db.connect.error' => 'nepodařilo se připojit k databázi, chyba: %error%',
            'config.error.db.create.error' => 'nepodařilo se vytvořit databázi (možná ji bude nutné vytvořit manuálně ve správě vašeho webhostingu): %error%',
            'config.error.secret.empty' => 'tajný hash nesmí být prázdný',
            'config.error.app_id.empty' => 'ID aplikace nesmí být prázdné',
            'config.error.app_id.invalid' => 'ID aplikace obsahuje nepovolené znaky',
            'config.db' => 'Přístup k MySQL databázi',
            'config.db.server' => 'Server',
            'config.db.server.help' => 'host (např. localhost nebo 127.0.0.1)',
            'config.db.port' => 'Port',
            'config.db.port.help' => 'pokud je potřeba nestandardní port, uveďte jej',
            'config.db.user' => 'Uživatel',
            'config.db.user.help' => 'uživatelské jméno',
            'config.db.password' => 'Heslo',
            'config.db.password.help' => 'heslo (je-li vyžadováno)',
            'config.db.name' => 'Databáze',
            'config.db.name.help' => 'název databáze (pokud neexistuje, bude vytvořena)',
            'config.db.prefix' => 'Prefix',
            'config.db.prefix.help' => 'předpona názvu tabulek',
            'config.system' => 'Nastavení systému',
            'config.url' => 'Adresa webu',
            'config.url.help' => 'absolutní cesta nebo URL ke stránkám',
            'config.secret' => 'Tajný hash',
            'config.secret.help' => 'náhodný tajný hash (používáno mj. jako součást XSRF ochrany)',
            'config.app_id' => 'ID aplikace',
            'config.app_id.help' => 'unikátní identifikátor v rámci serveru (používáno pro název session, cookies, ...)',
            'config.timezone' => 'Časové pásmo',
            'config.timezone.help' => 'časové pásmo (prázdné = spoléhat na nastavení serveru), viz',
            'config.locale' => 'Lokalizace',
            'config.locale.help' => 'nastavení lokalizace (prázdné = spoléhat na nastavení serveru), viz',
            'config.geo.latitude' => 'Zeměpisná šířka',
            'config.geo.longitude' => 'Zeměpisná délka',
            'config.geo.zenith' => 'Zenit',
            'config.debug' => 'Vývojový režim',
            'config.debug.help' => 'aktivovat vývojový režim (zobrazování chyb - nepoužívat na ostrém webu!)',

            'import.title' => 'Vytvoření databáze',
            'import.text' => 'Tento krok vytvoří potřebné tabulky a účet hlavního administrátora v databázi.',
            'import.error.settings.title.empty' => 'titulek webu nesmí být prázdný',
            'import.error.admin.username.empty' => 'uživatelské jméno nesmí být prázdné',
            'import.error.admin.password.empty' => 'heslo nesmí být prázdné',
            'import.error.admin.email.empty' => 'email nesmí být prázdný',
            'import.error.admin.email.invalid' => 'neplatná e-mailová adresa',
            'import.error.overwrite.required' => 'tabulky v databázi již existují, je potřeba potvrdit jejich přepsání',
            'import.settings' => 'Nastavení systému',
            'import.settings.title' => 'Titulek webu',
            'import.settings.title.help' => 'hlavní titulek stránek',
            'import.settings.description' => 'Popis webu',
            'import.settings.description.help' => 'krátký popis stránek',
            'import.settings.version_check' => 'Kontrola verze',
            'import.settings.version_check.help' => 'kontrolovat, zda je verze systému aktuální',
            'import.admin' => 'Účet administrátora',
            'import.admin.username' => 'Uživ. jméno',
            'import.admin.username.help' => 'povolené znaky jsou: a-z, tečka, pomlčka, podtržítko',
            'import.admin.email' => 'E-mail',
            'import.admin.email.help' => 'e-mailová adresa (pro obnovu hesla, atp.)',
            'import.admin.password' => 'Heslo',
            'import.admin.password.help' => 'nesmí být prázdné',
            'import.overwrite' => 'Přepis tabulek',
            'import.overwrite.text' => 'Pozor! V databázi již existují tabulky s prefixem "%prefix%". Přejete si je ODSTRANIT?',
            'import.overwrite.confirmation' => 'ano, nenávratně odstranit existující tabulky',

            'complete.title' => 'Hotovo',
            'complete.whats_next' => 'Co dál?',
            'complete.success' => 'Instalace byla úspěšně dokončena!',
            'complete.installdir_warning' => 'Než budete pokračovat, je potřeba odstranit adresář install ze serveru.',
            'complete.goto.web' => 'zobrazit stránky',
            'complete.goto.admin' => 'přihlásit se do administrace',
        ],

        // english
        'en' => [
            'step.submit' => 'Continue',
            'step.reset' => 'Start over',
            'step.exception' => 'Error',

            'config.title' => 'System configuration',
            'config.text' => 'This step will generate / overwrite the config.php file.',
            'config.error.db.port.invalid' => 'invalid port',
            'config.error.db.prefix.empty' => 'prefix must not be empty',
            'config.error.db.prefix.invalid' => 'prefix contains invalid characters',
            'config.error.db.connect.error' => 'could not connect to the database, error: %error%',
            'config.error.db.create.error' => 'could not create database (perhaps you need to create it manually via your webhosting\'s management page): %error%',
            'config.error.secret.empty' => 'secret hash must not be empty',
            'config.error.app_id.empty' => 'app ID must not be empty',
            'config.error.app_id.invalid' => 'app ID contains invalid characters',
            'config.db' => 'MySQL database access',
            'config.db.server' => 'Server',
            'config.db.server.help' => 'host (e.g. localhost or 127.0.0.1)',
            'config.db.port' => 'Port',
            'config.db.port.help' => 'if a non-standard port is needed, enter it',
            'config.db.user' => 'User',
            'config.db.user.help' => 'user name',
            'config.db.password' => 'Password',
            'config.db.password.help' => 'password (if required)',
            'config.db.name' => 'Database',
            'config.db.name.help' => 'name of the database (if it doesn\'t exist, it will be created)',
            'config.db.prefix' => 'Prefix',
            'config.db.prefix.help' => 'table name prefix',
            'config.system' => 'System configuration',
            'config.url' => 'Web URL',
            'config.url.help' => 'absolute path or URL of the website',
            'config.secret' => 'Secret hash',
            'config.secret.help' => 'random secret hash (used for XSRF protection etc.)',
            'config.app_id' => 'App ID',
            'config.app_id.help' => 'unique identifier (server-wide) (used as part of the session name, cookies, etc.)',
            'config.timezone' => 'Timezone',
            'config.timezone.help' => 'timezone (empty = rely on server settings), see',
            'config.locale' => 'Localisation',
            'config.locale.help' => 'localisation settings (empty = rely on server settings), see',
            'config.geo.latitude' => 'Latitude',
            'config.geo.longitude' => 'Longitude',
            'config.geo.zenith' => 'Zenith',
            'config.debug' => 'Debug mode',
            'config.debug.help' => 'enable debug mode (displays errors - do not use in production!)',

            'import.title' => 'Create database',
            'import.text' => 'This step will create system tables and the admin account.',
            'import.error.settings.title.empty' => 'title must not be empty',
            'import.error.admin.username.empty' => 'username must not be empty',
            'import.error.admin.password.empty' => 'password must not be empty',
            'import.error.admin.email.empty' => 'email must not be empty',
            'import.error.admin.email.invalid' => 'invalid email address',
            'import.error.overwrite.required' => 'tables already exist in the database - overwrite confirmation is required',
            'import.settings' => 'System settings',
            'import.settings.title' => 'Website title',
            'import.settings.title.help' => 'main website title',
            'import.settings.description' => 'Description',
            'import.settings.description.help' => 'brief site description',
            'import.settings.version_check' => 'Check version',
            'import.settings.version_check.help' => 'check whether the system is up to date',
            'import.admin' => 'Admin account',
            'import.admin.username' => 'Username',
            'import.admin.username.help' => 'allowed characters: a-z, dot, dash, underscore',
            'import.admin.email' => 'E-mail',
            'import.admin.email.help' => 'e-mail address (for password recovery and so on)',
            'import.admin.password' => 'Password',
            'import.admin.password.help' => 'must not be empty',
            'import.overwrite' => 'Overwrite tables',
            'import.overwrite.text' => 'Warning! The database already contains tables with "%prefix%" prefix. Do you wish to REMOVE them?',
            'import.overwrite.confirmation' => 'yes, remove the tables permanently',
            
            'complete.title' => 'Complete',
            'complete.whats_next' => 'What\'s next?',
            'complete.success' => 'Installation has been completed successfully!',
            'complete.installdir_warning' => 'Before you continue, you must remove the install directory.',
            'complete.goto.web' => 'open the website',
            'complete.goto.admin' => 'log into administration',
        ],
    ];

    /**
     * This is a static class
     */
    private function __construct()
    {
    }

    /**
     * Set the used language
     *
     * @param string $language
     */
    static function setLanguage(string $language): void
    {
        static::$language = $language;
    }

    /**
     * Get a label
     *
     * @param string     $key
     * @param array|null $replacements
     * @throws \RuntimeException     if the language has not been set
     * @throws \OutOfBoundsException if the key is not valid
     * @return string
     */
    static function get(string $key, ?array $replacements = null): string
    {
        if (static::$language === null) {
            throw new \RuntimeException('Language not set');
        }
        if (!isset(static::$labels[static::$language][$key])) {
            throw new \OutOfBoundsException(sprintf('Unknown key "%s[%s]"', static::$language, $key));
        }

        $value = static::$labels[static::$language][$key];

        if (!empty($replacements)) {
            $value = strtr($value, $replacements);
        }

        return $value;
    }

    /**
     * Render a label as HTML
     *
     * @param string     $key
     * @param array|null $replacements
     */
    static function render(string $key, ?array $replacements = null): void
    {
        echo _e(static::get($key, $replacements));
    }
}

/**
 * Installer errors
 */
class Errors
{
    /**
     * This is a static class
     */
    private function __construct()
    {
    }

    /**
     * @param array  $errors
     * @param string $mainLabelKey
     */
    static function render(array $errors, string $mainLabelKey): void
    {
        if (!empty($errors)) {
            ?>
<ul class="errors">
    <?php foreach ($errors as $error): ?>
        <li><?php is_array($error)
    ? Labels::render("{$mainLabelKey}.error.{$error[0]}", $error[1])
    : Labels::render("{$mainLabelKey}.error.{$error}") ?></li>
    <?php endforeach ?>
</ul>
<?php
        }
    }
}

/**
 * Step runner
 */
class StepRunner
{
    /** @var Step|null */
    private $current;
    /** @var Step[] */
    private $steps;

    /**
     * @param Step[] $steps
     */
    function __construct(array $steps)
    {
        $this->steps = $steps;

        // map step numbers
        $stepNumber = 0;
        foreach ($this->steps as $step) {
            $step->setNumber(++$stepNumber);
        }
    }

    /**
     * Run the steps
     *
     * @return string|null
     */
    function run(): ?string
    {
        $this->current = null;
        $submittedNumber = (int) Request::post('step_number', 0);

        // gather vars
        $vars = [];
        foreach ($this->steps as $step) {
            foreach ($step->getVarNames() as $varName) {
                $vars[$varName] = Request::post($varName, null, true);
            }
        }

        // run
        foreach ($this->steps as $step) {
            $this->current = $step;

            $step->setVars($vars);
            $step->setSubmittedNumber($submittedNumber);

            if ($step->isSubmittable() && $step->getNumber() === $submittedNumber) {
                $step->handleSubmit();
            }

            if (!$step->isComplete()) {
                return $this->runStep($step, $vars);
            }

            $step->postComplete();
        }

        return null;
    }

    /**
     * Get current step
     *
     * @return Step|null
     */
    function getCurrent(): ?Step
    {
        return $this->current;
    }

    /**
     * Get total number of steps
     *
     * @return int
     */
    function getTotal(): int
    {
        return count($this->steps);
    }

    /**
     * @param Step  $step
     * @param array $vars
     * @return string
     */
    private function runStep(Step $step, array $vars): string
    {
        ob_start();
        
        ?>
<form method="post" autocomplete="off">
    <?php if ($step->hasText()): ?>
        <p><?php Labels::render($step->getMainLabelKey() . '.text') ?></p>
    <?php endif ?>

    <?php Errors::render($step->getErrors(), $step->getMainLabelKey()) ?>

    <?php $step->run() ?>

    <p>
    <?php if ($step->getNumber() > 1): ?>
        <a class="btn btn-lg" id="start-over" href="."><?php Labels::render('step.reset') ?></a>
    <?php endif ?>
    <?php if ($step->isSubmittable()): ?>
        <input id="submit" name="step_submit" type="submit" value="<?php Labels::render('step.submit') ?>">
        <input type="hidden" name="<?= $step->getFormKeyVar() ?>" value="1">
        <input type="hidden" name="step_number" value="<?= $step->getNumber() ?>">
    <?php endif ?>
    </p>
    
    <?php foreach ($vars as $name => $value): ?>
        <?php if ($value !== null): ?>
            <input type="hidden" name="<?= _e($name) ?>" value="<?= _e($value) ?>">
        <?php endif ?>
    <?php endforeach ?>
</form>
<?php

        return ob_get_clean();
    }
}

/**
 * Base step
 */
abstract class Step
{
    /** @var int */
    protected $number;
    /** @var int */
    protected $submittedNumber;
    /** @var array */
    protected $vars = [];
    /** @var bool */
    protected $submitted = false;
    /** @var array */
    protected $errors = [];

    /**
     * @return string
     */
    abstract function getMainLabelKey(): string;

    /**
     * @return string
     */
    function getFormKeyVar(): string
    {
        return "step_submit_{$this->number}";
    }

    /**
     * @return string[]
     */
    function getVarNames(): array
    {
        return [];
    }

    /**
     * @param array $vars
     */
    function setVars(array $vars): void
    {
        $this->vars = $vars;
    }

    /**
     * @param int $number
     */
    function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * @return int
     */
    function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @param int $submittedNumber
     */
    function setSubmittedNumber(int $submittedNumber): void
    {
        $this->submittedNumber = $submittedNumber;
    }

    /**
     * @return int
     */
    function getSubmittedNumber(): int
    {
        return $this->submittedNumber;
    }

    /**
     * @return string
     */
    function getTitle(): string
    {
        return Labels::get($this->getMainLabelKey() . '.title');
    }

    /**
     * @return bool
     */
    function isComplete(): bool
    {
        return
            (
                (!$this->isSubmittable() || $this->submitted)
                && empty($this->errors)
            ) || (
                $this->submittedNumber > $this->number
            );
    }

    /**
     * @return bool
     */
    function hasText(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    function isSubmittable(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Handle step submission
     */
    function handleSubmit(): void
    {
        if ($this->isSubmittable()) {
            $this->doSubmit();
            $this->submitted = true;
        }
    }

    /**
     * Process the step form submission
     */
    protected function doSubmit(): void
    {
    }

    /**
     * Run the step
     */
    abstract function run(): void;

    /**
     * Execute some logic after the step has been completed
     * (e.g. before the next step is run)
     */
    function postComplete(): void
    {
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        if (Config::isLoaded() && array_key_exists($key, Config::$config)) {
            return Config::$config[$key];
        }

        return $default;
    }
}

/**
 * Choose a language step
 */
class ChooseLanguageStep extends Step
{
    function getMainLabelKey(): string
    {
        return 'language';
    }

    function getVarNames(): array
    {
        return ['language'];
    }

    function isComplete(): bool
    {
        return
            parent::isComplete()
            && isset($this->vars['language'])
            && in_array($this->vars['language'], ['cs', 'en'], true);
    }

    function run(): void
    {
        ?>
<ul class="big-list nobullets">
    <li><label><input type="radio" name="language" value="cs" checked> Čeština</label></li>
    <li><label><input type="radio" name="language" value="en"> English</label></li>
</ul>
<?php
    }

    function postComplete(): void
    {
        Labels::setLanguage($this->vars['language']);
    }
}

/**
 * Configuration step
 */
class ConfigurationStep extends Step
{
    function getMainLabelKey(): string
    {
        return 'config';
    }

    protected function doSubmit(): void
    {
        // load data
        $config = [
            'db.server' => trim(Request::post('config_db_server', '')),
            'db.port' => (int) trim(Request::post('config_db_port', '')) ?: null,
            'db.user' => trim(Request::post('config_db_user', '')),
            'db.password' => trim(Request::post('config_db_password', '')),
            'db.name' => trim(Request::post('config_db_name', '')),
            'db.prefix' => trim(Request::post('config_db_prefix', '')),
            'url' => rtrim(trim(Request::post('config_url', '')), '/'),
            'secret' => trim(Request::post('config_secret', '')),
            'app_id' => trim(Request::post('config_app_id', '')),
            'fallback_lang' => $this->vars['language'],
            'debug' => (bool) Form::loadCheckbox('config_debug'),
            'locale' => $this->getArrayConfigFromString(trim(Request::post('config_locale', ''))),
            'timezone' => trim(Request::post('config_timezone', '')) ?: null,
            'geo.latitude' => (float) trim(Request::post('config_geo_latitude')),
            'geo.longitude' => (float) trim(Request::post('config_geo_longitude')),
            'geo.zenith' => (float) trim(Request::post('config_geo_zenith')),
        ];

        // validate
        if ($config['db.port'] !== null && $config['db.port'] <= 0) {
            $this->errors[] = 'db.port.invalid';
        }

        if ($config['db.prefix'] === '') {
            $this->errors[] = 'db.prefix.empty';
        } elseif (!preg_match('{[a-zA-Z0-9_]+$}AD', $config['db.prefix'])) {
            $this->errors[] = 'db.prefix.invalid';
        }

        if ($config['secret'] === '') {
            $this->errors[] = 'secret.empty';
        }

        if ($config['app_id'] === '') {
            $this->errors[] = 'app_id.empty';
        } elseif (!ctype_alnum($config['app_id'])) {
            $this->errors[] = 'app_id.invalid';
        }

        // connect to the database
        if (empty($this->errors)) {
            $connectError = DB::connect($config['db.server'], $config['db.user'], $config['db.password'], '', $config['db.port']);

            if ($connectError === null) {
                // attempt to create the database if it does not exist
                try {
                    DB::query('CREATE DATABASE IF NOT EXISTS ' . DB::escIdt($config['db.name']) . ' COLLATE \'utf8mb4_unicode_ci\'');
                } catch (DatabaseException $e) {
                    $this->errors[] = ['db.create.error', ['%error%' => $e->getMessage()]];
                }
            } else {
                $this->errors[] = ['db.connect.error', ['%error%' => $connectError]];
            }
        }

        // generate config file
        if (empty($this->errors)) {
            $configTemplate = PhpTemplate::fromFile(__DIR__ . '/../system/config_template.php');

            file_put_contents(CONFIG_PATH, $configTemplate->compile($config));

            // reload
            Config::load();
        }
    }

    function isComplete(): bool
    {
        return
            parent::isComplete()
            && is_file(CONFIG_PATH)
            && Config::isLoaded()
            && DB::connect(Config::$config['db.server'], Config::$config['db.user'], Config::$config['db.password'], '', Config::$config['db.port']) === null;
    }

    function run(): void
    {
        // prepare defaults
        $url = Url::current();

        if (preg_match('{(/.+/)install/?$}AD', $url->path, $match)) {
            $defaultUrl = $match[1];
        } else {
            $defaultUrl = '/';
        }

        $defaultSecret = StringGenerator::generateString(64);
        $defaultGeoLatitude = 50.5;
        $defaultGeoLongitude = 14.26;
        $defaultGeoZenith = 90.583333;
        $defaultDebug = $this->getConfig('debug', false);

        ?>

<fieldset>
    <legend><?php Labels::render('config.db') ?></legend>
    <table>
        <tr>
            <th><?php Labels::render('config.db.server') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_db_server', $this->getConfig('db.server', 'localhost')) ?>></td>
            <td class="help"><?php Labels::render('config.db.server.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.db.port') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_db_port', $this->getConfig('db.port')) ?>></td>
            <td class="help"><?php Labels::render('config.db.port.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.db.user') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_db_user', $this->getConfig('db.user')) ?>></td>
            <td class="help"><?php Labels::render('config.db.user.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.db.password') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_db_password') ?>></td>
            <td class="help"><?php Labels::render('config.db.password.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.db.name') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_db_name', $this->getConfig('db.name')) ?>></td>
            <td class="help"><?php Labels::render('config.db.name.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.db.prefix') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_db_prefix', $this->getConfig('db.prefix', 'sunlight')) ?>></td>
            <td class="help"><?php Labels::render('config.db.prefix.help') ?></td>
        </tr>
    </table>
</fieldset>

<fieldset>
    <legend><?php Labels::render('config.system') ?></legend>
    <table>
        <tr>
            <th><?php Labels::render('config.url') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_url', $this->getConfig('url', $defaultUrl)) ?>></td>
            <td class="help"><?php Labels::render('config.url.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.secret') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_secret', $this->getConfig('secret', $defaultSecret)) ?>></td>
            <td class="help"><?php Labels::render('config.secret.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.app_id') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_app_id', $this->getConfig('app_id', 'sunlight')) ?>></td>
            <td class="help"><?php Labels::render('config.app_id.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.timezone') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_timezone', $this->getConfig('timezone')) ?>></td>
            <td class="help">
                <?php Labels::render('config.timezone.help') ?>
                <a href="https://php.net/timezones" target="_blank">PHP timezones</a>
            </td>
        </tr>
        <tr>
            <th><?php Labels::render('config.locale') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('config_locale', $this->getArrayConfigAsString('locale')) ?>></td>
            <td class="help">
                <?php Labels::render('config.locale.help') ?>
                <a href="https://php.net/setlocale" target="_blank">setlocale()</a>
            </td>
        </tr>
        <tr>
            <th><?php Labels::render('config.geo.latitude') ?></th>
            <td colspan="2"><input type="text"<?= Form::restorePostValueAndName('config_geo_latitude', $this->getConfig('geo.latitude', $defaultGeoLatitude)) ?>></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.geo.longitude') ?></th>
            <td colspan="2"><input type="text"<?= Form::restorePostValueAndName('config_geo_longitude', $this->getConfig('geo.longitude', $defaultGeoLongitude)) ?>></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.geo.zenith') ?></th>
            <td colspan="2"><input type="text"<?= Form::restorePostValueAndName('config_geo_zenith', $this->getConfig('geo.zenith', $defaultGeoZenith)) ?>></td>
        </tr>
        <tr>
            <th><?php Labels::render('config.debug') ?></th>
            <td><input type="checkbox"<?= Form::restoreCheckedAndName($this->getFormKeyVar(), 'config_debug', $this->getConfig('debug', $defaultDebug)) ?>></td>
            <td class="help"><?php Labels::render('config.debug.help') ?></td>
        </tr>
    </table>
</fieldset>
<?php
    }

    /**
     * Convert string representation of an array config to an array
     *
     * @param string        $value
     * @param callable|null $mapper
     * @return array|null
     */
    private function getArrayConfigFromString(string $value): ?array
    {
        return preg_split('/\s*,\s*/', $value, null, PREG_SPLIT_NO_EMPTY) ?: null;
    }

    /**
     * Get string representation of an array config option
     *
     * @param string      $key
     * @param array|null  $default
     * @return string
     */
    private function getArrayConfigAsString(string $key): string
    {
        if (!Config::isLoaded()) {
            $value = null;
        } else {
            $value = $this->getConfig($key);
        }

        return $value !== null
            ? implode(', ', $value)
            : '';
    }
}

/**
 * Import database step
 */
class ImportDatabaseStep extends Step
{
    /** @var string[] */
    private static $baseTableNames = [
        'article',
        'box',
        'user_group',
        'gallery_image',
        'iplog',
        'pm',
        'poll',
        'comment',
        'page',
        'shoutbox',
        'setting',
        'user',
        'user_activation',
        'redirect',
    ];
    /** @var array|null */
    private $existingTableNames;

    function getMainLabelKey(): string
    {
        return 'import';
    }
    
    protected function doSubmit(): void
    {
        $overwrite = (bool) Request::post('import_overwrite', false);
        
        $settings = [
            'title' => trim(Request::post('import_settings_title')),
            'description' => trim(Request::post('import_settings_description')),
            'language' => $this->vars['language'],
            'atreplace' => $this->vars['language'] === 'cs' ? '[zavinac]' : '[at]',
            'version_check' => Request::post('import_settings_version_check') ? 1 : 0,
        ];

        $admin = [
            'username' => StringManipulator::slugify(Request::post('import_admin_username'), false),
            'password' => Request::post('import_admin_password'),
            'email' => trim(Request::post('import_admin_email')),
        ];

        // validate
        if ($settings['title'] === '') {
            $this->errors[] = 'settings.title.empty';
        }

        if ($admin['username'] === '') {
            $this->errors[] = 'admin.username.empty';
        }

        if ($admin['password'] === '') {
            $this->errors[] = 'admin.password.empty';
        }

        if ($admin['email'] === '') {
            $this->errors[] = 'admin.email.empty';
        } elseif (!Email::validate($admin['email'])) {
            $this->errors[] = 'admin.email.invalid';
        }

        if (!$overwrite && count($this->getExistingTableNames()) > 0) {
            $this->errors[] = 'overwrite.required';
        }

        // import the database
        if (empty($this->errors)) {
            // use database
            DB::query('USE '. DB::escIdt(Config::$config['db.name']));

            // drop existing tables
            DatabaseLoader::dropTables($this->getExistingTableNames());
            $this->existingTableNames = null;

            // prepare
            $prefix = Config::$config['db.prefix'] . '_';

            // load the dump
            DatabaseLoader::load(
                SqlReader::fromFile(__DIR__ . '/database.sql'),
                'sunlight_',
                $prefix
            );
            
            // update settings
            foreach ($settings as $name => $value) {
                DB::update($prefix . 'setting', 'var=' . DB::val($name), ['val' => _e($value)]);
            }
            
            // update admin account
            DB::update($prefix . 'user', 'id=1', [
                'username' => $admin['username'],
                'password' => Password::create($admin['password'])->build(),
                'email' => $admin['email'],
                'activitytime' => time(),
                'registertime' => time(),
            ]);

            // alter initial content
            foreach ($this->getInitialContent() as $table => $rowMap) {
                foreach ($rowMap as $id => $changeset) {
                    DB::update($prefix . $table, 'id=' . DB::val($id), $changeset);
                }
            }
        }
    }

    private function getInitialContent(): array
    {
        if ($this->vars['language'] === 'cs') {
            return [
                'box' => [
                    1 => ['title' => 'Menu'],
                    2 => ['title' => 'Vyhledávání'],
                ],
                'user_group' => [
                    1 => ['title' => 'Hlavní administrátoři'],
                    2 => ['title' => 'Neregistrovaní'],
                    3 => ['title' => 'Registrovaní'],
                    4 => ['title' => 'Administrátoři'],
                    5 => ['title' => 'Moderátoři'],
                    6 => ['title' => 'Redaktoři'],
                ],
                'page' => [
                    1 => [
                        'title' => 'Úvod',
                        'content' => '<p>Instalace redakčního systému SunLight CMS ' . Core::VERSION . ' byla úspěšně dokončena!<br />
Nyní se již můžete <a href="admin/">přihlásit do administrace</a> (jméno a heslo bylo zvoleno při instalaci).</p>
<p>Podporu, diskusi a doplňky ke stažení naleznete na oficiálních webových stránkách <a href="https://sunlight-cms.cz/">sunlight-cms.cz</a>.</p>',
                    ],
                ],
            ];
        } else {
            return [
                'box' => [
                    1 => ['title' => 'Menu'],
                    2 => ['title' => 'Search'],
                ],
                'user_group' => [
                    1 => ['title' => 'Super administrators'],
                    2 => ['title' => 'Guests'],
                    3 => ['title' => 'Registered'],
                    4 => ['title' => 'Administrators'],
                    5 => ['title' => 'Moderators'],
                    6 => ['title' => 'Editors'],
                ],
                'page' => [
                    1 => [
                        'title' => 'Home',
                        'content' => '<p>Installation of SunLight CMS ' . Core::VERSION . ' has been a success!<br />
Now you can <a href="admin/">log in to the administration</a> (username and password has been setup during installation).</p>
<p>Support, forums and plugins are available at the official website <a href="https://sunlight-cms.cz/">sunlight-cms.cz</a>.</p>',
                    ],
                ],
            ];
        }
    }

    function isComplete(): bool
    {
        return
            parent::isComplete()
            && $this->isDatabaseInstalled();
    }

    function run(): void
    {
        ?>
<fieldset>
    <legend><?php Labels::render('import.settings') ?></legend>
    <table>
        <tr>
            <th><?php Labels::render('import.settings.title') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('import_settings_title') ?>></td>
            <td class="help"><?php Labels::render('import.settings.title.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('import.settings.description') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('import_settings_description') ?>></td>
            <td class="help"><?php Labels::render('import.settings.description.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('import.settings.version_check') ?></th>
            <td><input type="checkbox"<?= Form::restoreCheckedAndName($this->getFormKeyVar(), 'import_settings_version_check', true) ?>></td>
            <td class="help"><?php Labels::render('import.settings.version_check.help') ?></td>
        </tr>
    </table>
</fieldset>

<fieldset>
    <legend><?php Labels::render('import.admin') ?></legend>
    <table>
        <tr>
            <th><?php Labels::render('import.admin.username') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('import_admin_username', 'admin') ?>></td>
            <td class="help"><?php Labels::render('import.admin.username.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('import.admin.password') ?></th>
            <td><input type="password" name="import_admin_password"></td>
            <td class="help"><?php Labels::render('import.admin.password.help') ?></td>
        </tr>
        <tr>
            <th><?php Labels::render('import.admin.email') ?></th>
            <td><input type="text"<?= Form::restorePostValueAndName('import_admin_email', Config::$config['debug'] ? 'admin@localhost' : '@') ?>></td>
            <td class="help"><?php Labels::render('import.admin.email.help') ?></td>
        </tr>
    </table>
</fieldset>

<?php if (count($this->getExistingTableNames()) > 0): ?>
<fieldset>
    <legend><?php Labels::render('import.overwrite') ?></legend>
    <p class="msg warning"><?php Labels::render('import.overwrite.text', ['%prefix%' => Config::$config['db.prefix'] . '_']) ?></p>
    <p>
        <label>
            <input type="checkbox"<?= Form::restoreCheckedAndName($this->getFormKeyVar(), 'import_overwrite') ?>>
            <?php Labels::render('import.overwrite.confirmation') ?>
        </label>
    </p>
</fieldset>
<?php endif ?>
<?php
    }

    /**
     * @return bool
     */
    private function isDatabaseInstalled(): bool
    {
        return count(array_diff($this->getTableNames(), $this->getExistingTableNames())) === 0;
    }

    /**
     * @return string[]
     */
    private function getExistingTableNames(): array
    {
        if ($this->existingTableNames === null) {
            $this->existingTableNames = DB::queryRows(
                'SHOW TABLES FROM ' . DB::escIdt(Config::$config['db.name']) . ' LIKE ' . DB::val(Config::$config['db.prefix'] . '_%'),
                null,
                0,
                false,
                true
            ) ?: [];
        }

        return $this->existingTableNames;
    }

    /**
     * @return string[]
     */
    private function getTableNames(): array
    {
        $prefix = Config::$config['db.prefix'] . '_';

        return array_map(function ($baseTableName) use ($prefix) {
            return $prefix . $baseTableName;
        }, static::$baseTableNames);
    }
}

/**
 * Complete step
 */
class CompleteStep extends Step
{
    function getMainLabelKey(): string
    {
        return 'complete';
    }

    function isSubmittable(): bool
    {
        return false;
    }

    function hasText(): bool
    {
        return false;
    }

    function isComplete(): bool
    {
        return false;
    }

    function run(): void
    {
        ?>
<p class="msg success"><?php Labels::render('complete.success') ?></p>

<?php if (!Config::$config['debug']): ?>
    <p class="msg warning"><?php Labels::render('complete.installdir_warning') ?></p>
<?php endif ?>

<h2><?php Labels::render('complete.whats_next') ?></h2>

<ul class="big-list">
    <li><a href="<?= _e(Config::$config['url'] ?: '/') ?>" target="_blank"><?php Labels::render('complete.goto.web') ?></a></li>
    <li><a href="<?= _e(Config::$config['url']) ?>/admin/" target="_blank"><?php Labels::render('complete.goto.admin') ?></a></li>
</ul>
<?php
    }
}

// load configuration
Config::load();

// create step runner
$stepRunner= new StepRunner([
    new ChooseLanguageStep(),
    new ConfigurationStep(),
    new ImportDatabaseStep(),
    new CompleteStep(),
]);

// run
try {
    $content = $stepRunner->run();
} catch (\Throwable $e) {
    Output::cleanBuffers();

    ob_start();
    ?>
<h2><?php Labels::render('step.exception') ?></h2>
<pre><?= _e((string) $e) ?></pre>
<?php
    $content = ob_get_clean();
}
$step = $stepRunner->getCurrent();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {margin: 0; padding: 0;}
        body {margin: 1em; background-color: #ededed; font-family: sans-serif; font-size: 13px; color: #000;}
        a {color: #f60; text-decoration: none;}
        a:hover {color: #000;}
        h1, h2, h3, p, ol, ul, pre {line-height: 1.5;}
        h1 {margin: 0; padding: 0.5em 1em; font-size: 1.5em;}
        h2, h3 {margin: 0.5em 0;}
        p, ol, ul, pre {margin: 1em 0;}
        #step span {padding: 0 0.3em; margin-right: 0.2em; background-color: #fff;}
        #system-name {float: right; color: #f60;}
        h2 {font-size: 1.3em;}
        h3 {font-size: 1.1em;}
        h2:first-child, h3:first-child {margin-top: 0;}
        ul, ol {padding-left: 40px;}
        .big-list {margin: 0.5em 0; font-size: 1.5em;}
        .nobullets {list-style-type: none; padding-left: 0;}
        ul.errors {padding-top: 10px; padding-bottom: 10px; background-color: #eee;}
        ul.errors li {font-size: 1.1em; color: red;}
        select, input[type=text], input[type=password], input[type=reset], input[type=button], button {padding: 5px;}
        .btn {display: inline-block;}
        .btn, input[type=submit], input[type=button], input[type=reset], button {cursor: pointer; padding: 4px 16px; border: 1px solid #bbbbbb; background: #ededed; background: linear-gradient(to bottom, #f5f5f5, #ededed); color: #000; line-height: normal;}
        .btn:hover, input[type=submit]:hover, input[type=button]:hover, input[type=reset]:hover, button:hover {color: #fff; background: #fe5300; background: linear-gradient(to bottom, #fe7b3b, #ea4c00); border-color: #ea4c00; outline: none;}
        .btn-lg, input[type=submit] {padding: 10px; font-size: 1.2em;}
        fieldset {margin: 2em 0; border: 1px solid #ccc; padding: 10px;}
        legend {padding: 0 10px; font-weight: bold;}
        th {white-space: nowrap;}
        th, td {padding: 3px 5px;}
        form tbody th {text-align: right;}
        form td.help {color: #777;}
        pre {overflow: auto;}
        p.msg {padding: 10px;}
        p.msg.success {color: #080; background-color: #d9ffd9;}
        p.msg.notice {color: #000; background-color: #d9e3ff;}
        p.msg.warning {color: #c00; background-color: #ffd9d9;}
        #wrapper {margin: 0 auto; min-width: 600px; max-width: 950px;}
        #content {padding: 15px 30px 25px 30px; background-color: #fff;}
        #start-over {}
        #submit {float: right;}
        .cleaner {clear: both;}
    </style>
    <title><?= _e("[{$step->getNumber()}/{$stepRunner->getTotal()}]: {$step->getTitle()}") ?></title>
</head>

<body>

    <div id="wrapper">

        <h1>
            <span id="step">
                <span><?= $step->getNumber(), '/', $stepRunner->getTotal() ?></span>
                <?= _e($step->getTitle()) ?>
            </span>
            <span id="system-name">
                SunLight CMS <?= Core::VERSION ?>
            </span>
        </h1>

        <div id="content">
            <?= $content ?>

            <div class="cleaner"></div>
        </div>

    </div>

</body>
</html>
