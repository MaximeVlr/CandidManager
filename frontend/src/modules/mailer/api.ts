import type { MailerSettings, MailerSettingsPayload } from './types';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080';

export async function fetchMailerSettings(): Promise<MailerSettings> {
  const response = await fetch(`${apiBaseUrl}/api/mailer-settings`);

  if (!response.ok) {
    throw new Error(`Impossible de charger la configuration mailer (${response.status})`);
  }

  return await response.json() as MailerSettings;
}

export async function updateMailerSettings(payload: MailerSettingsPayload): Promise<MailerSettings> {
  const response = await fetch(`${apiBaseUrl}/api/mailer-settings`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const body = await response.json() as { message?: string };
    throw new Error(body.message ?? `Impossible de sauvegarder la configuration mailer (${response.status})`);
  }

  return await response.json() as MailerSettings;
}
