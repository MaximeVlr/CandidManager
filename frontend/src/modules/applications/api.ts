import type { ApplicationListFilters, ApplicationListResponse, ApplicationPreview, ImportApplicationsResponse, JobApplication, JobApplicationUpdatePayload, QueuedSendResponse, SendApplicationResponse } from './types';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080';

function buildApplicationSearchParams(filters: ApplicationListFilters, includePagination: boolean): URLSearchParams {
  const params = new URLSearchParams({
    sort: filters.sort,
    direction: filters.direction,
  });

  if (includePagination) {
    params.set('page', String(filters.page));
    params.set('limit', String(filters.limit));
  }

  if (filters.search.trim() !== '') {
    params.set('search', filters.search.trim());
  }

  if (filters.sendStatus !== '') {
    params.set('send_status', filters.sendStatus);
  }

  if (filters.response !== '') {
    params.set('response', filters.response);
  }

  if (filters.followUpCount !== '') {
    params.set('follow_up_count', filters.followUpCount);
  }

  return params;
}

export async function fetchApplications(filters: ApplicationListFilters): Promise<ApplicationListResponse> {
  const params = buildApplicationSearchParams(filters, true);

  const response = await fetch(`${apiBaseUrl}/api/applications?${params.toString()}`);

  if (!response.ok) {
    throw new Error(`Impossible de charger les candidatures (${response.status})`);
  }

  return await response.json() as ApplicationListResponse;
}

export function buildApplicationsCsvExportUrl(filters: ApplicationListFilters): string {
  const params = buildApplicationSearchParams(filters, false);
  params.set('limit', '10000');

  return `${apiBaseUrl}/api/export.csv?${params.toString()}`;
}

export function buildApplicationsJsonExportUrl(filters: ApplicationListFilters): string {
  const params = buildApplicationSearchParams(filters, false);
  params.set('limit', '10000');

  return `${apiBaseUrl}/api/export.json?${params.toString()}`;
}

export async function fetchApplication(id: string): Promise<JobApplication> {
  const response = await fetch(`${apiBaseUrl}/api/applications/${id}`);

  if (!response.ok) {
    throw new Error(`Impossible de charger la candidature (${response.status})`);
  }

  return await response.json() as JobApplication;
}

export async function createApplication(payload: JobApplicationUpdatePayload): Promise<JobApplication> {
  const response = await fetch(`${apiBaseUrl}/api/applications`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    throw new Error(`Impossible de creer la candidature (${response.status})`);
  }

  return await response.json() as JobApplication;
}

export async function updateApplication(id: string, payload: JobApplicationUpdatePayload): Promise<JobApplication> {
  const response = await fetch(`${apiBaseUrl}/api/applications/${id}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    throw new Error(`Impossible de mettre a jour la candidature (${response.status})`);
  }

  return await response.json() as JobApplication;
}

export async function deleteApplication(id: string): Promise<void> {
  const response = await fetch(`${apiBaseUrl}/api/applications/${id}`, {
    method: 'DELETE',
  });

  if (!response.ok) {
    throw new Error(`Impossible de supprimer la candidature (${response.status})`);
  }
}

export async function fetchApplicationPreview(id: string): Promise<ApplicationPreview> {
  const response = await fetch(`${apiBaseUrl}/api/applications/${id}/preview`);

  if (!response.ok) {
    throw new Error(`Impossible de charger la previsualisation (${response.status})`);
  }

  return await response.json() as ApplicationPreview;
}

export async function sendApplication(id: string): Promise<SendApplicationResponse> {
  const response = await fetch(`${apiBaseUrl}/api/send/${id}`, {
    method: 'POST',
  });

  const body = await response.json() as SendApplicationResponse;

  if (!response.ok) {
    throw new Error(body.message);
  }

  return body;
}

export async function sendFollowUpApplication(id: string): Promise<SendApplicationResponse> {
  const response = await fetch(`${apiBaseUrl}/api/send/${id}/follow-up`, {
    method: 'POST',
  });

  const body = await response.json() as SendApplicationResponse;

  if (!response.ok) {
    throw new Error(body.message);
  }

  return body;
}

export async function sendSelectedApplications(ids: string[]): Promise<QueuedSendResponse> {
  const response = await fetch(`${apiBaseUrl}/api/send/bulk`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ ids }),
  });

  if (!response.ok) {
    throw new Error(`Impossible de mettre les emails en file (${response.status})`);
  }

  return await response.json() as QueuedSendResponse;
}

export async function sendAllApplications(): Promise<QueuedSendResponse> {
  const response = await fetch(`${apiBaseUrl}/api/send/all`, {
    method: 'POST',
  });

  if (!response.ok) {
    throw new Error(`Impossible de mettre tous les emails en file (${response.status})`);
  }

  return await response.json() as QueuedSendResponse;
}

export async function retryFailedApplications(): Promise<QueuedSendResponse> {
  const response = await fetch(`${apiBaseUrl}/api/send/retry-failed`, {
    method: 'POST',
  });

  if (!response.ok) {
    throw new Error(`Impossible de reprendre les echecs (${response.status})`);
  }

  return await response.json() as QueuedSendResponse;
}

export async function importApplicationsJson(json: string): Promise<ImportApplicationsResponse> {
  const response = await fetch(`${apiBaseUrl}/api/import`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: json,
  });

  const body = await response.json() as ImportApplicationsResponse | ImportApplicationsErrorResponse;

  if (!response.ok) {
    const details = body.errors?.map((item) => {
      const prefix = item.index === null ? '' : `Ligne ${item.index + 1}: `;
      const field = item.field === null ? '' : `${item.field}: `;

      return `${prefix}${field}${item.message}`;
    }).join('\n');

    const errorBody = body as ImportApplicationsErrorResponse;

    throw new Error(details || errorBody.message || `Import impossible (${response.status})`);
  }

  return body as ImportApplicationsResponse;
}

type ImportApplicationsErrorResponse = {
  message?: string;
  errors?: ImportApplicationsResponse['errors'];
};
