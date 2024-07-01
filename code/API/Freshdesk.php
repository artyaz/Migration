<?php

namespace API;

use API\Migrate\Zendesk;

class Freshdesk extends Connector
{
    function __construct($credentials = []){
        parent::__construct($credentials);
    }

    /**
     * @param int|null $id
     * @return array
     */
    private function getAgentById(?int $id): array
    {
        return $this->connect('agents/' . $id, 'GET');
    }

    /**
     * @param int|null $id
     * @return array
     */
    private function getGroupById(?int $id): array
    {
        return $this->connect('groups/' . $id, 'GET');
    }

    /**
     * @param int|null $id
     * @return array
     */
    private function getCompanyById(?int $id): array
    {
        return $this->connect('companies/' . $id, 'GET');
    }

    /**
     * @param int $id
     * @return array
     */
    private function getTicketComments(int $id): array
    {
        $requestItem = $this->iteratePagination('tickets/' . $id . '/conversations');

        $comments = [];

        foreach ($requestItem as $item) {
            $comment = [
                'body' => $item['body_text'],
                'author' => $item['user_id'],
                'created_at' => $item['created_at'],

            ];
            $comments[] = $comment;
        }
        return $comments;
    }

    /**
     * @param $id
     * @return string
     */
    private function getTicketStatusById($id): string{
        $statusMapping = [
            2 => 'Open',
            3 => 'Pending',
            4 => 'Resolved',
            5 => 'Closed',
        ];

        return $statusMapping[$id];
    }

    /**
     * @param $id
     * @return string
     */
    private function getTicketPriorityById($id): string
    {
        $priorityMapping = [
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
            4 => 'Urgent',
        ];

        return $priorityMapping[$id];
    }

    /**
     * @param $id
     * @return string
     */
    private function getTicketDescription($id): string
    {
        return $this->connect('tickets/' . $id, 'GET')['description_text'];
    }

    /**
     * @param $requesterID
     * @return array
     */
    private function reviewContact($requesterID): array
    {
        if($this->connect('contacts/' . $requesterID, 'GET')){
            return $this->connect('contacts/' . $requesterID, 'GET');
        } else {
            return array_merge($this->connect('agents/' . $requesterID, 'GET')['contact'], $this->connect('agents/' . $requesterID, 'GET'));
        }
    }

    /**
     * @param $item
     * @return array
     */
    private function iteratePagination($item): array {
        $items = [];
        $page  = 1;

        do
        {
            $queryParams = [
                'query' => [

                    'page' => $page,
                    'per_page' => 1
                ]
            ];
            $requestItem = $this->connect($item, 'GET', $queryParams);
            $items = array_merge($items, $requestItem);
            $page++;
        } while (false === empty($requestItem));

        return $items;
    }

    /**
     * @return array
     */
    public function getTickets(): array
    {
        $readyTickets = [];

        $tickets = $this->iteratePagination('tickets');

        foreach ($tickets as $ticket)
        {
            $contact = $this->reviewContact($ticket['requester_id']);

            $readyTickets[] = [
                'id'              => $ticket['id'],
                'subject'         => $ticket['subject'],
                'created_at'      => $ticket['created_at'],
                'status'          => $this->getTicketStatusById($ticket['status']),
                'priority'        => $this->getTicketPriorityById($ticket['priority']),
                'agent_id'        => $ticket['responder_id'],
                'agent_name'      => $this->getAgentById($ticket['responder_id'])['contact']['name'] ?? null,
                'agent_email'     => $this->getAgentById($ticket['responder_id'])['contact']['email'] ?? null,
                'contact_id'      => $contact['id'] ?? $ticket['responder_id'],
                'contact_name'    => $contact['name'],
                'contact_email'   => $contact['email'],
                'group_id'        => $ticket['group_id'],
                'group_name'      => $this->getGroupById($ticket['group_id'])['name'],
                'company_id'      => $ticket['company_id'],
                'company_name'    => $this->getCompanyById($ticket['company_id'])['name'] ?? null,
                'cf_ticket_notes' => $ticket['custom_fields']['cf_ticket_notes'] ?? null,
                'description'     => $this->getTicketDescription($ticket['id']),
                'comments'        => $this->getTicketComments($ticket['id']),
            ];
        }
        return $readyTickets;
    }



}





$freshdeskCredentials = [
    'apikey' => base64_encode('C9eIqD2crcaRhAcnKI8R:X'),
    'url' => 'https://joeroc.freshdesk.com/api/v2/'
];

$zendeskCredentials = [
    'apikey' => 'Basic ' . base64_encode('qa@help-desk-migration.com/token:wsQQ0Xu6rQOQPTB50Auj5ev3Vj3QZWDe3AZUUvyS'),
    'url' => 'https://helpdeskmigrationservice1652109111.zendesk.com/api/v2/'
];

$zendesk = new Zendesk($zendeskCredentials);

$migration = new Freshdesk($freshdeskCredentials);

$zendesk->importTicket($migration->getTickets());





