<template>
  <q-page class="login-root">
    <div class="login-shell">
      <div class="login-brand">
        
        <span class="login-brand-name">PsiCol</span>
      </div>
      <p class="login-subtitle">Sistema de gestión de incidentes</p>

      <div class="login-card">
        <q-form @submit.prevent="onSubmit" class="q-gutter-sm">
          <q-input
            v-model="form.email"
            label="Correo electrónico"
            type="email"
            outlined dense autofocus
            :rules="[(val) => !!val || 'El correo es requerido']"
          />
          <q-input
            v-model="form.password"
            label="Contraseña"
            :type="showPassword ? 'text' : 'password'"
            outlined dense
            :rules="[(val) => !!val || 'La contraseña es requerida']"
          >
            <template #append>
              <q-icon
                :name="showPassword ? 'visibility_off' : 'visibility'"
                class="cursor-pointer" style="color:#96B4B2"
                @click="showPassword = !showPassword"
              />
            </template>
          </q-input>
          <q-btn
            type="submit" label="Ingresar" color="primary"
            class="full-width q-mt-xs" unelevated :loading="loading"
          />
        </q-form>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'stores/auth';

const $q = useQuasar();
const router = useRouter();
const authStore = useAuthStore();
const loading = ref(false);
const showPassword = ref(false);
const form = ref({ email: '', password: '' });

async function onSubmit() {
  loading.value = true;
  try {
    await authStore.login(form.value.email, form.value.password);
    await router.push('/');
  } catch {
    $q.notify({ type: 'negative', message: 'Credenciales incorrectas' });
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-root {
  display: flex; align-items: center; justify-content: center;
  min-height: 100vh; background: #EFF8F7;
}
.login-shell { width: 100%; max-width: 360px; padding: 0 16px; }
.login-brand { display: flex; align-items: center; gap: 7px; margin-bottom: 3px; }
.login-brand-dot {
  display: inline-block; width: 10px; height: 10px;
  border-radius: 3px; background: #2A9D8F;
}
.login-brand-name { font-size: 22px; font-weight: 700; letter-spacing: -0.02em; color: #1C3035; }
.login-subtitle   { font-size: 12px; color: #96B4B2; margin: 0 0 18px; }
.login-card {
  background: #F9FFFE;
  border: 1px solid rgba(42, 157, 143, 0.14);
  border-radius: 10px;
  padding: 22px 20px;
}
</style>
