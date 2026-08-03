<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { fetchTemplates, updateTemplate, uploadTemplateCv } from '../api';
import type { MailTemplate, TemplateUpdatePayload } from '../types';

type TemplateName = 'default' | 'follow_up';

const template = ref<MailTemplate | null>(null);
const templates = ref<MailTemplate[]>([]);
const selectedTemplateName = ref<TemplateName>('default');
const allowedVariables = ref<string[]>([]);
const isLoading = ref(true);
const isSaving = ref(false);
const isUploadingCv = ref(false);
const error = ref<string | null>(null);
const notice = ref<string | null>(null);

const form = reactive<TemplateUpdatePayload>({
  html_body: '',
  text_body: '',
});

const previewHtml = computed(() => form.html_body);
const templateOptions: Array<{ name: TemplateName; label: string }> = [
  { name: 'default', label: 'Premiere candidature' },
  { name: 'follow_up', label: 'Relance' },
];

function formatVariable(variable: string): string {
  return `{{ ${variable} }}`;
}

async function loadTemplates(): Promise<void> {
  isLoading.value = true;
  error.value = null;

  try {
    const response = await fetchTemplates();
    templates.value = response.items;
    template.value = findSelectedTemplate();
    allowedVariables.value = response.allowed_variables;
    form.html_body = template.value?.html_body ?? '';
    form.text_body = template.value?.text_body ?? '';
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isLoading.value = false;
  }
}

function findSelectedTemplate(): MailTemplate | null {
  return templates.value.find((item) => item.name === selectedTemplateName.value) ?? null;
}

function selectTemplate(): void {
  template.value = findSelectedTemplate();
  form.html_body = template.value?.html_body ?? '';
  form.text_body = template.value?.text_body ?? '';
  error.value = null;
  notice.value = null;
}

async function save(): Promise<void> {
  isSaving.value = true;
  error.value = null;
  notice.value = null;

  try {
    template.value = await updateTemplate(selectedTemplateName.value, { ...form });
    const index = templates.value.findIndex((item) => item.name === selectedTemplateName.value);
    if (index === -1) {
      templates.value = [...templates.value, template.value];
    } else {
      templates.value = templates.value.map((item) => item.name === selectedTemplateName.value ? template.value as MailTemplate : item);
    }
    notice.value = 'Template sauvegarde.';
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isSaving.value = false;
  }
}

async function uploadCv(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;

  if (file === null) {
    return;
  }

  isUploadingCv.value = true;
  error.value = null;
  notice.value = null;

  try {
    template.value = await uploadTemplateCv(selectedTemplateName.value, file);
    templates.value = templates.value.map((item) => item.name === selectedTemplateName.value ? template.value as MailTemplate : item);
    notice.value = 'CV associe au template.';
    input.value = '';
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isUploadingCv.value = false;
  }
}

onMounted(loadTemplates);
</script>

<template>
  <section class="templates-layout">
    <header class="section-header">
      <div>
        <p class="eyebrow">Templates</p>
        <h1>Email HTML et texte</h1>
      </div>
      <button type="button" :disabled="isSaving || isLoading" @click="save">
        {{ isSaving ? 'Sauvegarde' : 'Sauvegarder' }}
      </button>
    </header>

    <p v-if="error" class="error-message">{{ error }}</p>
    <p v-if="notice" class="notice-message">{{ notice }}</p>

    <section class="panel">
      <div class="template-cv-row">
        <label class="template-select">
          <span>Type de template</span>
          <select v-model="selectedTemplateName" :disabled="isLoading || isSaving" @change="selectTemplate">
            <option v-for="option in templateOptions" :key="option.name" :value="option.name">{{ option.label }}</option>
          </select>
        </label>

        <div>
          <h2>CV associe</h2>
          <p class="muted-text">{{ template?.cv?.original_name ?? 'Aucun CV associe' }}</p>
        </div>
        <label class="file-action">
          <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" :disabled="isUploadingCv || isLoading" @change="uploadCv" />
          <span>{{ isUploadingCv ? 'Upload' : 'Associer un CV' }}</span>
        </label>
      </div>
    </section>

    <div class="variables-strip">
      <span v-for="variable in allowedVariables" :key="variable">{{ formatVariable(variable) }}</span>
    </div>

    <div class="editor-grid">
      <label class="editor-panel">
        <span>HTML</span>
        <textarea v-model="form.html_body" :disabled="isLoading" rows="18" />
      </label>

      <label class="editor-panel">
        <span>Texte</span>
        <textarea v-model="form.text_body" :disabled="isLoading" rows="18" />
      </label>
    </div>

    <section class="preview-panel">
      <h2>Apercu HTML brut</h2>
      <div class="html-preview" v-html="previewHtml" />
    </section>
  </section>
</template>
