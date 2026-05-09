import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from 'boot/axios';

interface User {
  id: number;
  name: string;
  email: string;
  roles: string[];
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'));
  const user = ref<User | null>(null);

  const isAuthenticated = computed(() => !!token.value);
  const isAdmin  = computed(() => user.value?.roles.includes('admin')  ?? false);
  const isAgente = computed(() => user.value?.roles.includes('agente') ?? false);
  const canEdit   = computed(() => isAdmin.value || isAgente.value);
  const canDelete = computed(() => isAdmin.value);

  async function login(email: string, password: string) {
    const { data } = await api.post<{ access_token: string }>('/auth/login', {
      email,
      password,
    });
    token.value = data.access_token;
    localStorage.setItem('token', data.access_token);
    await fetchUser();
  }

  async function fetchUser() {
    const { data } = await api.get<User>('/auth/me');
    user.value = data;
  }

  async function logout() {
    try {
      await api.post('/auth/logout');
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem('token');
    }
  }

  return { token, user, isAuthenticated, isAdmin, isAgente, canEdit, canDelete, login, fetchUser, logout };
});
