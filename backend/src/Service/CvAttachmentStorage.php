<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final readonly class CvAttachmentStorage
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function __construct(
        private string $projectDir,
    ) {
    }

    /**
     * @return array{original_name: string, stored_name: string, mime_type: string}
     */
    public function store(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException('Le CV doit etre un fichier PDF, DOC ou DOCX.');
        }

        if ($file->getSize() !== null && $file->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Le CV ne doit pas depasser 5 Mo.');
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $storedName = Uuid::v7()->toRfc4122().'.'.$extension;
        $file->move($this->uploadDirectory(), $storedName);

        return [
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
        ];
    }

    public function absolutePath(string $storedName): string
    {
        return $this->uploadDirectory().DIRECTORY_SEPARATOR.$storedName;
    }

    private function uploadDirectory(): string
    {
        $directory = $this->projectDir.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'cv';

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de creer le dossier de stockage des CV.');
        }

        return $directory;
    }
}
