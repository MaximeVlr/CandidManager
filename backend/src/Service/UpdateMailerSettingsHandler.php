<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\MailerSettingsUpdateRequest;
use App\Repository\MailerSettingsRepositoryInterface;

final readonly class UpdateMailerSettingsHandler
{
    public function __construct(
        private MailerSettingsRepositoryInterface $mailerSettings,
        private SecretCipher $secretCipher,
    ) {
    }

    public function __invoke(MailerSettingsUpdateRequest $request): array
    {
        $settings = $this->mailerSettings->get();
        $settings->update(
            $request->provider,
            $request->enabled,
            $request->fromEmail,
            $request->fromName,
            $request->username,
            $request->password === null ? null : $this->secretCipher->encrypt($request->password),
            $request->host,
            $request->port,
            $request->encryption,
        );
        $this->mailerSettings->save($settings);

        return $settings->toArray();
    }
}
