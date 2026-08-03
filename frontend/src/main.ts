import { createApp } from 'vue';
import App from './App.vue';
import { installApiLoadingIndicator } from './apiLoading';
import './styles.css';

installApiLoadingIndicator();

createApp(App).mount('#app');
