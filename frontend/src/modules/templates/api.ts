import type { MailTemplate, MailTemplateCategory, TemplateCreatePayload, TemplateUpdatePayload, TemplatesResponse } from './types';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080';

export async function fetchTemplates(): Promise<TemplatesResponse> {
  const response = await fetch(`${apiBaseUrl}/api/templates`);

  if (!response.ok) {
    throw new Error(`Impossible de charger les templates (${response.status})`);
  }

  return await response.json() as TemplatesResponse;
}

export async function fetchTemplate(name: string): Promise<MailTemplate> {
  const response = await fetch(`${apiBaseUrl}/api/templates/${encodeURIComponent(name)}`);

  if (!response.ok) {
    throw new Error(`Impossible de charger le template (${response.status})`);
  }

  return await response.json() as MailTemplate;
}

export async function fetchTemplateCategories(): Promise<MailTemplateCategory[]> {
  const response = await fetch(`${apiBaseUrl}/api/template-categories`);

  if (!response.ok) {
    throw new Error(`Impossible de charger les catégories (${response.status})`);
  }

  const data = await response.json() as { items: MailTemplateCategory[] };
  return data.items;
}

export async function fetchTemplateCategory(id: string): Promise<MailTemplateCategory> {
  const response = await fetch(`${apiBaseUrl}/api/template-categories/${encodeURIComponent(id)}`);
  if (!response.ok) throw await templateError(response, 'Impossible de charger la catégorie');
  return await response.json() as MailTemplateCategory;
}

export async function createTemplateCategory(name: string): Promise<MailTemplateCategory> {
  const response = await fetch(`${apiBaseUrl}/api/template-categories`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name }),
  });
  if (!response.ok) throw await templateError(response, 'Impossible de créer la catégorie');
  return await response.json() as MailTemplateCategory;
}

export async function updateTemplateCategory(id: string, name: string): Promise<MailTemplateCategory> {
  const response = await fetch(`${apiBaseUrl}/api/template-categories/${encodeURIComponent(id)}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name }),
  });
  if (!response.ok) throw await templateError(response, 'Impossible de renommer la catégorie');
  return await response.json() as MailTemplateCategory;
}

export async function deleteTemplateCategory(id: string): Promise<void> {
  const response = await fetch(`${apiBaseUrl}/api/template-categories/${encodeURIComponent(id)}`, { method: 'DELETE' });
  if (!response.ok) throw await templateError(response, 'Impossible de supprimer la catégorie');
}

async function templateError(response: Response, fallback: string): Promise<Error> {
  const body = await response.json().catch(() => null) as { message?: string } | null;
  return new Error(body?.message ?? `${fallback} (${response.status})`);
}

export async function createTemplate(payload: TemplateCreatePayload): Promise<MailTemplate> {
  const response = await fetch(`${apiBaseUrl}/api/templates`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    throw await templateError(response, 'Impossible de créer le template');
  }

  return await response.json() as MailTemplate;
}

export async function updateTemplate(name: string, payload: TemplateUpdatePayload): Promise<MailTemplate> {
  const response = await fetch(`${apiBaseUrl}/api/templates/${encodeURIComponent(name)}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    throw await templateError(response, 'Impossible de sauvegarder le template');
  }

  return await response.json() as MailTemplate;
}

export async function deleteTemplate(name: string): Promise<void> {
  const response = await fetch(`${apiBaseUrl}/api/templates/${encodeURIComponent(name)}`, { method: 'DELETE' });

  if (!response.ok) {
    throw await templateError(response, 'Impossible de supprimer le template');
  }
}

export async function uploadTemplateCv(name: string, file: File): Promise<MailTemplate> {
  const formData = new FormData();
  formData.set('cv', file);

  const response = await fetch(`${apiBaseUrl}/api/templates/${encodeURIComponent(name)}/cv`, {
    method: 'POST',
    body: formData,
  });

  if (!response.ok) {
    const body = await response.json() as { message?: string };
    throw new Error(body.message ?? `Impossible d'associer le CV (${response.status})`);
  }

  return await response.json() as MailTemplate;
}

export async function fetchTemplateCvPreview(name: string): Promise<Blob> {
  const response = await fetch(`${apiBaseUrl}/api/templates/${encodeURIComponent(name)}/cv/preview`);

  if (!response.ok) {
    throw await templateError(response, "Impossible de charger l'aperçu du CV");
  }

  return await response.blob();
}
