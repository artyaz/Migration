<?php

include __DIR__ . '/vendor/autoload.php';

use API\Tickets;

$test = new Tickets();
$result = $test->test();