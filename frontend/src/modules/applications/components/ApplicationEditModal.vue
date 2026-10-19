<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { fetchTemplates } from '../../templates/api';
import type { MailTemplate } from '../../templates/types';
import type { JobApplication, JobApplicationUpdatePayload } from '../types';

const props = defineProps<{
  application: JobApplication | null;
  isSaving: boolean;
  title: string;
}>();

const emit = defineEmits<{
  close: [];
  save: [payload: JobApplicationUpdatePayload];
}>();

const templates = ref<MailTemplate[]>([]);
const isLoadingTemplates = ref(true);
const templatesError = ref<string | null>(null);

async function loadTemplates(): Promise<void> {
  isLoadingTemplates.value = true;
  templatesError.value = null;

  try {
    templates.value = (await fetchTemplates()).items;
  } catch (error) {
    templatesError.value = error instanceof Error ? error.message : 'Impossible de charger les templates.';
  } finally {
    isLoadingTemplates.value = false;
  }
}

onMounted(loadTemplates);

const form = reactive<JobApplicationUpdatePayload>({
  company: '',
  location: '',
  email: '',
  subject: '',
  custom_message: '',
  official_source_url: '',
  response: 'none',
  follow_up: false,
  template_id: null,
});

watch(
  () => props.application,
  (application) => {
    if (application === null) {
      form.company = '';
      form.location = '';
      form.email = '';
      form.subject = '';
      form.custom_message = '';
      form.official_source_url = '';
      form.response = 'none';
      form.follow_up = false;
      form.template_id = null;
      return;
    }

    form.company = application.company;
    form.location = application.location;
    form.email = application.email;
    form.subject = application.subject;
    form.custom_message = application.custom_message;
    form.official_source_url = application.official_source_url;
    form.response = application.response;
    form.follow_up = application.follow_up;
    form.template_id = application.template_id ?? null;
  },
  { immediate: true },
);

function submit(): void {
  emit('save', { ...form });
}
</script>

<template>
  <div class="modal-backdrop" role="presentation" @click.self="emit('close')">
    <form class="modal" @submit.prevent="submit">
      <header class="modal-header">
        <h2>{{ title }}</h2>
        <button type="button" class="ghost-button" @click="emit('close')">Fermer</button>
      </header>

      <div class="form-grid">
        <label>
          <span>Entreprise</span>
          <input v-model="form.company" required maxlength="180" />
        </label>

        <label>
          <span>Localisation</span>
          <input v-model="form.location" required maxlength="120" />
        </label>

        <label>
          <span>Email</span>
          <input v-model="form.email" required maxlength="180" type="email" />
        </label>

        <label>
          <span>Sujet</span>
          <input v-model="form.subject" required maxlength="180" />
        </label>

        <label class="is-wide">
          <span>URL source</span>
          <input v-model="form.official_source_url" required maxlength="2048" type="url" />
        </label>

        <label>
          <span>Reponse</span>
          <select v-model="form.response">
            <option value="none">Aucune</option>
            <option value="pending">En attente</option>
            <option value="positive">Positive</option>
            <option value="negative">Negative</option>
          </select>
        </label>

        <label class="is-wide">
          <span>Template à utiliser</span>
          <select v-model="form.template_id" :disabled="isLoadingTemplates || templatesError !== null || isSaving">
            <option :value="null">Template par défaut</option>
            <option
              v-if="form.template_id && !templates.some((template) => template.id === form.template_id)"
              :value="form.template_id"
              disabled
            >Template actuellement associé</option>
            <option v-for="template in templates" :key="template.id" :value="template.id">
              {{ template.name }}
            </option>
          </select>
          <span v-if="isLoadingTemplates" role="status">Chargement des templates…</span>
          <span v-else-if="templatesError" class="error-message" role="alert">{{ templatesError }}</span>
          <button v-if="templatesError" type="button" class="ghost-button" @click="loadTemplates">Réessayer</button>
        </label>

        <label class="is-wide">
          <span>Message personnalise</span>
          <textarea v-model="form.custom_message" required maxlength="10000" rows="7" />
        </label>
      </div>

      <footer class="modal-actions">
        <button type="button" class="ghost-button" @click="emit('close')">Annuler</button>
        <button type="submit" :disabled="isSaving">
          {{ isSaving ? 'Enregistrement' : 'Enregistrer' }}
        </button>
      </footer>
    </form>
  </div>
</template>
