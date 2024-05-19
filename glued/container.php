<?php
declare(strict_types=1);

use Glued\Lib\Utils;
use Sabre\Event\Emitter;

require_once(__ROOT__ . '/vendor/vaizard/glued-lib/src/Includes/container.php');

$container->set('events', function () {
    return new Emitter();
});

$container->set('utils', function (Container $c) {
    return new Utils($c->get('settings'), $c->get('routecollector'));
});


