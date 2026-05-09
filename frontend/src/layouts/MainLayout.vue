<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="psicol-header">
      <q-toolbar class="psicol-toolbar">
        <q-btn flat dense round icon="menu" aria-label="Menú" class="hdr-btn" @click="leftDrawerOpen = !leftDrawerOpen" />

        <q-toolbar-title class="row items-center no-wrap" style="gap:6px">
          <span class="brand-dot" />
          <span class="brand-name">PsiCol</span>
          <span class="brand-sep gt-xs">·</span>
          <span class="brand-sub gt-xs">Incidentes</span>
        </q-toolbar-title>

        <div v-if="authStore.user" class="user-pill gt-xs">
          <div class="user-av">{{ authStore.user.name.charAt(0).toUpperCase() }}</div>
          <span class="user-name">{{ authStore.user.name }}</span>
          <span v-if="authStore.user.roles?.[0]" class="user-role" :class="authStore.user.roles[0]">
            {{ authStore.user.roles[0] }}
          </span>
        </div>

        <q-btn flat round dense icon="logout" class="hdr-btn q-ml-sm" @click="logout">
          <q-tooltip>Cerrar sesión</q-tooltip>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above class="psicol-drawer">
      <div class="nav-wrap">
        <div class="nav-section-label">Principal</div>
        <q-item
          v-for="nav in navItems"
          :key="nav.to"
          :to="nav.to"
          :exact="nav.exact ?? false"
          active-class="nav-active"
          clickable v-ripple
          class="nav-item"
        >
          <q-item-section avatar>
            <q-icon :name="nav.icon" size="18px" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="nav-label">{{ nav.label }}</q-item-label>
            <q-item-label caption class="nav-cap">{{ nav.caption }}</q-item-label>
          </q-item-section>
        </q-item>
      </div>
      <template #append>
        <div class="drawer-footer">v0.1.0</div>
      </template>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'stores/auth';

const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();
const leftDrawerOpen = ref(false);

const navItems = [
  { label: 'Dashboard',  caption: 'Resumen general',       icon: 'space_dashboard',    to: '/dashboard', exact: true },
  { label: 'Incidentes', caption: 'Solicitudes y tickets',  icon: 'confirmation_number', to: '/tickets' },
];

async function logout() {
  try {
    await authStore.logout();
    await router.push('/login');
  } catch {
    $q.notify({ type: 'negative', message: 'Error al cerrar sesión' });
  }
}
</script>

<style scoped>
.psicol-header {
  background: #F9FFFE;
  border-bottom: 1px solid rgba(42, 157, 143, 0.12);
  color: #1C3035;
  box-shadow: none;
}
.psicol-toolbar { min-height: 50px; }

.hdr-btn { color: #5D7E7B; }
.hdr-btn:hover { color: #1C3035; }

.brand-dot {
  display: inline-block;
  width: 8px; height: 8px;
  border-radius: 2px;
  background: #2A9D8F;
  flex-shrink: 0;
}
.brand-name { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; color: #1C3035; }
.brand-sep  { color: #96B4B2; margin: 0 1px; }
.brand-sub  { font-size: 12px; color: #5D7E7B; }

.user-pill {
  display: flex; align-items: center; gap: 5px;
  padding: 3px 8px 3px 4px;
  background: #EFF8F7;
  border: 1px solid rgba(42, 157, 143, 0.14);
  border-radius: 20px;
}
.user-av {
  width: 22px; height: 22px; border-radius: 50%;
  background: #2A9D8F; color: #fff;
  font-size: 10px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.user-name  { font-size: 12px; color: #2D4A47; font-weight: 500; }
.user-role  { font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 1px 5px; border-radius: 3px; }
.user-role.admin    { background: #F0EBFC; color: #7C4DBE; }
.user-role.agente   { background: #EBF7F5; color: #2A9D8F; }
.user-role.paciente { background: #EFF8F7; color: #264653; }

:deep(.psicol-drawer) {
  background: #F9FFFE !important;
  border-right: 1px solid rgba(42, 157, 143, 0.10) !important;
}
.nav-wrap { padding: 12px 8px 8px; }
.nav-section-label {
  font-size: 10px; font-weight: 700; letter-spacing: 0.10em;
  text-transform: uppercase; color: #96B4B2;
  padding: 0 10px 8px;
}
.nav-item {
  border-radius: 6px; margin-bottom: 2px; padding: 7px 10px;
  color: #5D7E7B; border-left: 3px solid transparent;
  transition: background 0.12s, color 0.12s; min-height: unset;
}
.nav-item:hover { background: #EFF8F7; color: #2D4A47; }
.nav-active { background: #EBF7F5 !important; color: #2A9D8F !important; border-left-color: #2A9D8F !important; }
.nav-label  { font-size: 13px; font-weight: 500; color: inherit; }
.nav-cap    { font-size: 11px; color: inherit; opacity: 0.7; }

.drawer-footer {
  padding: 10px 16px; font-size: 11px; color: #96B4B2;
  border-top: 1px solid rgba(42, 157, 143, 0.09); text-align: center;
}
</style>
