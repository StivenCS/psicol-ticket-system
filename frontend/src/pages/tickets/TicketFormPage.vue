<template>
  <q-page class="psicol-page q-pa-md">
    <div class="row items-center q-mb-sm" style="gap:6px">
      <q-btn flat round dense icon="arrow_back" style="color:#5D7E7B" @click="router.back()" />
      <h1 class="page-title" style="margin:0">{{ isEdit ? 'Editar ticket' : 'Nuevo ticket' }}</h1>
    </div>

    <div class="form-shell">
      <q-form @submit.prevent="onSubmit" class="q-gutter-sm">
        <q-input v-model="form.title" label="Título *" outlined dense :rules="[(v) => !!v || 'El título es requerido']" />
        <q-input v-model="form.description" label="Descripción *" type="textarea" outlined rows="4" :rules="[(v) => !!v || 'La descripción es requerida']" />

        <div class="row q-gutter-sm">
          <q-select
            v-model="form.priority" :options="priorityOptions" option-value="value" option-label="label"
            emit-value map-options label="Prioridad *" outlined dense class="col"
            :rules="[(v) => !!v || 'La prioridad es requerida']"
          >
            <template #selected-item="{ opt }">
              <span :class="['p-chip', opt.value]">{{ opt.label }}</span>
            </template>
          </q-select>
          <q-select
            v-model="form.status" :options="statusOptions" option-value="value" option-label="label"
            emit-value map-options label="Estado *" outlined dense class="col"
            :rules="[(v) => !!v || 'El estado es requerido']"
          >
            <template #selected-item="{ opt }">
              <span :class="['s-chip', opt.value]">{{ opt.label }}</span>
            </template>
          </q-select>
        </div>

        <q-select
          v-model="form.assigned_to" :options="users" option-value="id" option-label="name"
          emit-value map-options label="Asignar a" outlined dense clearable
        />

        <q-input v-model="form.due_date" label="Fecha de vencimiento" outlined dense clearable>
          <template #append>
            <q-icon name="event" class="cursor-pointer" style="color:#96B4B2">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="form.due_date" mask="YYYY-MM-DD" :options="(d) => d >= today">
                  <div class="row items-center justify-end"><q-btn v-close-popup label="OK" color="primary" flat /></div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>

        <div class="row q-gutter-sm justify-end q-pt-xs">
          <q-btn flat label="Cancelar" style="color:#5D7E7B" @click="router.back()" />
          <q-btn type="submit" color="primary" unelevated
            :label="isEdit ? 'Guardar cambios' : 'Crear ticket'"
            :loading="loading"
          />
        </div>
      </q-form>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { api } from 'boot/axios';
import { useTicketStore, type User } from 'stores/ticket';

const route  = useRoute();
const router = useRouter();
const $q     = useQuasar();
const store  = useTicketStore();

const isEdit  = computed(() => !!route.params.id);
const today   = new Date().toISOString().slice(0, 10).replace(/-/g, '/');
const loading = ref(false);
const users   = ref<User[]>([]);

const form = ref({
  title: '', description: '', priority: 'medium', status: 'open',
  assigned_to: null as number | null, due_date: null as string | null,
});

const priorityOptions = [
  { label: 'Baja', value: 'low' }, { label: 'Media', value: 'medium' },
  { label: 'Alta', value: 'high' }, { label: 'Crítica', value: 'critical' },
];
const statusOptions = [
  { label: 'Abierto', value: 'open' }, { label: 'En progreso', value: 'in_progress' },
  { label: 'Resuelto', value: 'resolved' }, { label: 'Cerrado', value: 'closed' },
];

async function onSubmit() {
  loading.value = true;
  try {
    if (isEdit.value) {
      await store.updateTicket(Number(route.params.id), form.value);
      $q.notify({ type: 'positive', message: 'Ticket actualizado' });
    } else {
      await store.createTicket(form.value);
      $q.notify({ type: 'positive', message: 'Ticket creado correctamente' });
    }
    await router.push('/tickets');
  } catch {
    $q.notify({ type: 'negative', message: 'Error al guardar el ticket' });
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  const { data } = await api.get<User[]>('/users');
  users.value = data;
  if (isEdit.value) {
    const ticket = await store.getTicket(Number(route.params.id));
    form.value = {
      title: ticket.title, description: ticket.description,
      priority: ticket.priority, status: ticket.status,
      assigned_to: ticket.assigned_to, due_date: ticket.due_date,
    };
  }
});
</script>

<style scoped>
.form-shell {
  background: #F9FFFE;
  border: 1px solid rgba(42,157,143,0.10);
  border-radius: 8px; padding: 20px 18px;
  max-width: 640px;
}
</style>
