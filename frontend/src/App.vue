<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { buildApplicationsCsvExportUrl, buildApplicationsJsonExportUrl, createApplication, deleteApplication, fetchApplication, fetchApplicationPreview, fetchApplications, importApplicationsJson, retryFailedApplications, sendAllApplications, sendApplication, sendFollowUpApplication, sendSelectedApplications, updateApplication } from './modules/applications/api';
import ApplicationEditModal from './modules/applications/components/ApplicationEditModal.vue';
import ApplicationPreviewDrawer from './modules/applications/components/ApplicationPreviewDrawer.vue';
import type { ApplicationListFilters, ApplicationListResponse, ApplicationPreview, JobApplication, JobApplicationUpdatePayload } from './modules/applications/types';
import { fetchHealth, type HealthResponse } from './modules/health/api';
import LogsPanel from './modules/logs/components/LogsPanel.vue';
import { fetchMailerSettings } from './modules/mailer/api';
import MailerSettingsPanel from './modules/mailer/components/MailerSettingsPanel.vue';
import type { MailerSettings } from './modules/mailer/types';
import StatsPanel from './modules/stats/components/StatsPanel.vue';
import TemplatesPanel from './modules/templates/components/TemplatesPanel.vue';

const health = ref<HealthResponse | null>(null);
const mailerSettings = ref<MailerSettings | null>(null);
const applications = ref<ApplicationListResponse | null>(null);
const selectedApplication = ref<JobApplication | null>(null);
const selectedIds = ref<Set<string>>(new Set());
const editedApplication = ref<JobApplication | null>(null);
const isCreatingApplication = ref(false);
const preview = ref<ApplicationPreview | null>(null);
const isHealthLoading = ref(true);
const isApplicationsLoading = ref(true);
const isSaving = ref(false);
const isPreviewLoading = ref(false);
const isSending = ref(false);
const isImporting = ref(false);
const isApiLoading = ref(false);
const isExportModalOpen = ref(false);
const error = ref<string | null>(null);
const notice = ref<string | null>(null);
const isSidebarCollapsed = ref(false);
const activeView = ref<'applications' | 'templates' | 'mailer' | 'logs' | 'stats'>('applications');

const filters = reactive<ApplicationListFilters>({
  search: '',
  sendStatus: '',
  response: '',
  followUp: '',
  sort: 'created_at',
  direction: 'desc',
  page: 1,
  limit: 25,
});

const initialFilters: ApplicationListFilters = {
  search: '',
  sendStatus: '',
  response: '',
  followUp: '',
  sort: 'created_at',
  direction: 'desc',
  page: 1,
  limit: 25,
};

const hasApplications = computed(() => (applications.value?.items.length ?? 0) > 0);
const selectedCount = computed(() => selectedIds.value.size);
const selectedMailerName = computed(() => {
  if (mailerSettings.value === null || !mailerSettings.value.enabled) {
    return 'Aucun mailer actif';
  }

  return mailerSettings.value.provider === 'gmail' ? 'Gmail' : 'Brevo';
});
const selectedMailerTransport = computed(() => {
  if (mailerSettings.value === null) {
    return 'Configuration en cours de chargement';
  }

  if (!mailerSettings.value.enabled) {
    return 'Envois desactives';
  }

  return mailerSettings.value.provider === 'gmail'
    ? `${mailerSettings.value.host}:${mailerSettings.value.port}`
    : 'SMTP Brevo';
});

function canSendFollowUp(application: JobApplication): boolean {
  if (application.sent_at === null) {
    return false;
  }

  const sentAt = new Date(application.sent_at);

  if (Number.isNaN(sentAt.getTime())) {
    return false;
  }

  const sevenDaysInMilliseconds = 7 * 24 * 60 * 60 * 1000;

  return Date.now() - sentAt.getTime() >= sevenDaysInMilliseconds;
}

function sendStatusLabel(status: JobApplication['send_status']): string {
  const labels: Record<JobApplication['send_status'], string> = {
    pending: 'En attente',
    sending: 'En cours',
    sent: 'Envoye',
    failed: 'Echec',
  };

  return labels[status];
}

function responseLabel(response: JobApplication['response']): string {
  const labels: Record<JobApplication['response'], string> = {
    none: 'Aucune',
    pending: 'En attente',
    positive: 'Positive',
    negative: 'Negative',
  };

  return labels[response];
}

async function loadMailerSettings(): Promise<void> {
  try {
    mailerSettings.value = await fetchMailerSettings();
  } catch {
    mailerSettings.value = null;
  }
}

async function loadApplications(): Promise<void> {
  isApplicationsLoading.value = true;
  error.value = null;

  try {
    applications.value = await fetchApplications(filters);
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isApplicationsLoading.value = false;
  }
}

function applyFilters(): void {
  filters.page = 1;
  void loadApplications();
}

function resetFilters(): void {
  Object.assign(filters, initialFilters);
  void loadApplications();
}

function updateSort(sort: string): void {
  if (filters.sort === sort) {
    filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';
  } else {
    filters.sort = sort;
    filters.direction = 'asc';
  }

  void loadApplications();
}

async function openEditModal(application: JobApplication): Promise<void> {
  error.value = null;
  notice.value = null;

  try {
    editedApplication.value = await fetchApplication(application.id);
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  }
}

function openCreateModal(): void {
  error.value = null;
  notice.value = null;
  editedApplication.value = null;
  isCreatingApplication.value = true;
}

async function saveApplication(payload: JobApplicationUpdatePayload): Promise<void> {
  isSaving.value = true;
  error.value = null;

  try {
    const wasCreating = isCreatingApplication.value;
    const updated = isCreatingApplication.value
      ? await createApplication(payload)
      : editedApplication.value === null
        ? null
        : await updateApplication(editedApplication.value.id, payload);

    if (updated === null) {
      return;
    }

    editedApplication.value = null;
    isCreatingApplication.value = false;
    selectedApplication.value = updated;
    notice.value = wasCreating ? 'Candidature creee.' : 'Candidature enregistree.';
    await loadApplications();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isSaving.value = false;
  }
}

async function removeSelectedApplication(): Promise<void> {
  if (selectedApplication.value === null) {
    return;
  }

  const confirmed = window.confirm(`Supprimer la candidature ${selectedApplication.value.company} ?`);

  if (!confirmed) {
    return;
  }

  error.value = null;
  notice.value = null;

  try {
    await deleteApplication(selectedApplication.value.id);
    const next = new Set(selectedIds.value);
    next.delete(selectedApplication.value.id);
    selectedIds.value = next;
    selectedApplication.value = null;
    notice.value = 'Candidature supprimee.';
    await loadApplications();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  }
}

async function openPreview(application: JobApplication): Promise<void> {
  isPreviewLoading.value = true;
  error.value = null;
  notice.value = null;

  try {
    preview.value = await fetchApplicationPreview(application.id);
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isPreviewLoading.value = false;
  }
}

async function sendSelectedApplication(): Promise<void> {
  if (selectedApplication.value === null) {
    return;
  }

  isSending.value = true;
  error.value = null;
  notice.value = null;

  try {
    const result = await sendApplication(selectedApplication.value.id);
    notice.value = result.message;
    selectedApplication.value = result.application;
    await loadApplications();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
    await loadApplications();
  } finally {
    isSending.value = false;
  }
}

async function changeApplicationResponse(application: JobApplication, response: JobApplication['response']): Promise<void> {
  isSaving.value = true;
  error.value = null;
  notice.value = null;

  try {
    const updated = await updateApplication(application.id, {
      company: application.company,
      location: application.location,
      email: application.email,
      subject: application.subject,
      custom_message: application.custom_message,
      official_source_url: application.official_source_url,
      response,
      follow_up: application.follow_up,
    });

    selectedApplication.value = updated;
    notice.value = response === 'positive' ? 'Reponse marquee positive.' : 'Reponse marquee negative.';
    await loadApplications();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isSaving.value = false;
  }
}

async function sendFollowUp(application: JobApplication): Promise<void> {
  isSending.value = true;
  error.value = null;
  notice.value = null;
  selectedApplication.value = application;

  try {
    const result = await sendFollowUpApplication(application.id);
    notice.value = result.sent ? 'Relance envoyee.' : result.message;
    selectedApplication.value = result.application;
    await loadApplications();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
    await loadApplications();
  } finally {
    isSending.value = false;
  }
}

function toggleSelected(id: string): void {
  const next = new Set(selectedIds.value);

  if (next.has(id)) {
    next.delete(id);
  } else {
    next.add(id);
  }

  selectedIds.value = next;
}

function toggleCurrentPageSelection(): void {
  const currentItems = applications.value?.items ?? [];
  const next = new Set(selectedIds.value);
  const allSelected = currentItems.every((application) => next.has(application.id));

  for (const application of currentItems) {
    if (allSelected) {
      next.delete(application.id);
    } else {
      next.add(application.id);
    }
  }

  selectedIds.value = next;
}

async function queueSelectedApplications(): Promise<void> {
  isSending.value = true;
  error.value = null;
  notice.value = null;

  try {
    const result = await sendSelectedApplications(Array.from(selectedIds.value));
    notice.value = `${result.queued} email(s) mis en file sur une limite de ${result.limit}.`;
    selectedIds.value = new Set();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isSending.value = false;
  }
}

async function queueAllApplications(): Promise<void> {
  isSending.value = true;
  error.value = null;
  notice.value = null;

  try {
    const result = await sendAllApplications();
    notice.value = `${result.queued} email(s) en attente mis en file sur une limite de ${result.limit}.`;
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isSending.value = false;
  }
}

async function retryFailed(): Promise<void> {
  isSending.value = true;
  error.value = null;
  notice.value = null;

  try {
    const result = await retryFailedApplications();
    notice.value = `${result.queued} email(s) en echec remis en file sur une limite de ${result.limit}.`;
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isSending.value = false;
  }
}

function exportCsv(): void {
  isExportModalOpen.value = false;
  window.location.href = buildApplicationsCsvExportUrl(filters);
}

function exportJson(): void {
  isExportModalOpen.value = false;
  window.location.href = buildApplicationsJsonExportUrl(filters);
}

async function importJsonFile(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;

  if (file === null) {
    return;
  }

  isImporting.value = true;
  error.value = null;
  notice.value = null;

  try {
    const json = await file.text();
    const result = await importApplicationsJson(json);
    notice.value = `${result.created} candidature(s) importee(s), ${result.skipped} ignoree(s).`;
    input.value = '';
    await loadApplications();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isImporting.value = false;
  }
}

function handleApiLoadingChange(event: Event): void {
  const customEvent = event as CustomEvent<{ isLoading: boolean }>;
  isApiLoading.value = customEvent.detail.isLoading;
}

onMounted(async () => {
  window.addEventListener('api-loading-change', handleApiLoadingChange);

  try {
    health.value = await fetchHealth();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isHealthLoading.value = false;
  }

  await loadApplications();
  await loadMailerSettings();
});

onBeforeUnmount(() => {
  window.removeEventListener('api-loading-change', handleApiLoadingChange);
});

watch(activeView, (view) => {
  if (view === 'applications') {
    void loadMailerSettings();
  }
});
</script>

<template>
  <main class="app-shell" :class="{ 'is-sidebar-collapsed': isSidebarCollapsed }">
    <div v-if="isApiLoading" class="global-loader" role="status" aria-live="polite">
      <span class="loader-spinner" />
      <span>Chargement</span>
    </div>

    <aside class="sidebar" aria-label="Navigation principale">
      <div class="brand-row">
        <div class="brand">
          <span class="brand-mark">CM</span>
          <span class="brand-label">CandidManager</span>
        </div>

        <button
          type="button"
          class="sidebar-toggle"
          :aria-label="isSidebarCollapsed ? 'Deplier le menu' : 'Replier le menu'"
          :title="isSidebarCollapsed ? 'Deplier le menu' : 'Replier le menu'"
          @click="isSidebarCollapsed = !isSidebarCollapsed"
        >
          {{ isSidebarCollapsed ? '>' : '<' }}
        </button>
      </div>

      <nav class="nav-list">
        <button type="button" class="nav-item" :class="{ 'is-active': activeView === 'applications' }" title="Candidatures" @click="activeView = 'applications'"><span class="nav-short">C</span><span class="nav-label">Candidatures</span></button>
        <button type="button" class="nav-item" :class="{ 'is-active': activeView === 'templates' }" title="Templates" @click="activeView = 'templates'"><span class="nav-short">T</span><span class="nav-label">Templates</span></button>
        <button type="button" class="nav-item" :class="{ 'is-active': activeView === 'mailer' }" title="Mailer" @click="activeView = 'mailer'"><span class="nav-short">M</span><span class="nav-label">Mailer</span></button>
        <button type="button" class="nav-item" :class="{ 'is-active': activeView === 'logs' }" title="Logs" @click="activeView = 'logs'"><span class="nav-short">L</span><span class="nav-label">Logs</span></button>
        <button type="button" class="nav-item" :class="{ 'is-active': activeView === 'stats' }" title="Stats" @click="activeView = 'stats'"><span class="nav-short">S</span><span class="nav-label">Stats</span></button>
      </nav>
    </aside>

    <section class="workspace">
      <template v-if="activeView === 'applications'">
      <header class="topbar">
        <div>
          <p class="eyebrow">Socle applicatif</p>
          <h1>Candidatures spontanees</h1>
        </div>
        <div class="api-status" :class="{ 'is-ready': health, 'is-error': error }">
          <span class="status-dot" />
          <span v-if="isHealthLoading">Connexion API</span>
          <span v-else-if="health">{{ health.service }}</span>
          <span v-else>API indisponible</span>
        </div>
      </header>

      <div class="content-grid">
        <section class="panel">
          <h2>Import JSON</h2>
          <label class="drop-zone">
            <input type="file" accept="application/json,.json" :disabled="isImporting" @change="importJsonFile" />
            <span>{{ isImporting ? 'Import en cours' : 'Choisir applications.json' }}</span>
          </label>
        </section>

        <section class="panel">
          <h2>Envoi {{ selectedMailerName }}</h2>
          <dl class="settings-list">
            <div>
              <dt>Transport</dt>
              <dd>{{ selectedMailerTransport }}</dd>
            </div>
            <div>
              <dt>Mailer actif</dt>
              <dd>{{ mailerSettings?.enabled ? 'Oui' : 'Non' }}</dd>
            </div>
            <div>
              <dt>Protection batch</dt>
              <dd>Limite via .env</dd>
            </div>
          </dl>
        </section>
      </div>

      <section class="table-surface">
        <div class="table-toolbar">
          <h2>Destinataires</h2>
          <div class="toolbar-actions">
            <button type="button" @click="openCreateModal">Nouvelle</button>
            <button type="button" :disabled="selectedApplication === null" @click="selectedApplication && openPreview(selectedApplication)">Previsualiser</button>
            <button type="button" :disabled="selectedApplication === null" @click="selectedApplication && openEditModal(selectedApplication)">Editer</button>
            <button type="button" :disabled="selectedApplication === null" class="danger-button" @click="removeSelectedApplication">Supprimer</button>
            <button type="button" :disabled="selectedApplication === null || isSending" @click="sendSelectedApplication">
              {{ isSending ? 'Envoi' : 'Envoyer' }}
            </button>
            <button type="button" :disabled="selectedCount === 0 || isSending" @click="queueSelectedApplications">Envoyer selection</button>
            <button type="button" :disabled="isSending" @click="retryFailed">Reprendre echecs</button>
            <button type="button" @click="isExportModalOpen = true">Exporter</button>
          </div>
        </div>

        <form class="filters-bar" @submit.prevent="applyFilters">
          <label>
            <span>Recherche</span>
            <input v-model="filters.search" type="search" placeholder="Entreprise, ville, email" />
          </label>

          <label>
            <span>Envoi</span>
            <select v-model="filters.sendStatus">
              <option value="">Tous</option>
              <option value="pending">En attente</option>
              <option value="sending">En cours</option>
              <option value="sent">Envoye</option>
              <option value="failed">Echec</option>
            </select>
          </label>

          <label>
            <span>Reponse</span>
            <select v-model="filters.response">
              <option value="">Toutes</option>
              <option value="none">Aucune</option>
              <option value="pending">En attente</option>
              <option value="positive">Positive</option>
              <option value="negative">Negative</option>
            </select>
          </label>

          <label>
            <span>Relance</span>
            <select v-model="filters.followUp">
              <option value="">Toutes</option>
              <option value="true">Oui</option>
              <option value="false">Non</option>
            </select>
          </label>

          <div class="filter-actions">
            <button type="submit">Filtrer</button>
            <button type="button" class="ghost-button" @click="resetFilters">Reset</button>
          </div>
        </form>

        <p v-if="error" class="error-message">{{ error }}</p>
        <p v-if="notice" class="notice-message">{{ notice }}</p>

        <table>
          <thead>
            <tr>
              <th><input type="checkbox" :checked="hasApplications && applications?.items.every((application) => selectedIds.has(application.id))" @change="toggleCurrentPageSelection" /></th>
              <th><button type="button" class="sort-button" @click="updateSort('company')">Entreprise</button></th>
              <th><button type="button" class="sort-button" @click="updateSort('location')">Localisation</button></th>
              <th><button type="button" class="sort-button" @click="updateSort('email')">Email</button></th>
              <th>Sujet</th>
              <th><button type="button" class="sort-button" @click="updateSort('send_status')">Statut</button></th>
              <th>Reponse</th>
              <th>Options</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isApplicationsLoading">
              <td colspan="8">Chargement des candidatures</td>
            </tr>
            <tr v-else-if="!hasApplications">
              <td colspan="8">Aucune candidature importee</td>
            </tr>
            <tr
              v-for="application in applications?.items"
              v-else
              :key="application.id"
              :class="{ 'is-selected': selectedApplication?.id === application.id }"
              @click="selectedApplication = application"
            >
              <td @click.stop>
                <input type="checkbox" :checked="selectedIds.has(application.id)" @change="toggleSelected(application.id)" />
              </td>
              <td>{{ application.company }}</td>
              <td>{{ application.location }}</td>
              <td>{{ application.email }}</td>
              <td>{{ application.subject }}</td>
              <td><span class="status-badge" :class="`is-${application.send_status}`">{{ sendStatusLabel(application.send_status) }}</span></td>
              <td>
                <span class="response-badge" :class="`is-${application.response}`">
                  <span v-if="application.response === 'negative'" class="negative-response-icon" aria-hidden="true">x</span>
                  {{ responseLabel(application.response) }}
                </span>
              </td>
              <td class="options-cell">
                <button
                  v-if="application.send_status === 'pending' && application.response === 'none'"
                  type="button"
                  class="mail-preview-button"
                  aria-label="Previsualiser l'email"
                  @click.stop="selectedApplication = application; openPreview(application)"
                >
                  &#9993;
                </button>
                <template v-else-if="application.send_status === 'sent' && application.response === 'none'">
                  <button
                    type="button"
                    class="option-icon-button is-negative"
                    :disabled="isSaving"
                    aria-label="Marquer la reponse negative"
                    title="Marquer negative"
                    @click.stop="changeApplicationResponse(application, 'negative')"
                  >
                    x
                  </button>
                  <button
                    type="button"
                    class="option-icon-button is-positive"
                    :disabled="isSaving"
                    aria-label="Marquer la reponse positive"
                    title="Marquer positive"
                    @click.stop="changeApplicationResponse(application, 'positive')"
                  >
                    +
                  </button>
                  <button
                    v-if="canSendFollowUp(application)"
                    type="button"
                    class="follow-up-count-button"
                    :disabled="isSending"
                    :aria-label="`Envoyer une relance. ${application.follow_up_count} relance(s) effectuee(s)`"
                    :title="`Envoyer une relance. ${application.follow_up_count} relance(s) effectuee(s)`"
                    @click.stop="sendFollowUp(application)"
                  >
                    {{ application.follow_up_count }}
                  </button>
                </template>
                <button
                  v-else-if="application.send_status === 'sent' && application.response === 'pending' && canSendFollowUp(application)"
                  type="button"
                  class="follow-up-count-button"
                  :aria-label="`${application.follow_up_count} relance(s) effectuee(s)`"
                  :title="`${application.follow_up_count} relance(s) effectuee(s)`"
                  @click.stop
                >
                  {{ application.follow_up_count }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <footer class="pagination-bar">
          <span>{{ applications?.total ?? 0 }} candidature(s)</span>
          <div>
            <button type="button" :disabled="filters.page <= 1" @click="filters.page--; loadApplications()">Precedent</button>
            <span>Page {{ applications?.page ?? 1 }} / {{ applications?.pages ?? 1 }}</span>
            <button type="button" :disabled="filters.page >= (applications?.pages ?? 1)" @click="filters.page++; loadApplications()">Suivant</button>
          </div>
        </footer>
      </section>

      <ApplicationEditModal
        v-if="editedApplication || isCreatingApplication"
        :application="editedApplication"
        :title="isCreatingApplication ? 'Nouvelle candidature' : 'Editer la candidature'"
        :is-saving="isSaving"
        @close="editedApplication = null; isCreatingApplication = false"
        @save="saveApplication"
      />

      <ApplicationPreviewDrawer
        v-if="preview"
        :preview="preview"
        :is-loading="isPreviewLoading"
        @close="preview = null"
        @send="preview = null; sendSelectedApplication()"
      />

      <div v-if="isExportModalOpen" class="modal-backdrop" role="presentation" @click.self="isExportModalOpen = false">
        <section class="modal export-modal" role="dialog" aria-modal="true" aria-labelledby="export-modal-title">
          <header class="modal-header">
            <h2 id="export-modal-title">Exporter les candidatures</h2>
            <button type="button" class="ghost-button" @click="isExportModalOpen = false">Fermer</button>
          </header>

          <div class="export-format-list">
            <button type="button" class="export-format-button" @click="exportCsv">
              <span>CSV</span>
            </button>
            <button type="button" class="export-format-button" @click="exportJson">
              <span>JSON</span>
            </button>
          </div>
        </section>
      </div>
      </template>

      <TemplatesPanel v-else-if="activeView === 'templates'" />

      <MailerSettingsPanel v-else-if="activeView === 'mailer'" />

      <LogsPanel v-else-if="activeView === 'logs'" />

      <StatsPanel v-else />
    </section>
  </main>
</template>
