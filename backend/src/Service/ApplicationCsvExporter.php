<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\JobApplication;

final readonly class ApplicationCsvExporter
{
    /**
     * @param list<JobApplication> $jobApplications
     */
    public function export(array $jobApplications): string
    {
        $handle = fopen('php://temp', 'w+');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create CSV buffer.');
        }

        fputcsv($handle, [
            'company',
            'location',
            'email',
            'subject',
            'custom_message',
            'official_source_url',
            'send_status',
            'response',
            'follow_up',
            'sent_at',
            'last_error',
            'created_at',
            'updated_at',
        ]);

        foreach ($jobApplications as $jobApplication) {
            $data = $jobApplication->toArray();

            fputcsv($handle, [
                $data['company'],
                $data['location'],
                $data['email'],
                $data['subject'],
                $data['custom_message'],
                $data['official_source_url'],
                $data['send_status'],
                $data['response'],
                $data['follow_up'] ? 'true' : 'false',
                $data['sent_at'] ?? '',
                $data['last_error'] ?? '',
                $data['created_at'],
                $data['updated_at'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        if ($csv === false) {
            throw new \RuntimeException('Unable to read CSV buffer.');
        }

        return $csv;
    }
}
