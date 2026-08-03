<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\JobApplication;

final readonly class ApplicationJsonExporter
{
    /**
     * @param list<JobApplication> $jobApplications
     */
    public function export(array $jobApplications): string
    {
        $applications = [];

        foreach ($jobApplications as $jobApplication) {
            $data = $jobApplication->toArray();

            $applications[] = [
                'company' => $data['company'],
                'location' => $data['location'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'custom_message' => $data['custom_message'],
                'official_source_url' => $data['official_source_url'],
                'sent' => $data['send_status'] === 'sent',
                'response' => $data['response'],
                'follow_up' => $data['follow_up'],
            ];
        }

        return json_encode(['applications' => $applications], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
