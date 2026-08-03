import type { ApplicationStats } from './types';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080';

export async function fetchStats(): Promise<ApplicationStats> {
  const response = await fetch(`${apiBaseUrl}/api/stats`);

  if (!response.ok) {
    throw new Error(`Impossible de charger les statistiques (${response.status})`);
  }

  return await response.json() as ApplicationStats;
}
