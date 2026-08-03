<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { fetchLogs } from '../api';
import type { SendLogFilters, SendLogListResponse } from '../types';

const logs = ref<SendLogListResponse | null>(null);
const isLoading = ref(true);
const error = ref<string | null>(null);

const filters = reactive<SendLogFilters>({
  search: '',
  status: '',
  page: 1,
  limit: 25,
});

async function loadLogs(): Promise<void> {
  isLoading.value = true;
  error.value = null;

  try {
    logs.value = await fetchLogs(filters);
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isLoading.value = false;
  }
}

function applyFilters(): void {
  filters.page = 1;
  void loadLogs();
}

onMounted(loadLogs);
</script>

<template>
  <section class="logs-layout">
    <header class="section-header">
      <div>
        <p class="eyebrow">Logs</p>
        <h1>Tentatives SMTP</h1>
      </div>
      <button type="button" @click="loadLogs">Actualiser</button>
    </header>

    <form class="filters-bar compact" @submit.prevent="applyFilters">
      <label>
        <span>Recherche</span>
        <input v-model="filters.search" type="search" placeholder="Entreprise, email, erreur" />
      </label>

      <label>
        <span>Statut</span>
        <select v-model="filters.status">
          <option value="">Tous</option>
          <option value="success">Succes</option>
          <option value="failure">Echec</option>
        </select>
      </label>

      <button type="submit">Filtrer</button>
    </form>

    <p v-if="error" class="error-message">{{ error }}</p>

    <section class="table-surface">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Entreprise</th>
            <th>Email</th>
            <th>Statut</th>
            <th>SMTP</th>
            <th>Duree</th>
            <th>Erreur</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isLoading">
            <td colspan="7">Chargement des logs</td>
          </tr>
          <tr v-else-if="(logs?.items.length ?? 0) === 0">
            <td colspan="7">Aucun log</td>
          </tr>
          <tr v-for="log in logs?.items" v-else :key="log.id">
            <td>{{ new Date(log.created_at).toLocaleString() }}</td>
            <td>{{ log.application.company }}</td>
            <td>{{ log.application.email }}</td>
            <td><span class="badge">{{ log.status }}</span></td>
            <td>{{ log.smtp_code ?? '-' }}</td>
            <td>{{ log.duration_ms ?? '-' }} ms</td>
            <td>{{ log.error_message ?? '-' }}</td>
          </tr>
        </tbody>
      </table>

      <footer class="pagination-bar">
        <span>{{ logs?.total ?? 0 }} log(s)</span>
        <div>
          <button type="button" :disabled="filters.page <= 1" @click="filters.page--; loadLogs()">Precedent</button>
          <span>Page {{ logs?.page ?? 1 }} / {{ logs?.pages ?? 1 }}</span>
          <button type="button" :disabled="filters.page >= (logs?.pages ?? 1)" @click="filters.page++; loadLogs()">Suivant</button>
        </div>
      </footer>
    </section>
  </section>
</template>
