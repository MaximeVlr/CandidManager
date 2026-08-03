<script setup lang="ts">
import type { ApplicationPreview } from '../types';

defineProps<{
  preview: ApplicationPreview;
  isLoading: boolean;
}>();

const emit = defineEmits<{
  close: [];
  send: [];
}>();
</script>

<template>
  <aside class="preview-drawer" aria-label="Previsualisation email">
    <header class="drawer-header">
      <div>
        <p class="eyebrow">Previsualisation</p>
        <h2>{{ preview.subject }}</h2>
      </div>
      <button type="button" class="ghost-button" @click="emit('close')">Fermer</button>
    </header>

    <div v-if="isLoading" class="drawer-loading">Chargement</div>

    <template v-else>
      <section class="preview-section">
        <h3>HTML</h3>
        <div class="mail-preview" v-html="preview.html_body" />
      </section>

      <section class="preview-section">
        <h3>Texte</h3>
        <pre>{{ preview.text_body }}</pre>
      </section>
    </template>

    <footer class="drawer-actions">
      <button type="button" class="ghost-button" @click="emit('close')">Annuler</button>
      <button type="button" :disabled="isLoading" @click="emit('send')">Envoyer</button>
    </footer>
  </aside>
</template>
