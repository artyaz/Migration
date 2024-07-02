<?php

namespace API\Retrieve;

use API\Connector;

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
     * @param int|null $id
     * @return array
     */
    private function getTicket(?int $id): array {
        return $this->connect('tickets/' . $id, 'GET', ['query' => ['include' => 'conversations']]);
    }

    /**
     * @param $id
     * @return array
     */
    private function getTicketComments($id): array
    {

        $ticket = $this->getTicket($id);

        $sourceFormattedComments = count($ticket['conversations']) >= 10 ?
            $this->iteratePagination('tickets/' . $id . '/conversations') :
            $ticket['conversations'];

        $descriptionAuthor = $ticket['source'] === 10 ? $ticket['responder_id'] : $ticket['requester_id'];

        $comments[] = [
            'body' => $ticket['description_text'],
            'author' => $descriptionAuthor,
            'created_at' => $ticket['created_at'],
            'attachments' => $ticket['attachments'] ?? null
        ];

        foreach ($sourceFormattedComments as $item) {
            $comments[] = [
                'body' => $item['body_text'],
                'author' => $item['user_id'],
                'created_at' => $item['created_at'],
                'attachments' => $item['attachments']

            ];
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
     * @param $requesterID
     * @return array
     */
    private function reviewContact($requesterID): array
    {
        $request = $this->connect('contacts/' . $requesterID, 'GET');
        if($request){
            return $request;
        } else {
            return array_merge($this->connect('agents/' . $requesterID, 'GET')['contact'], $this->connect('agents/' . $requesterID, 'GET'));
        }
    }

    /**
     * @param $item
     * @param array $additionalParams
     * @return array
     */
    private function iteratePagination($item, array $additionalParams = []): array {
        $items = [];
        $page  = 1;

        do
        {
            $queryParams = [
                'query' => [
                    'page' => $page,
                    'per_page' => 10,
                ]
            ];

            $queryParams['query'] = array_merge($queryParams['query'], $additionalParams);

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

        $tickets = $this->iteratePagination('tickets', ['include' => 'description']);

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
                'group_name'      => $this->getGroupById($ticket['group_id'])['name'] ?? null,
                'company_id'      => $ticket['company_id'],
                'company_name'    => $this->getCompanyById($ticket['company_id'])['name'] ?? null,
                'cf_ticket_notes' => $ticket['custom_fields']['cf_ticket_notes'] ?? null,
                'comments'        => $this->getTicketComments($ticket['id']),
            ];
        }
        return $readyTickets;
    }
}
//









