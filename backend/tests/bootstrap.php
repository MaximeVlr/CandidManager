<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Docker exports APP_ENV=dev in $_ENV; PHPUnit's forced server values must win.
foreach (['APP_ENV', 'APP_DEBUG', 'APP_SECRET', 'MAILER_DSN', 'MAIL_SEND_ENABLED', 'MAIL_DRY_RUN', 'MAIL_FROM', 'MAIL_FROM_NAME', 'MAIL_MAX_BATCH_SIZE'] as $name) {
    if (isset($_SERVER[$name])) {
        $_ENV[$name] = $_SERVER[$name];
        putenv($name.'='.$_SERVER[$name]);
    }
}

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
