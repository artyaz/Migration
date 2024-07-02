<?php
namespace API\Utils;
use API\Migrate\Zendesk;
use API\Retrieve\Freshdesk;

class InitiateMigration {
    function startMigration(): void
    {
        $env = parse_ini_file('.env');
        $freshdeskCredentials = [
            'apikey' => base64_encode($env['FRESHDESK_TOKEN']),
            'url' => $env['FRESHDESK_URL']
        ];

        $zendeskCredentials = [
            'apikey' => 'Basic ' . base64_encode($env['ZENDESK_EMAIL'] . '/token:' . $env['ZENDESK_TOKEN']),
            'url' => $env['ZENDESK_URL']
        ];

        $zendesk = new Zendesk($zendeskCredentials);

        $migration = new Freshdesk($freshdeskCredentials);

        $zendesk->importTicket($migration->getTickets());

    }
}




