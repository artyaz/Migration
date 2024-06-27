<?php

namespace API;
use GuzzleHttp\Client;
class Tickets
{
    private function connector($object) {
        $url = 'https://devncie.freshdesk.com/api/v2/%s';
        $url = sprintf($url, $object);
        $client = new Client([
            'base_uri' => $url,
            'headers' => [
                'Authorization' => base64_encode('9ehqwHdT7HBS4ZxhrRZb:X'),
            ]
        ]);
        try {
            $response = $client->request('GET');
            $body = json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            var_dump($e->getMessage());
        }
        return $body;
    }

    private function getContactById($id){
        $requestItem = 'contacts/' . $id;
      return $this->connector($requestItem);
    }

    private function getAgentById($id){
        $requestItem = 'agents/' . $id;
        return $this->connector($requestItem);
    }

    private function getGroupById($id)
    {
        $requestItem = 'groups/' . $id;
        return $this->connector($requestItem);
    }

    private function getCompanyById($id) {
        $requestItem = 'companies/' . $id;
        return $this->connector($requestItem);
    }

    private function getTicketComments($id) {
        $requestItem = 'tickets/' . $id . '/reply';
        $response = $this->connector($requestItem);
    }

    private function getTicketStatusById($id){
        $statusMapping = [
            2 => 'Open',
            3 => 'Pending',
            4 => 'Resolved',
            5 => 'Closed',
        ];

        return $statusMapping[$id];
    }

    private function getTicketPriorityById($id){
        $priorityMapping = [
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
            4 => 'Urgent',
        ];

        return $priorityMapping[$id];
    }

    private function reviewContact(){

    }
    public function getTickets() {
        $readyTickets = [];
        $tickets = $this->connector('tickets');

        foreach ($tickets as $ticket) {
            $contact = [];

            if($ticket['responder_id'] === $ticket['requester_id']) {
                $contact = [
                    'id' => $ticket['responder_id'],
                'name' => $this->getAgentById($ticket['responder_id'])['contact']['name'],
                'email' => $this->getAgentById($ticket['responder_id'])['contact']['email'],
                ];

            } else {
                $contact = [
                    'id' => $ticket['requester_id'],
                    'name' => $this->getAgentById($ticket['requester_id'])['name'],
                    'email' => $this->getAgentById($ticket['requester_id'])['email'],
                ];
            }

            $readyTickets[] = [
                'id' => $ticket['id'],
                'subject' => $ticket['subject'],
                'status' => $this->getTicketStatusById($ticket['status']),
                'priority' => $this->getTicketPriorityById($ticket['priority']),
                'agent_id' => $ticket['responder_id'],
                'agent_name' => $this->getAgentById($ticket['responder_id'])['contact']['name'],
                'agent_email' => $this->getAgentById($ticket['responder_id'])['contact']['email'],
                'contact_id' => $contact['id'],
                'contact_name' => $contact['name'],
                'contact_email' => $contact['email'],
                'group_id' => $ticket['group_id'],
                'group_name' => $this->getGroupById($ticket['group_id'])['name'],
                'company_id' => $ticket['company_id'],
                'company_name' => $this->getCompanyById($ticket['company_id'])['name'],
            ];
        }
        return $readyTickets;
    }

}

$migration = new Tickets();

$result = $migration->getTickets();

echo $result;




