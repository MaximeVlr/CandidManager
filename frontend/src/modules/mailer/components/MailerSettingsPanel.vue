<script setup lang="ts">
import { nextTick, onMounted, reactive, ref } from 'vue';
import { fetchMailerSettings, updateMailerSettings } from '../api';
import type { MailerSettings, MailerSettingsPayload } from '../types';

const settings = ref<MailerSettings | null>(null);
const isLoading = ref(true);
const isSaving = ref(false);
const error = ref<string | null>(null);
const notice = ref<string | null>(null);
const isHelpOpen = ref(false);
const helpTrigger = ref<HTMLButtonElement | null>(null);
const helpClose = ref<HTMLButtonElement | null>(null);

async function openHelp(): Promise<void> {
  isHelpOpen.value = true;
  await nextTick();
  helpClose.value?.focus();
}

async function closeHelp(): Promise<void> {
  isHelpOpen.value = false;
  await nextTick();
  helpTrigger.value?.focus();
}

const form = reactive<MailerSettingsPayload>({
  provider: 'gmail',
  enabled: false,
  from_email: '',
  from_name: 'CandidManager',
  username: '',
  password: '',
  host: 'smtp.gmail.com',
  port: 587,
  encryption: 'tls',
});

async function loadSettings(): Promise<void> {
  isLoading.value = true;
  error.value = null;

  try {
    settings.value = await fetchMailerSettings();
    form.provider = settings.value.provider;
    form.enabled = settings.value.enabled;
    form.from_email = settings.value.from_email;
    form.from_name = settings.value.from_name;
    form.username = settings.value.username;
    form.password = '';
    form.host = settings.value.host;
    form.port = settings.value.port;
    form.encryption = settings.value.encryption;
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isLoading.value = false;
  }
}

async function save(): Promise<void> {
  isSaving.value = true;
  error.value = null;
  notice.value = null;

  try {
    settings.value = await updateMailerSettings({ ...form });
    form.password = '';
    notice.value = form.provider === 'gmail' ? 'Configuration Gmail sauvegardee.' : 'Mailer Brevo selectionne.';
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Erreur inconnue';
  } finally {
    isSaving.value = false;
  }
}

function isActiveProvider(provider: MailerSettingsPayload['provider']): boolean {
  return form.provider === provider && form.enabled;
}

function toggleProvider(provider: MailerSettingsPayload['provider'], event: Event): void {
  const checked = event.target instanceof HTMLInputElement ? event.target.checked : false;
  form.provider = provider;
  form.enabled = checked;
}

onMounted(loadSettings);
</script>

<template>
  <section class="mailer-layout">
    <header class="section-header">
      <div>
        <p class="eyebrow">Mailer</p>
        <h1>Configuration mailer</h1>
      </div>
      <button type="button" :disabled="isLoading || isSaving" @click="save">
        {{ isSaving ? 'Sauvegarde' : 'Sauvegarder' }}
      </button>
    </header>

    <p v-if="error" class="error-message">{{ error }}</p>
    <p v-if="notice" class="notice-message">{{ notice }}</p>

    <form class="mailer-choices" @submit.prevent="save">
      <section class="panel mailer-choice" :class="{ 'is-selected': form.provider === 'gmail' }">
        <header class="mailer-choice-header">
          <div>
            <p class="eyebrow">SMTP administre</p>
            <h2>Gmail</h2>
          </div>

          <div class="mailer-choice-actions">
            <button ref="helpTrigger" type="button" class="ghost-button" @click="openHelp">Comment faire ?</button>
            <label class="checkbox-line">
              <input :checked="isActiveProvider('gmail')" type="checkbox" @change="toggleProvider('gmail', $event)" />
              <span>Utiliser ce mailer pour les envois</span>
            </label>
          </div>
        </header>

        <div class="form-grid">
          <label>
            <span>Email expediteur</span>
            <input v-model="form.from_email" type="email" :required="form.provider === 'gmail'" />
          </label>

          <label>
            <span>Nom expediteur</span>
            <input v-model="form.from_name" :required="form.provider === 'gmail'" />
          </label>

          <label>
            <span>Compte Gmail</span>
            <input v-model="form.username" type="email" :required="form.provider === 'gmail'" />
          </label>

          <label>
            <span>Mot de passe application</span>
            <input v-model="form.password" type="password" :placeholder="settings?.has_password ? 'Deja configure' : ''" />
          </label>

          <label>
            <span>Serveur SMTP</span>
            <input v-model="form.host" :required="form.provider === 'gmail'" />
          </label>

          <label>
            <span>Port</span>
            <input v-model.number="form.port" type="number" min="1" max="65535" :required="form.provider === 'gmail'" />
          </label>

          <label>
            <span>Chiffrement</span>
            <select v-model="form.encryption">
              <option value="tls">TLS</option>
              <option value="ssl">SSL</option>
            </select>
          </label>
        </div>
      </section>

      <section class="panel mailer-choice" :class="{ 'is-selected': form.provider === 'brevo' }">
        <header class="mailer-choice-header">
          <div>
            <p class="eyebrow">SMTP environnement</p>
            <h2>Brevo</h2>
          </div>

          <label class="checkbox-line">
            <input :checked="isActiveProvider('brevo')" type="checkbox" @change="toggleProvider('brevo', $event)" />
            <span>Utiliser ce mailer pour les envois</span>
          </label>
        </header>

        <div class="readonly-mailer-panel">
          <p class="muted-text">Les informations Brevo sont gerees uniquement via les variables d'environnement du backend.</p>
        </div>
      </section>
    </form>

    <div v-if="isHelpOpen" class="modal-backdrop" role="presentation" @click.self="closeHelp">
      <section class="modal smtp-help-modal" role="dialog" aria-modal="true" aria-labelledby="smtp-help-title" @keydown.esc="closeHelp">
        <header class="modal-header">
          <h2 id="smtp-help-title">Comment configurer Gmail ?</h2>
          <button ref="helpClose" type="button" class="ghost-button" @click="closeHelp">Fermer</button>
        </header>

        <p>Pour envoyer des e-mails depuis une application avec une adresse Gmail, voici les informations à renseigner :</p>
        <ul class="smtp-help-settings">
          <li>Serveur SMTP : smtp.gmail.com</li>
          <li>Port : 587</li>
          <li>Sécurité : TLS ou STARTTLS</li>
          <li>Authentification : oui</li>
          <li>Nom d’utilisateur : votre adresse Gmail complète</li>
        </ul>

        <p>Ces paramètres sont communs aux comptes Gmail : vous n’avez pas besoin de les chercher dans les réglages de votre boîte mail.</p>
        <p>Pour obtenir le mot de passe à utiliser :</p>
        <ol class="smtp-help-steps">
          <li>Ouvrez <a href="https://myaccount.google.com/security" target="_blank" rel="noopener noreferrer">https://myaccount.google.com/security</a>.</li>
          <li>Activez la validation en deux étapes si elle ne l’est pas déjà.</li>
          <li>Ouvrez <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer">https://myaccount.google.com/apppasswords</a> et connectez-vous si nécessaire.</li>
          <li>Créez un mot de passe d’application, par exemple en le nommant « Mon application ».</li>
          <li>Copiez le code généré et utilisez-le comme mot de passe SMTP. Il remplace votre mot de passe Google habituel dans la configuration de cette application.</li>
        </ol>

        <p>Google n’affiche ce mot de passe d’application qu’une seule fois. Si vous le perdez, créez-en un nouveau. Si l’option « Mots de passe d’application » n’apparaît pas, elle peut être indisponible pour certains comptes professionnels ou scolaires, ou avec certains réglages de sécurité.</p>
      </section>
    </div>
  </section>
</template>
