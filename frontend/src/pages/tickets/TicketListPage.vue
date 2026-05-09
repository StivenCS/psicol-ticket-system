<template>
  <q-page class="psicol-page q-pa-md">
    <!-- Header -->
    <div class="row items-center q-mb-sm">
      <div class="col">
        <h1 class="page-title">Solicitudes e Incidentes</h1>
      </div>
      <div class="col-auto row" style="gap:6px">
        <q-btn-dropdown
          flat dense icon="download" label="Exportar"
          style="color:#5D7E7B;border:1px solid rgba(42,157,143,0.18);border-radius:6px;padding:0 10px"
          :loading="exporting" no-icon-animation
        >
          <q-list dense>
            <q-item clickable v-close-popup @click="exportTickets('xlsx')">
              <q-item-section avatar><q-icon name="table_view" color="positive" /></q-item-section>
              <q-item-section>Excel (.xlsx)</q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="exportTickets('csv')">
              <q-item-section avatar><q-icon name="description" color="info" /></q-item-section>
              <q-item-section>CSV (.csv)</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
        <q-btn color="primary" icon="add" label="Nuevo" unelevated dense @click="goToCreate" />
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar q-mb-sm">
      <q-input
        v-model="store.filters.search" placeholder="Buscar..." outlined dense clearable
        class="filter-search" @update:model-value="onFilterChange"
      >
        <template #prepend><q-icon name="search" size="16px" style="color:#96B4B2" /></template>
      </q-input>

      <q-select
        v-model="store.filters.priority" :options="priorityOptions" option-value="value" option-label="label"
        emit-value map-options label="Prioridad" outlined dense clearable
        class="filter-select" @update:model-value="onFilterChange"
      />
      <q-select
        v-model="store.filters.status" :options="statusOptions" option-value="value" option-label="label"
        emit-value map-options label="Estado" outlined dense clearable
        class="filter-select" @update:model-value="onFilterChange"
      />
      <q-select
        v-model="store.filters.assigned_to" :options="users" option-value="id" option-label="name"
        emit-value map-options label="Asignado" outlined dense clearable
        class="filter-select" @update:model-value="onFilterChange"
      />

      <q-input v-model="store.filters.date_from" label="Desde" outlined dense class="filter-date" @update:model-value="onFilterChange">
        <template #append>
          <q-icon name="event" class="cursor-pointer" style="color:#96B4B2">
            <q-popup-proxy cover transition-show="scale" transition-hide="scale">
              <q-date v-model="store.filters.date_from" mask="YYYY-MM-DD" @update:model-value="onFilterChange">
                <div class="row items-center justify-end"><q-btn v-close-popup label="OK" color="primary" flat /></div>
              </q-date>
            </q-popup-proxy>
          </q-icon>
        </template>
      </q-input>
      <q-input v-model="store.filters.date_to" label="Hasta" outlined dense class="filter-date" @update:model-value="onFilterChange">
        <template #append>
          <q-icon name="event" class="cursor-pointer" style="color:#96B4B2">
            <q-popup-proxy cover transition-show="scale" transition-hide="scale">
              <q-date v-model="store.filters.date_to" mask="YYYY-MM-DD" @update:model-value="onFilterChange">
                <div class="row items-center justify-end"><q-btn v-close-popup label="OK" color="primary" flat /></div>
              </q-date>
            </q-popup-proxy>
          </q-icon>
        </template>
      </q-input>

      <q-btn flat dense icon="clear" size="sm" style="color:#96B4B2" @click="clearFilters">
        <q-tooltip>Limpiar filtros</q-tooltip>
      </q-btn>
    </div>

    <!-- Skeleton loader -->
    <div v-if="store.loading && store.tickets.length === 0" class="tickets-skeleton">
      <div v-for="i in 8" :key="i" class="skel-row">
        <div class="skeleton-row" style="width:40px;height:12px" />
        <div class="skeleton-row" style="flex:1;height:12px" />
        <div class="skeleton-row" style="width:60px;height:20px;border-radius:4px" />
        <div class="skeleton-row" style="width:70px;height:20px;border-radius:4px" />
        <div class="skeleton-row" style="width:80px;height:12px" />
        <div class="skeleton-row" style="width:80px;height:12px" />
        <div class="skeleton-row" style="width:80px;height:12px" />
      </div>
    </div>

    <!-- Table -->
    <q-table
      v-else
      :rows="store.tickets" :columns="columns" row-key="id"
      :loading="store.loading"
      v-model:pagination="store.pagination"
      :rows-per-page-options="[10,15,25,50]"
      binary-state-sort flat
      class="tickets-table"
      @request="onTableRequest"
      @row-click="(_evt, row) => router.push(`/tickets/${row.id}`)"
    >
      <template #body-cell-priority="{ value }">
        <q-td><span :class="['p-chip', value]">{{ priorityLabel(value) }}</span></q-td>
      </template>
      <template #body-cell-status="{ value }">
        <q-td><span :class="['s-chip', value]">{{ statusLabel(value) }}</span></q-td>
      </template>
      <template #body-cell-creator="{ row }">
        <q-td>{{ row.creator?.name ?? '—' }}</q-td>
      </template>
      <template #body-cell-assigned_to="{ row }">
        <q-td>
          <span v-if="row.assignee">{{ row.assignee.name }}</span>
          <span v-else style="color:#96B4B2">Sin asignar</span>
        </q-td>
      </template>
      <template #body-cell-due_date="{ value }">
        <q-td>{{ value ?? '—' }}</q-td>
      </template>
      <template #body-cell-actions="{ row }">
        <q-td class="text-right" @click.stop>
          <q-btn v-if="auth.canEdit" flat round dense icon="edit" size="sm" color="primary" @click="goToEdit(row.id)" />
          <q-btn v-if="auth.canDelete" flat round dense icon="delete" size="sm" color="negative" @click="confirmDelete(row)" />
        </q-td>
      </template>
    </q-table>

    <!-- Delete dialog -->
    <q-dialog v-model="deleteDialog">
      <q-card style="min-width:300px;border-radius:10px">
        <q-card-section class="row items-center" style="gap:8px">
          <q-icon name="warning" color="negative" size="sm" />
          <span style="color:#2D4A47">¿Eliminar <strong>{{ ticketToDelete?.title }}</strong>?</span>
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar, type QTableProps } from 'quasar';
import { api } from 'boot/axios';
import { useTicketStore, type Ticket, type User } from 'stores/ticket';
import { useAuthStore } from 'stores/auth';

const router = useRouter();
const $q     = useQuasar();
const store  = useTicketStore();
const auth   = useAuthStore();

const users         = ref<User[]>([]);
const deleteDialog  = ref(false);
const deleting      = ref(false);
const exporting     = ref(false);
const ticketToDelete = ref<Ticket | null>(null);

const columns: QTableProps['columns'] = [
  { name: 'id',          label: '#',          field: 'id',          sortable: true,  align: 'left', style: 'width:54px' },
  { name: 'title',       label: 'Título',      field: 'title',       sortable: true,  align: 'left' },
  { name: 'priority',    label: 'Prioridad',   field: 'priority',    sortable: true,  align: 'left' },
  { name: 'status',      label: 'Estado',      field: 'status',      sortable: true,  align: 'left' },
  { name: 'creator',     label: 'Creador',     field: 'creator',     sortable: false, align: 'left' },
  { name: 'assigned_to', label: 'Asignado',    field: 'assigned_to', sortable: false, align: 'left' },
  { name: 'due_date',    label: 'Vencimiento', field: 'due_date',    sortable: true,  align: 'left' },
  { name: 'actions',     label: '',            field: 'actions',     sortable: false, align: 'right' },
];

const priorityOptions = [
  { label: 'Baja', value: 'low' }, { label: 'Media', value: 'medium' },
  { label: 'Alta', value: 'high' }, { label: 'Crítica', value: 'critical' },
];
const statusOptions = [
  { label: 'Abierto', value: 'open' }, { label: 'En progreso', value: 'in_progress' },
  { label: 'Resuelto', value: 'resolved' }, { label: 'Cerrado', value: 'closed' },
];

function priorityLabel(p: string) {
  return ({ low:'Baja', medium:'Media', high:'Alta', critical:'Crítica' } as Record<string,string>)[p] ?? p;
}
function statusLabel(s: string) {
  return ({ open:'Abierto', in_progress:'En progreso', resolved:'Resuelto', closed:'Cerrado' } as Record<string,string>)[s] ?? s;
}

let searchTimer: ReturnType<typeof setTimeout>;
function onFilterChange() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { store.pagination.page = 1; void store.fetchTickets(); }, 400);
}

type TableRequestProps = Parameters<NonNullable<QTableProps['onRequest']>>[0];
function onTableRequest(props: TableRequestProps) {
  store.pagination.page        = props.pagination.page;
  store.pagination.rowsPerPage = props.pagination.rowsPerPage;
  store.pagination.sortBy      = props.pagination.sortBy ?? 'created_at';
  store.pagination.descending  = props.pagination.descending;
  void store.fetchTickets();
}

function clearFilters() { store.resetFilters(); void store.fetchTickets(); }
function goToCreate()   { void router.push('/tickets/create'); }
function goToEdit(id: number) { void router.push(`/tickets/${id}/edit`); }

function confirmDelete(ticket: Ticket) { ticketToDelete.value = ticket; deleteDialog.value = true; }

async function exportTickets(format: 'xlsx' | 'csv') {
  exporting.value = true;
  try {
    const response = await api.get('/tickets/export', { params: { ...store.filters, format }, responseType: 'blob' });
    const url = URL.createObjectURL(response.data as Blob);
    const a   = Object.assign(document.createElement('a'), { href: url, download: `tickets_${new Date().toISOString().slice(0,10)}.${format}` });
    a.click();
    URL.revokeObjectURL(url);
  } catch {
    $q.notify({ type: 'negative', message: 'Error al exportar los tickets' });
  } finally {
    exporting.value = false;
  }
}

async function doDelete() {
  if (!ticketToDelete.value) return;
  deleting.value = true;
  try {
    await store.deleteTicket(ticketToDelete.value.id);
    $q.notify({ type: 'positive', message: 'Ticket eliminado' });
    deleteDialog.value = false;
    void store.fetchTickets();
  } catch {
    $q.notify({ type: 'negative', message: 'Error al eliminar' });
  } finally {
    deleting.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get<User[]>('/users');
    users.value = data;
  } catch { /* sin permisos */ }
  void store.fetchTickets();
});
</script>

<style scoped>
.filter-bar {
  display: flex; flex-wrap: wrap; gap: 6px; align-items: flex-end;
  background: #F9FFFE;
  border: 1px solid rgba(42,157,143,0.09);
  border-radius: 7px; padding: 10px 12px;
}
.filter-search { min-width: 180px; flex: 1; }
.filter-select { min-width: 120px; }
.filter-date   { min-width: 100px; }

/* Skeleton */
.tickets-skeleton {
  background: #F9FFFE;
  border: 1px solid rgba(42,157,143,0.09);
  border-radius: 7px;
  overflow: hidden;
}
.skel-row {
  display: flex; align-items: center; gap: 16px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(42,157,143,0.06);
}
.skel-row:last-child { border-bottom: none; }

/* Table */
.tickets-table {
  background: #F9FFFE;
  border: 1px solid rgba(42,157,143,0.09);
  border-radius: 7px;
}
:deep(.tickets-table thead th) {
  font-size: 10px; font-weight: 700; letter-spacing: 0.06em;
  text-transform: uppercase; color: #96B4B2;
  border-bottom: 1px solid rgba(42,157,143,0.09);
  background: #F9FFFE;
}
:deep(.tickets-table tbody tr) { cursor: pointer; transition: background 0.1s; }
:deep(.tickets-table tbody tr:hover) { background: #EFF8F7; }
:deep(.tickets-table tbody td) {
  font-size: 12px; color: #2D4A47;
  border-bottom: 1px solid rgba(42,157,143,0.07);
  padding-top: 8px; padding-bottom: 8px;
}
:deep(.tickets-table .q-table__top),
:deep(.tickets-table .q-table__bottom) {
  background: #F9FFFE;
  border-color: rgba(42,157,143,0.09);
}
</style>
