import type { SendLogFilters, SendLogListResponse } from './types';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080';

export async function fetchLogs(filters: SendLogFilters): Promise<SendLogListResponse> {
  const params = new URLSearchParams({
    page: String(filters.page),
    limit: String(filters.limit),
  });

  if (filters.search.trim() !== '') {
    params.set('search', filters.search.trim());
  }

  if (filters.status !== '') {
    params.set('status', filters.status);
  }

  const response = await fetch(`${apiBaseUrl}/api/logs?${params.toString()}`);

  if (!response.ok) {
    throw new Error(`Impossible de charger les logs (${response.status})`);
  }

  return await response.json() as SendLogListResponse;
}
