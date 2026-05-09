<template>
  <q-page class="psicol-page q-pa-md">
    <!-- Nav -->
    <div class="row items-center q-mb-sm" style="gap:6px">
      <q-btn flat round dense icon="arrow_back" style="color:#5D7E7B" @click="router.back()" />
      <span class="detail-id">Ticket #{{ route.params.id }}</span>
      <q-space />
      <q-btn v-if="auth.canEdit" flat icon="edit" label="Editar" color="primary" size="sm" @click="goToEdit" />
      <q-btn v-if="auth.canDelete" flat icon="delete" label="Eliminar" color="negative" size="sm" @click="deleteDialog = true" />
    </div>

    <!-- Skeleton -->
    <template v-if="loading">
      <div class="status-row q-mb-sm">
        <div class="skeleton-row" style="width:180px;height:36px;border-radius:6px" />
        <div class="skeleton-row" style="width:60px;height:20px;border-radius:4px" />
      </div>
      <div class="ticket-card" style="border-left-color:rgba(42,157,143,0.2)">
        <div class="skeleton-row" style="height:22px;width:55%;margin-bottom:10px" />
        <div class="skeleton-row" style="height:12px;width:90%;margin-bottom:6px" />
        <div class="skeleton-row" style="height:12px;width:75%;margin-bottom:6px" />
        <div class="skeleton-row" style="height:12px;width:60%" />
        <div class="sep" />
        <div class="meta-grid">
          <div v-for="i in 4" :key="i">
            <div class="skeleton-row" style="height:10px;width:50%;margin-bottom:6px" />
            <div class="skeleton-row" style="height:13px;width:70%" />
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="ticket">
      <!-- Status + priority -->
      <div class="status-row q-mb-sm">
        <q-select
          v-model="ticket.status" :options="statusOptions"
          option-value="value" option-label="label"
          emit-value map-options outlined dense label="Estado"
          style="min-width:170px"
          :loading="updatingStatus"
          @update:model-value="updateStatus"
        />
        <span :class="['p-chip', ticket.priority]">{{ priorityLabel(ticket.priority) }}</span>
        <span v-if="isOverdue" class="overdue-badge">
          <q-icon name="warning" size="11px" />Vencido
        </span>
      </div>

      <!-- Tarjeta con borde-izquierdo = estado (signature element) -->
      <div class="ticket-card" :style="`border-left-color:${statusAccent}`">
        <div class="ticket-title">{{ ticket.title }}</div>
        <div class="ticket-desc">{{ ticket.description }}</div>
        <div class="sep" />
        <div class="meta-grid">
          <div class="meta-item">
            <div class="meta-label">Creador</div>
            <div class="meta-value row items-center" style="gap:5px">
              <div class="meta-av" style="background:#2A9D8F">{{ ticket.creator?.name?.charAt(0).toUpperCase() }}</div>
              {{ ticket.creator?.name ?? '—' }}
            </div>
          </div>
          <div class="meta-item">
            <div class="meta-label">Asignado a</div>
            <div class="meta-value row items-center" style="gap:5px">
              <template v-if="ticket.assignee">
                <div class="meta-av" style="background:#264653">{{ ticket.assignee.name.charAt(0).toUpperCase() }}</div>
                {{ ticket.assignee.name }}
              </template>
              <span v-else style="color:#96B4B2">Sin asignar</span>
            </div>
          </div>
          <div class="meta-item">
            <div class="meta-label">Vencimiento</div>
            <div class="meta-value" :class="isOverdue ? 'text-negative' : ''">{{ ticket.due_date ?? 'Sin fecha' }}</div>
          </div>
          <div class="meta-item">
            <div class="meta-label">Creado</div>
            <div class="meta-value">{{ formatDate(ticket.created_at) }}</div>
          </div>
        </div>
      </div>
    </template>

    <q-dialog v-model="deleteDialog">
      <q-card style="min-width:300px;border-radius:10px">
        <q-card-section class="row items-center" style="gap:8px">
          <q-icon name="warning" color="negative" size="sm" />
          <span style="color:#2D4A47">¿Eliminar <strong>{{ ticket?.title }}</strong>?</span>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" style="color:#5D7E7B" v-close-popup />
          <q-btn flat label="Eliminar" color="negative" :loading="deleting" @click="doDelete" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useTicketStore, type Ticket } from 'stores/ticket';
import { useAuthStore } from 'stores/auth';

const route  = useRoute();
const router = useRouter();
const $q     = useQuasar();
const store  = useTicketStore();
const auth   = useAuthStore();

const ticket        = ref<Ticket | null>(null);
const loading       = ref(true);
const updatingStatus = ref(false);
const deleteDialog  = ref(false);
const deleting      = ref(false);

const isOverdue = computed(() => {
  if (!ticket.value?.due_date) return false;
  if (['resolved','closed'].includes(ticket.value.status)) return false;
  return new Date(ticket.value.due_date) < new Date();
});

const statusAccent = computed(() => ({
  open: '#3B82F6', in_progress: '#F4A261', resolved: '#2A9D8F', closed: '#96B4B2',
}[ticket.value?.status ?? 'closed'] ?? '#96B4B2'));

const statusOptions = [
  { label: 'Abierto',     value: 'open' },
  { label: 'En progreso', value: 'in_progress' },
  { label: 'Resuelto',    value: 'resolved' },
  { label: 'Cerrado',     value: 'closed' },
];

function priorityLabel(p: string) {
  return ({ low:'Baja', medium:'Media', high:'Alta', critical:'Crítica' } as Record<string,string>)[p] ?? p;
}
function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('es-CO', { year:'numeric', month:'short', day:'2-digit' });
}

async function loadTicket() {
  loading.value = true;
  try {
    ticket.value = await store.getTicket(Number(route.params.id));
  } catch {
    $q.notify({ type: 'negative', message: 'Error al cargar el ticket' });
    void router.back();
  } finally {
    loading.value = false;
  }
}

async function updateStatus(status: string) {
  updatingStatus.value = true;
  try {
    ticket.value = await store.updateTicket(Number(route.params.id), { status });
    $q.notify({ type: 'positive', message: 'Estado actualizado' });
  } catch {
    $q.notify({ type: 'negative', message: 'Error al actualizar el estado' });
    await loadTicket();
  } finally {
    updatingStatus.value = false;
  }
}

function goToEdit() { void router.push(`/tickets/${route.params.id}/edit`); }

async function doDelete() {
  deleting.value = true;
  try {
    await store.deleteTicket(Number(route.params.id));
    $q.notify({ type: 'positive', message: 'Ticket eliminado' });
    void router.push('/tickets');
  } catch {
    $q.notify({ type: 'negative', message: 'Error al eliminar' });
  } finally {
    deleting.value = false;
  }
}

onMounted(loadTicket);
</script>

<style scoped>
.detail-id { font-size: 15px; font-weight: 700; color: #1C3035; }

.status-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.overdue-badge {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 7px; background: #FDEBEC; color: #E63946;
  border-radius: 4px; font-size: 11px; font-weight: 600;
  letter-spacing: 0.04em; text-transform: uppercase;
}

/* Signature: borde izquierdo = color del estado */
.ticket-card {
  background: #F9FFFE;
  border: 1px solid rgba(42,157,143,0.09);
  border-left: 4px solid;
  border-radius: 7px; padding: 18px;
  transition: border-left-color 0.2s;
}
.ticket-title { font-size: 16px; font-weight: 700; color: #1C3035; letter-spacing: -0.01em; margin-bottom: 8px; line-height: 1.3; }
.ticket-desc  { font-size: 13px; color: #5D7E7B; line-height: 1.6; white-space: pre-wrap; }

.sep { height: 1px; background: rgba(42,157,143,0.09); margin: 14px 0; }

.meta-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; }
@media (max-width: 700px) { .meta-grid { grid-template-columns: repeat(2,1fr); } }

.meta-label { font-size: 10px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #96B4B2; margin-bottom: 5px; }
.meta-value { font-size: 12px; color: #2D4A47; font-weight: 500; }
.meta-av {
  width: 20px; height: 20px; border-radius: 50%; color: #fff;
  font-size: 9px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
</style>
