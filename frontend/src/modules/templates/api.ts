import type { MailTemplate, TemplateUpdatePayload, TemplatesResponse } from './types';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080';

export async function fetchTemplates(): Promise<TemplatesResponse> {
  const response = await fetch(`${apiBaseUrl}/api/templates`);

  if (!response.ok) {
    throw new Error(`Impossible de charger les templates (${response.status})`);
  }

  return await response.json() as TemplatesResponse;
}

export async function updateTemplate(name: string, payload: TemplateUpdatePayload): Promise<MailTemplate> {
  const response = await fetch(`${apiBaseUrl}/api/templates/${name}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    throw new Error(`Impossible de sauvegarder le template (${response.status})`);
  }

  return await response.json() as MailTemplate;
}

export async function uploadTemplateCv(name: string, file: File): Promise<MailTemplate> {
  const formData = new FormData();
  formData.set('cv', file);

  const response = await fetch(`${apiBaseUrl}/api/templates/${name}/cv`, {
    method: 'POST',
    body: formData,
  });

  if (!response.ok) {
    const body = await response.json() as { message?: string };
    throw new Error(body.message ?? `Impossible d'associer le CV (${response.status})`);
  }

  return await response.json() as MailTemplate;
}
