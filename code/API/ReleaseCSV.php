<?php

namespace API;

class ReleaseCSV
{
    /**
     * @param $data
     * @return void
     */
    public function formatCSV($data): void
    {
        $resultCSV = fopen("/code/API/results.csv", "c");

        $headers = array('Ticket ID', 'Subject', 'Status', 'Priority', 'Agent ID', 'Agent Name', 'Agent Email', 'Contact ID', 'Contact Name', 'Contact Email', 'Group ID', 'Group Name', 'Company ID', 'Company Name', 'Description', 'Comments');

        fputcsv($resultCSV, $headers);

        foreach ($data as $row) {
            fputcsv($resultCSV, $row);
        }
    }
}