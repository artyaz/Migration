<?php
namespace API;
include __DIR__ . '/vendor/autoload.php';

use API\Utils\InitiateMigration;

$test = new InitiateMigration();
$test ->startMigration();
