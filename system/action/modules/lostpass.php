<?php

use Sunlight\Captcha;
use Sunlight\Database\Database as DB;
use Sunlight\Email;
use Sunlight\GenericTemplates;
use Sunlight\IpLog;
use Sunlight\Message;
use Sunlight\Router;
use Sunlight\Util\Form;
use Sunlight\Util\Password;
use Sunlight\Util\Request;
use Sunlight\Util\StringGenerator;
use Sunlight\Util\Url;

defined('_root') or exit;

if (!_lostpass) {
    $_index['type'] = _index_not_found;
    return;
}

if (_logged_in) {
    $_index['type'] = _index_guest_only;
    return;
}

/* ---  vystup  --- */

$_index['title'] = _lang('mod.lostpass');

if (isset($_GET['user'], $_GET['hash'])) {
    // kontrola hashe a zmena hesla
    do {

        // kontrola limitu
        if (!IpLog::check(_iplog_failed_login_attempt)) {
            $output .= Message::error(_lang('login.attemptlimit', ['%max_attempts%' => _maxloginattempts, '%minutes%' => _maxloginexpire / 60]));
            break;
        }

        // data uzivatele
        $user = Request::get('user');
        $hash = Request::get('hash');
        $userdata = DB::queryRow("SELECT id,email,username,security_hash,security_hash_expires FROM " . _user_table . " WHERE username=" . DB::val($user));
        if (
            $userdata === false
            || $hash !== $userdata['security_hash']
            || time() >= $userdata['security_hash_expires']
        ) {
            IpLog::update(_iplog_failed_login_attempt);
            $output .= Message::warning(_lang('mod.lostpass.badlink'));
            $output .= '<p><a href="' . _e(Router::module('lostpass')) . '">' . _lang('global.tryagain') . ' &gt;</a></p>';
            break;
        }

        // vygenerovat heslo a odeslat na email
        $newpass = StringGenerator::generateString(12);

        if (!Email::send(
            $userdata['email'],
            _lang('mod.lostpass.mail.subject', ['%domain%' => Url::base()->getFullHost()]),
            _lang('mod.lostpass.mail.text2', [
                '%domain%' => Url::base()->getFullHost(),
                '%username%' =>  $userdata['username'],
                '%newpass%' => $newpass,
                '%date%' => GenericTemplates::renderTime(time()),
                '%ip%' => _user_ip,
            ])
        )) {
            $output .= Message::error(_lang('global.emailerror'));
            break;
        }

        // zmenit heslo
        DB::update(_user_table, 'id=' . DB::val($userdata['id']), [
            'password' => Password::create($newpass)->build(),
            'security_hash' => null,
            'security_hash_expires' => 0,
        ]);

        // vse ok! email s heslem byl odeslan
        $output .= Message::ok(_lang('mod.lostpass.generated'));

    } while (false);
} else {
    // zobrazeni formulare
    $output .= "<p class='bborder'>" . _lang('mod.lostpass.p') . "</p>";

    // odeslani emailu
    $sent = false;
    if (isset($_POST['username'])) do {

        // kontrola limitu
        if (!IpLog::check(_iplog_password_reset_requested)) {
            $output .= Message::error(_lang('mod.lostpass.limit', ['%limit%' => _lostpassexpire / 60]));
            break;
        }

        // kontrolni obrazek
        if (!Captcha::check()) {
            $output .= Message::warning(_lang('captcha.failure2'));
            break;
        }

        // data uzivatele
        $username = Request::post('username');
        $email = Request::post('email');
        $userdata = DB::queryRow("SELECT id,email,username FROM " . _user_table . " WHERE username=" . DB::val($username) . " AND email=" . DB::val($email));
        if ($userdata === false) {
            $output .= Message::warning(_lang('mod.lostpass.notfound'));
            break;
        }

        // vygenerovani hashe
        $hash = StringGenerator::generateString(64);
        DB::update(_user_table, 'id=' . DB::val($userdata['id']), [
            'security_hash' => $hash,
            'security_hash_expires' => time() + 3600,
        ]);

        // odeslani emailu
        $link = Router::module('lostpass', 'user=' . $username . '&hash=' . $hash, true);

        if (!Email::send(
            $userdata['email'],
            _lang('mod.lostpass.mail.subject', ['%domain%' => Url::base()->getFullHost()]),
            _lang('mod.lostpass.mail.text', [
                '%domain%' => Url::base()->getFullHost(),
                '%username%' => $userdata['username'],
                '%link%' => $link,
                '%date%' => GenericTemplates::renderTime(time()),
                '%ip%' => _user_ip,
            ])
        )) {
            $output .= Message::error(_lang('global.emailerror'));
            break;
        }

        // vse ok! email byl odeslan
        IpLog::update(_iplog_password_reset_requested);
        $output .= Message::ok(_lang('mod.lostpass.mailsent'));
        $sent = true;

    } while (false);

    // formular
    if (!$sent) {
        $captcha = Captcha::init();

        $output .= Form::render(
            [
                'name' => 'lostpassform',
                'action' => Router::module('lostpass'),
                'submit_text' => _lang('global.send'),
                'autocomplete' => 'off',
            ],
            [
                ['label' => _lang('login.username'), 'content' => "<input type='text' class='inputsmall' maxlength='24'" . Form::restorePostValueAndName('username') . ">"],
                ['label' => _lang('global.email'), 'content' => "<input type='email' class='inputsmall' " . Form::restorePostValueAndName('email', '@') . ">"],
                $captcha
            ]
        );
    }
}
