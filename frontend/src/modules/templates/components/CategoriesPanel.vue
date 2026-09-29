<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { createTemplateCategory, deleteTemplateCategory, fetchTemplateCategories, fetchTemplateCategory, fetchTemplates, updateTemplateCategory } from '../api';
import type { MailTemplateCategory } from '../types';

const categories = ref<MailTemplateCategory[]>([]);
const templateCounts = ref<Record<string, number>>({});
const selectedId = ref<string | null>(null);
const search = ref('');
const isLoading = ref(true);
const isOpening = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const isEditorOpen = ref(false);
const editingId = ref<string | null>(null);
const name = ref('');
const error = ref<string | null>(null);
const notice = ref<string | null>(null);
const editorError = ref<string | null>(null);

const selectedCategory = computed(() => categories.value.find((category) => category.id === selectedId.value) ?? null);
const filteredCategories = computed(() => {
  const query = search.value.trim().toLocaleLowerCase('fr');
  return query === '' ? categories.value : categories.value.filter((category) => category.name.toLocaleLowerCase('fr').includes(query));
});

async function loadCategories(): Promise<void> {
  isLoading.value = true;
  error.value = null;
  try {
    const [categoryItems, templates] = await Promise.all([fetchTemplateCategories(), fetchTemplates()]);
    categories.value = categoryItems;
    templateCounts.value = templates.items.reduce<Record<string, number>>((counts, template) => {
      if (template.category_id !== null) counts[template.category_id] = (counts[template.category_id] ?? 0) + 1;
      return counts;
    }, {});
    if (!categories.value.some((category) => category.id === selectedId.value)) selectedId.value = null;
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isLoading.value = false;
  }
}

function openCreate(): void {
  editingId.value = null;
  name.value = '';
  editorError.value = null;
  isEditorOpen.value = true;
}

async function openEdit(): Promise<void> {
  if (selectedCategory.value === null) return;
  isOpening.value = true;
  error.value = null;
  try {
    const category = await fetchTemplateCategory(selectedCategory.value.id);
    editingId.value = category.id;
    name.value = category.name;
    editorError.value = null;
    isEditorOpen.value = true;
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isOpening.value = false;
  }
}

function closeEditor(): void {
  if (!isSaving.value) isEditorOpen.value = false;
}

async function save(): Promise<void> {
  isSaving.value = true;
  editorError.value = null;
  try {
    const created = editingId.value === null;
    const saved = created
      ? await createTemplateCategory(name.value.trim())
      : await updateTemplateCategory(editingId.value as string, name.value.trim());
    isEditorOpen.value = false;
    selectedId.value = saved.id;
    notice.value = created ? 'Catégorie créée.' : 'Catégorie enregistrée.';
    await loadCategories();
  } catch (caughtError) {
    editorError.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isSaving.value = false;
  }
}

async function removeSelected(): Promise<void> {
  if (selectedCategory.value === null) return;
  const count = templateCounts.value[selectedCategory.value.id] ?? 0;
  const confirmation = count > 0
    ? `Supprimer la catégorie ${selectedCategory.value.name} ? Ses ${count} template(s) resteront sans catégorie.`
    : `Supprimer la catégorie ${selectedCategory.value.name} ?`;
  if (!window.confirm(confirmation)) return;

  isDeleting.value = true;
  error.value = null;
  notice.value = null;
  try {
    await deleteTemplateCategory(selectedCategory.value.id);
    selectedId.value = null;
    notice.value = 'Catégorie supprimée.';
    await loadCategories();
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isDeleting.value = false;
  }
}

onMounted(loadCategories);
</script>

<template>
  <section class="templates-layout">
    <header class="topbar">
      <div>
        <p class="eyebrow">Email</p>
        <h1>Catégories de templates</h1>
      </div>
    </header>

    <section class="table-surface template-table">
      <div class="table-toolbar">
        <h2>Liste des catégories</h2>
        <div class="toolbar-actions">
          <button type="button" :disabled="isLoading" @click="openCreate">Nouveau</button>
          <button type="button" :disabled="selectedCategory === null || isOpening || isDeleting" @click="openEdit">{{ isOpening ? 'Ouverture' : 'Editer' }}</button>
          <button type="button" class="danger-button" :disabled="selectedCategory === null || isDeleting" @click="removeSelected">{{ isDeleting ? 'Suppression' : 'Supprimer' }}</button>
        </div>
      </div>

      <div class="filters-bar template-filters">
        <label><span>Recherche</span><input v-model="search" type="search" placeholder="Nom de catégorie" /></label>
      </div>

      <p v-if="error" class="error-message" role="alert">{{ error }}</p>
      <p v-if="notice" class="notice-message" role="status">{{ notice }}</p>

      <table>
        <thead><tr><th>Nom</th><th>Templates associés</th></tr></thead>
        <tbody>
          <tr v-if="isLoading"><td colspan="2">Chargement des catégories</td></tr>
          <tr v-else-if="filteredCategories.length === 0"><td colspan="2">Aucune catégorie trouvée</td></tr>
          <tr
            v-for="category in filteredCategories"
            v-else
            :key="category.id"
            :class="{ 'is-selected': selectedId === category.id }"
            tabindex="0"
            :aria-selected="selectedId === category.id"
            @click="selectedId = category.id"
            @keydown.enter="selectedId = category.id"
            @keydown.space.prevent="selectedId = category.id"
          >
            <td>{{ category.name }}</td>
            <td>{{ templateCounts[category.id] ?? 0 }}</td>
          </tr>
        </tbody>
      </table>
      <footer class="pagination-bar"><span>{{ filteredCategories.length }} catégorie(s)</span></footer>
    </section>

    <div v-if="isEditorOpen" class="modal-backdrop" role="presentation" @click.self="closeEditor">
      <form class="modal category-editor-modal" @submit.prevent="save">
        <header class="modal-header">
          <h2>{{ editingId === null ? 'Nouvelle catégorie' : 'Editer la catégorie' }}</h2>
          <button type="button" class="ghost-button" @click="closeEditor">Fermer</button>
        </header>
        <p v-if="editorError" class="error-message" role="alert">{{ editorError }}</p>
        <div class="form-grid category-editor-form">
          <label class="is-wide"><span>Nom</span><input v-model="name" required maxlength="120" autofocus /></label>
        </div>
        <footer class="modal-actions">
          <button type="button" class="ghost-button" @click="closeEditor">Annuler</button>
          <button type="submit" :disabled="isSaving">{{ isSaving ? 'Enregistrement' : 'Enregistrer' }}</button>
        </footer>
      </form>
    </div>
  </section>
</template>
