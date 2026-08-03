<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { fetchStats } from '../api';
import type { ApplicationStats } from '../types';

const stats = ref<ApplicationStats | null>(null);
const isLoading = ref(true);
const error = ref<string | null>(null);

async function loadStats(): Promise<void> {
  isLoading.value = true;
  error.value = null;

  try {
    stats.value = await fetchStats();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadStats);
</script>

<template>
  <section class="stats-layout">
    <header class="section-header">
      <div>
        <p class="eyebrow">Stats</p>
        <h1>Suivi des candidatures</h1>
      </div>
      <button type="button" @click="loadStats">Actualiser</button>
    </header>

    <p v-if="error" class="error-message">{{ error }}</p>

    <div v-if="isLoading" class="table-surface">Chargement des statistiques</div>

    <template v-else-if="stats">
      <section class="stats-grid">
        <article class="stat-tile">
          <span>Total</span>
          <strong>{{ stats.total }}</strong>
        </article>
        <article class="stat-tile">
          <span>En attente</span>
          <strong>{{ stats.pending }}</strong>
        </article>
        <article class="stat-tile">
          <span>Envoyees</span>
          <strong>{{ stats.sent }}</strong>
        </article>
        <article class="stat-tile">
          <span>Echecs</span>
          <strong>{{ stats.failed }}</strong>
        </article>
        <article class="stat-tile">
          <span>Relances</span>
          <strong>{{ stats.follow_up }}</strong>
        </article>
        <article class="stat-tile">
          <span>Taux echec</span>
          <strong>{{ stats.failure_rate }}%</strong>
        </article>
      </section>

      <section class="content-grid">
        <div class="panel">
          <h2>Reponses</h2>
          <dl class="settings-list">
            <div><dt>Aucune</dt><dd>{{ stats.responses.none }}</dd></div>
            <div><dt>En attente</dt><dd>{{ stats.responses.pending }}</dd></div>
            <div><dt>Positive</dt><dd>{{ stats.responses.positive }}</dd></div>
            <div><dt>Negative</dt><dd>{{ stats.responses.negative }}</dd></div>
          </dl>
        </div>

        <div class="panel">
          <h2>Logs SMTP</h2>
          <dl class="settings-list">
            <div><dt>Succes</dt><dd>{{ stats.logs.success }}</dd></div>
            <div><dt>Echecs</dt><dd>{{ stats.logs.failure }}</dd></div>
            <div><dt>En cours</dt><dd>{{ stats.sending }}</dd></div>
          </dl>
        </div>
      </section>
    </template>
  </section>
</template>
