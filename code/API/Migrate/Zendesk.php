<?php
namespace API\Migrate;
use API\Connector;

class Zendesk extends Connector {

    /**
     * @param array $credentials
     */
    function __construct(array $credentials = []) {
        parent::__construct($credentials);
    }

    /**
     * @param $email
     * @param $name
     * @param $oldId
     * @return array
     */
    private function verifyMapAbility($email, $name, $oldId): array
    {
        $user = $this->connect('users/search', 'GET', ['query' => ['query' => 'email:"' . $email . '"']]);
        if(count($user['users'])) {
            return ['oldID' => $oldId, 'newID' => $user['users']['0']['id']];
        } else {
            $newUser = $this->connect('users', 'POST', [], ['user' => ['name' => $name, 'email' => $email]]);
            return ['oldID' => $oldId, 'newID' => $newUser['user']['id']];
        }
    }

    /**
     * @param $mapping
     * @param $id
     * @return string
     */
    private function mapping($mapping, $id): string
    {
        if($mapping[$id] ?? null) {
            return $mapping[$id];
        } else {
            return $mapping['default'];
        }
    }

    /**
     * @param $tickets
     * @return void
     */
    public function importTicket($tickets): void
    {
        foreach ($tickets as $ticket) {
            $mapping = [
                'users' => [
                    'default' => '393588259920',
                ],
                'groups' => [
                    'default' => '5481923040914',
                ],
                'fields' => [
                    'cf_ticket_notes' => '18055606057874'
                ]
            ];

            $newUser =$this->verifyMapAbility($ticket['contact_email'], $ticket['contact_name'], $ticket['contact_id']);
            $mapping['users'][$newUser['oldID']] = $newUser['newID'];

            $rt = [];

            $rt['subject']      = $ticket['subject'];
            $rt['requester_id'] = $mapping['users'][$ticket['contact_id']];
            $rt['priority']     = $ticket['priority'];
            $rt['status']       = $ticket['status'];
            $rt['created_at']   = $ticket['created_at'];
            $rt['assignee_id']  = $this->mapping($mapping['users'], $ticket['assignee_id'] ?? null);
            $rt['group_id']     = $this->mapping($mapping['groups'], $ticket['group_id']);

            foreach ($ticket['comments'] as $comment)
            {
                $commentUploads = [];

                if($comment['attachments'] ?? null)
                {
                    foreach ($comment['attachments'] as $attachment)
                    {
                        $file = fopen($attachment['attachment_url'], 'r');

                        $uploadResult = $this->connect('uploads.json', 'POST',
                            [ 'query' => ['filename' => $attachment['name']]],
                            $file, 'application/binary', false);

                        $commentUploads[] = $uploadResult['upload']['token'];
                    }
                }

                $rtComment = [];
                $rtComment['author_id']  = $this->mapping($mapping['users'], $comment['author'] ?? null);
                $rtComment['created_at'] = $comment['created_at'];
                $rtComment['value']      = $comment['body'];
                $rtComment['uploads']    = $commentUploads;
                $rt['comments'][]        = $rtComment;
            }
            foreach ($mapping['fields'] as $sourceName => $targetName)
            {
                $rtCCField = [
                    'id'    => $targetName,
                    'value' => $ticket[$sourceName]
                ];
                $rt['custom_fields'][] = $rtCCField;
            }
            $readyTickets['ticket'] = $rt;
            $this->connect('imports/tickets', 'POST', [], $readyTickets);
        }
    }
}
//