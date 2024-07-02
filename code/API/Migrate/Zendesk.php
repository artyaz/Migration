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
        if(count($user['users']) === 1 && $user['users'][0]['email'] === $email) {
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
    private function includeComments(&$comments, $mapping): array
    {
        foreach ($comments as &$comment)
        {
            if(false === empty($comment['attachments']))
            {
                foreach ($comment['attachments'] as $attachment)
                {
                    $file = fopen($attachment['attachment_url'], 'r');

                    $uploadResult = $this->connect('uploads.json', 'POST',
                        [ 'query' => ['filename' => $attachment['name']]],
                        $file, 'application/binary', false);

                    $comment['uploads'] = $uploadResult['upload']['token'];
                }
            }

            $comment = [
                'author_id'  => $this->mapping($mapping, $comment['author'] ?? null),
                'created_at' => $comment['created_at'],
                'value'      => $comment['body'],
                'uploads'    => $comment['uploads'] ?? []
            ];
        }

        return $comments;
    }

    /**
     * @param $mapping
     * @param $ticket
     * @return array
     */
    private function customFieldsMapping($mapping, &$ticket): array
    {
        foreach ($mapping as $sourceName => $targetName)
        {
            $rtCCField = [
                'id'    => $targetName,
                'value' => $ticket[$sourceName]
            ];
            $ticket['custom_fields'] = $rtCCField;
        }

        return $ticket;
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
        foreach ($tickets as &$ticket) {


            $mapping = [
                'users' => [
                    'default'        => '393588259920',
                ],
                'groups' => [
                    'default'        => '5481923040914',
                ],
                'fields' => [
                    'cf_ticketnotes' => '18055606057874'
                ]
            ];

            $newUser = $this->verifyMapAbility($ticket['contact_email'], $ticket['contact_name'], $ticket['contact_id']);
            $mapping['users'][$newUser['oldID']] = $newUser['newID'];

            $ticket['ticket'] = [
                'subject' => $ticket['subject'],
                'tags'    => $ticket['tags'],
                'requester_id' => $mapping['users'][$ticket['contact_id']],
                'priority' => $ticket['priority'],
                'status' => $ticket['status'],
                'created_at' => $ticket['created_at'],
                'assignee_id' => $this->mapping($mapping['users'], $ticket['assignee_id'] ?? null),
                'group_id' => $this->mapping($mapping['groups'], $ticket['group_id']),
                'comments' => $this->includeComments($ticket['comments'], $mapping['users']),
                'custom_fields' => $this->customFieldsMapping($mapping['fields'], $ticket),
            ];

            $this->connect('imports/tickets', 'POST', [], $ticket);
            unset($nt);
        }
    }
}