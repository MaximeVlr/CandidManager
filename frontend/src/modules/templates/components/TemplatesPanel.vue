<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { createTemplate, deleteTemplate, fetchTemplate, fetchTemplateCategories, fetchTemplateCvPreview, fetchTemplates, updateTemplate, uploadTemplateCv } from '../api';
import type { MailTemplate, MailTemplateCategory, TemplateCreatePayload } from '../types';

const templates = ref<MailTemplate[]>([]);
const categories = ref<MailTemplateCategory[]>([]);
const allowedVariables = ref<string[]>([]);
const selectedId = ref<string | null>(null);
const search = ref('');
const isLoading = ref(true);
const isOpening = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const isUploadingCv = ref(false);
const isEditorOpen = ref(false);
const editingName = ref<string | null>(null);
const currentCvName = ref<string | null>(null);
const pendingCv = ref<File | null>(null);
const cvPreviewUrl = ref<string | null>(null);
const isPdfPreview = ref(false);
const isPreviewLoading = ref(false);
const error = ref<string | null>(null);
const notice = ref<string | null>(null);
const editorError = ref<string | null>(null);
const editorNotice = ref<string | null>(null);

const form = reactive<TemplateCreatePayload>({ name: '', html_body: '', text_body: '', category_id: null });
const selectedTemplate = computed(() => templates.value.find((item) => item.id === selectedId.value) ?? null);
const hasCvForPreview = computed(() => pendingCv.value !== null || currentCvName.value !== null);
const filteredTemplates = computed(() => {
  const query = search.value.trim().toLocaleLowerCase('fr');
  if (query === '') return templates.value;
  return templates.value.filter((item) => item.name.toLocaleLowerCase('fr').includes(query)
    || categoryName(item.category_id).toLocaleLowerCase('fr').includes(query));
});

function categoryName(id: string | null): string {
  if (id === null) return 'Sans catégorie';
  return categories.value.find((category) => category.id === id)?.name ?? 'Catégorie inconnue';
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function formatVariable(variable: string): string {
  return `{{ ${variable} }}`;
}

async function loadTemplates(): Promise<void> {
  isLoading.value = true;
  error.value = null;
  try {
    const [response, categoryItems] = await Promise.all([fetchTemplates(), fetchTemplateCategories()]);
    templates.value = response.items;
    allowedVariables.value = response.allowed_variables;
    categories.value = categoryItems;
    if (!templates.value.some((item) => item.id === selectedId.value)) selectedId.value = null;
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isLoading.value = false;
  }
}

function openCreate(): void {
  closeCvPreview();
  editingName.value = null;
  currentCvName.value = null;
  pendingCv.value = null;
  Object.assign(form, { name: '', html_body: '', text_body: '', category_id: null });
  editorError.value = null;
  editorNotice.value = null;
  isEditorOpen.value = true;
}

async function openEdit(): Promise<void> {
  if (selectedTemplate.value === null) return;
  isOpening.value = true;
  error.value = null;
  try {
    const template = await fetchTemplate(selectedTemplate.value.name);
    closeCvPreview();
    editingName.value = template.name;
    currentCvName.value = template.cv?.original_name ?? null;
    pendingCv.value = null;
    Object.assign(form, {
      name: template.name,
      html_body: template.html_body,
      text_body: template.text_body,
      category_id: template.category_id,
    });
    editorError.value = null;
    editorNotice.value = null;
    isEditorOpen.value = true;
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isOpening.value = false;
  }
}

function closeEditor(): void {
  if (!isSaving.value && !isUploadingCv.value) {
    closeCvPreview();
    pendingCv.value = null;
    isEditorOpen.value = false;
  }
}

function closeCvPreview(): void {
  if (cvPreviewUrl.value !== null) {
    URL.revokeObjectURL(cvPreviewUrl.value);
    cvPreviewUrl.value = null;
  }
  isPdfPreview.value = false;
}

async function openCvPreview(): Promise<void> {
  if (!hasCvForPreview.value || isPreviewLoading.value) return;
  closeCvPreview();
  isPreviewLoading.value = true;
  editorError.value = null;
  try {
    const file = pendingCv.value ?? (editingName.value !== null ? await fetchTemplateCvPreview(editingName.value) : null);
    if (file === null) return;

    const supportedTypes = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    if (file.type !== '' && !supportedTypes.includes(file.type)) {
      throw new Error('Le CV doit être un fichier PDF, DOC ou DOCX.');
    }

    const header = new Uint8Array(await file.slice(0, 5).arrayBuffer());
    const hasPdfHeader = header.length === 5 && String.fromCharCode(...header) === '%PDF-';
    if (file.type === 'application/pdf' && !hasPdfHeader) {
      throw new Error('Le fichier PDF est invalide.');
    }

    isPdfPreview.value = hasPdfHeader && (file.type === '' || file.type === 'application/pdf');
    const preview = isPdfPreview.value ? new Blob([file], { type: 'application/pdf' }) : file;
    cvPreviewUrl.value = URL.createObjectURL(preview);
  } catch (caughtError) {
    editorError.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isPreviewLoading.value = false;
  }
}

async function save(): Promise<void> {
  isSaving.value = true;
  editorError.value = null;
  editorNotice.value = null;
  let createdTemplate: MailTemplate | null = null;
  try {
    const created = editingName.value === null;
    const payload = { ...form, name: form.name.trim() };
    let saved = created
      ? await createTemplate(payload)
      : await updateTemplate(editingName.value as string, {
        html_body: payload.html_body, text_body: payload.text_body, category_id: payload.category_id,
      });
    if (created) {
      createdTemplate = saved;
      editingName.value = saved.name;
      selectedId.value = saved.id;
    }
    if (pendingCv.value !== null) {
      saved = await uploadTemplateCv(saved.name, pendingCv.value);
      pendingCv.value = null;
      currentCvName.value = saved.cv?.original_name ?? null;
    }
    closeCvPreview();
    isEditorOpen.value = false;
    selectedId.value = saved.id;
    notice.value = created ? 'Template créé.' : 'Template enregistré.';
    await loadTemplates();
  } catch (caughtError) {
    const message = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
    if (createdTemplate !== null) {
      templates.value = [...templates.value, createdTemplate].sort((first, second) => first.name.localeCompare(second.name));
      editorError.value = `Template créé, mais le CV n'a pas été associé : ${message}`;
    } else {
      editorError.value = message;
    }
  } finally {
    isSaving.value = false;
  }
}

async function removeSelected(): Promise<void> {
  if (selectedTemplate.value === null || !window.confirm(`Supprimer le template ${selectedTemplate.value.name} ?`)) return;
  isDeleting.value = true;
  error.value = null;
  notice.value = null;
  try {
    await deleteTemplate(selectedTemplate.value.name);
    selectedId.value = null;
    notice.value = 'Template supprimé.';
    await loadTemplates();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isDeleting.value = false;
  }
}

async function selectCv(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;
  if (file === null) return;
  closeCvPreview();
  if (editingName.value === null) {
    pendingCv.value = file;
    editorError.value = null;
    input.value = '';
    return;
  }
  isUploadingCv.value = true;
  editorError.value = null;
  editorNotice.value = null;
  try {
    const updated = await uploadTemplateCv(editingName.value, file);
    pendingCv.value = null;
    currentCvName.value = updated.cv?.original_name ?? null;
    templates.value = templates.value.map((item) => item.id === updated.id ? updated : item);
    editorNotice.value = 'CV associé au template.';
  } catch (caughtError) {
    editorError.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isUploadingCv.value = false;
    input.value = '';
  }
}

onMounted(loadTemplates);
onBeforeUnmount(closeCvPreview);
</script>

<template>
  <section class="templates-layout">
    <header class="topbar">
      <div>
        <p class="eyebrow">Templates</p>
        <h1>Templates d'email</h1>
      </div>
    </header>

    <section class="table-surface template-table">
      <div class="table-toolbar">
        <h2>Liste des templates</h2>
        <div class="toolbar-actions">
          <button type="button" :disabled="isLoading" @click="openCreate">Nouveau</button>
          <button type="button" :disabled="selectedTemplate === null || isOpening || isDeleting" @click="openEdit">{{ isOpening ? 'Ouverture' : 'Editer' }}</button>
          <button type="button" class="danger-button" :disabled="selectedTemplate === null || isDeleting" @click="removeSelected">{{ isDeleting ? 'Suppression' : 'Supprimer' }}</button>
        </div>
      </div>

      <div class="filters-bar template-filters">
        <label>
          <span>Recherche</span>
          <input v-model="search" type="search" placeholder="Nom ou catégorie" />
        </label>
      </div>

      <p v-if="error" class="error-message" role="alert">{{ error }}</p>
      <p v-if="notice" class="notice-message" role="status">{{ notice }}</p>

      <table>
        <thead><tr><th>Nom</th><th>Catégorie</th><th>CV associé</th><th>Dernière modification</th></tr></thead>
        <tbody>
          <tr v-if="isLoading"><td colspan="4">Chargement des templates</td></tr>
          <tr v-else-if="filteredTemplates.length === 0"><td colspan="4">Aucun template trouvé</td></tr>
          <tr
            v-for="item in filteredTemplates"
            v-else
            :key="item.id"
            :class="{ 'is-selected': selectedId === item.id }"
            tabindex="0"
            :aria-selected="selectedId === item.id"
            @click="selectedId = item.id"
            @keydown.enter="selectedId = item.id"
            @keydown.space.prevent="selectedId = item.id"
          >
            <td>{{ item.name }}</td>
            <td>{{ categoryName(item.category_id) }}</td>
            <td>{{ item.cv?.original_name ?? 'Aucun' }}</td>
            <td>{{ formatDate(item.updated_at) }}</td>
          </tr>
        </tbody>
      </table>
      <footer class="pagination-bar"><span>{{ filteredTemplates.length }} template(s)</span></footer>
    </section>

    <div v-if="isEditorOpen" class="modal-backdrop" role="presentation" @click.self="closeEditor">
      <form class="modal template-editor-modal" @submit.prevent="save">
        <header class="modal-header">
          <h2>{{ editingName === null ? 'Nouveau template' : `Editer ${editingName}` }}</h2>
          <button type="button" class="ghost-button" @click="closeEditor">Fermer</button>
        </header>

        <p v-if="editorError" class="error-message" role="alert">{{ editorError }}</p>
        <p v-if="editorNotice" class="notice-message" role="status">{{ editorNotice }}</p>

        <div class="form-grid">
          <label>
            <span>Nom</span>
            <input v-model="form.name" required maxlength="120" :readonly="editingName !== null" placeholder="Ex. candidature_stage" />
          </label>
          <label>
            <span>Catégorie</span>
            <select v-model="form.category_id">
              <option :value="null">Sans catégorie</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </label>
        </div>

        <div class="template-cv-row template-editor-cv">
          <div>
            <h2>CV associé</h2>
            <p class="muted-text">{{ pendingCv?.name ?? currentCvName ?? 'Aucun CV associé' }}</p>
          </div>
          <div class="template-cv-actions">
            <button type="button" class="ghost-button" :disabled="!hasCvForPreview || isPreviewLoading || isUploadingCv || isSaving" @click="openCvPreview">
              {{ isPreviewLoading ? 'Chargement' : 'Aperçu du CV' }}
            </button>
            <label class="file-action">
              <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" :disabled="isUploadingCv || isSaving" @change="selectCv" />
              <span>{{ isUploadingCv ? 'Envoi' : 'Associer un CV' }}</span>
            </label>
          </div>
        </div>

        <div class="variables-strip template-editor-variables">
          <span v-for="variable in allowedVariables" :key="variable">{{ formatVariable(variable) }}</span>
        </div>
        <div class="editor-grid template-editor-grid">
          <label class="editor-panel"><span>HTML</span><textarea v-model="form.html_body" required rows="12" /></label>
          <label class="editor-panel"><span>Texte</span><textarea v-model="form.text_body" required rows="12" /></label>
        </div>
        <section class="preview-panel template-editor-preview">
          <h2>Aperçu HTML</h2>
          <iframe class="html-preview-frame" title="Aperçu du template HTML" sandbox="" :srcdoc="form.html_body" />
        </section>
        <footer class="modal-actions">
          <button type="button" class="ghost-button" @click="closeEditor">Annuler</button>
          <button type="submit" :disabled="isSaving || isUploadingCv">{{ isSaving ? 'Enregistrement' : 'Enregistrer' }}</button>
        </footer>
      </form>
    </div>

    <div v-if="cvPreviewUrl !== null" class="modal-backdrop" role="presentation" @click.self="closeCvPreview">
      <section class="modal cv-preview-modal" role="dialog" aria-modal="true" aria-labelledby="cv-preview-title" @keydown.esc="closeCvPreview">
        <header class="modal-header">
          <h2 id="cv-preview-title">Aperçu du CV</h2>
          <button type="button" class="ghost-button" @click="closeCvPreview">Fermer</button>
        </header>
        <iframe class="cv-preview-frame" :src="cvPreviewUrl" title="Document CV" :sandbox="isPdfPreview ? undefined : ''" />
        <p class="muted-text">L'affichage des fichiers DOC et DOCX dépend du navigateur.</p>
      </section>
    </div>
  </section>
</template>
