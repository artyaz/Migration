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
     * @param $comments
     * @param $mapping
     * @return array
     */
    private function includeComments($comments, $mapping): array
    {
        $outputComments = [];

        foreach ($comments as $comment)
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

            $rtComment['author_id']  = $this->mapping($mapping, $comment['author'] ?? null);
            $rtComment['created_at'] = $comment['created_at'];
            $rtComment['value']      = $comment['body'];
            $rtComment['uploads']    = $commentUploads;
            $outputComments[]        = $rtComment;
        }

        return $outputComments;
    }

    /**
     * @param $mapping
     * @param $ticket
     * @return array
     */
    private function customFieldsMapping($mapping, $ticket): array
    {
        $outputMapping = [];

        foreach ($mapping as $sourceName => $targetName)
        {
            $rtCCField = [
                'id'    => $targetName,
                'value' => $ticket[$sourceName]
            ];
            $outputMapping[] = $rtCCField;
        }

        return $outputMapping;
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
                    'cf_ticketnotes' => '18055606057874'
                ]
            ];

            $newUser = $this->verifyMapAbility($ticket['contact_email'], $ticket['contact_name'], $ticket['contact_id']);
            $mapping['users'][$newUser['oldID']] = $newUser['newID'];

            $nt                    = &$newTicket['ticket'];
            $nt['subject']         = $ticket['subject'];
            $nt['requester_id']    = $mapping['users'][$ticket['contact_id']];
            $nt['priority']        = $ticket['priority'];
            $nt['status']          = $ticket['status'];
            $nt['created_at']      = $ticket['created_at'];
            $nt['assignee_id']     = $this->mapping($mapping['users'], $ticket['assignee_id'] ?? null);
            $nt['group_id']        = $this->mapping($mapping['groups'], $ticket['group_id']);
            $nt['comments']        = $this->includeComments($ticket['comments'], $mapping['users']);
            $nt['custom_fields']   = $this->customFieldsMapping($mapping['fields'], $ticket);

            $this->connect('imports/tickets', 'POST', [], $newTicket);
            unset($nt);
        }
    }
}