<template>
  <q-page class="psicol-page q-pa-md">
    <div class="page-header">
      <h1 class="page-title">Dashboard</h1>
      <p class="page-sub">Resumen de incidentes activos</p>
    </div>

    <!-- KPI strip -->
    <div class="kpi-grid q-mb-md">
      <template v-if="loading">
        <div v-for="i in 4" :key="i" class="kpi-card kpi-skeleton">
          <div class="skeleton-row" style="height:32px;width:60%;margin-bottom:6px" />
          <div class="skeleton-row" style="height:11px;width:80%" />
        </div>
      </template>
      <template v-else>
        <div class="kpi-card kpi-total">
          <div class="kpi-value">{{ stats?.total ?? '—' }}</div>
          <div class="kpi-label">Total incidentes</div>
        </div>
        <div class="kpi-card kpi-overdue">
          <div class="kpi-value">{{ stats?.overdue ?? '—' }}</div>
          <div class="kpi-label">Vencidos</div>
        </div>
        <div class="kpi-card kpi-open">
          <div class="kpi-value">{{ stats?.by_status?.open ?? '—' }}</div>
          <div class="kpi-label">Abiertos</div>
        </div>
        <div class="kpi-card kpi-progress">
          <div class="kpi-value">{{ stats?.by_status?.in_progress ?? '—' }}</div>
          <div class="kpi-label">En progreso</div>
        </div>
      </template>
    </div>

    <!-- Distribution -->
    <div class="row q-gutter-sm">
      <div class="col-12 col-md-6 dist-card">
        <div class="dist-title">Por estado</div>
        <template v-if="loading">
          <div v-for="i in 4" :key="i" class="dist-row">
            <div class="dist-meta">
              <div class="skeleton-row" style="width:8px;height:8px;border-radius:50%;flex-shrink:0" />
              <div class="skeleton-row" style="flex:1;height:11px" />
              <div class="skeleton-row" style="width:20px;height:11px" />
            </div>
            <div class="dist-track"><div class="skeleton-row" style="height:100%;width:100%" /></div>
          </div>
        </template>
        <template v-else>
          <div v-for="item in statusItems" :key="item.key" class="dist-row">
            <div class="dist-meta">
              <span class="dist-dot" :style="`background:${item.hex}`" />
              <span class="dist-label">{{ item.label }}</span>
              <span class="dist-count">{{ item.value }}</span>
            </div>
            <div class="dist-track">
              <div class="dist-fill" :style="`width:${pct(item.value)}%;background:${item.hex}`" />
            </div>
          </div>
        </template>
      </div>

      <div class="col-12 col-md-6 dist-card">
        <div class="dist-title">Por prioridad</div>
        <template v-if="loading">
          <div v-for="i in 4" :key="i" class="dist-row">
            <div class="dist-meta">
              <div class="skeleton-row" style="width:8px;height:8px;border-radius:50%;flex-shrink:0" />
              <div class="skeleton-row" style="flex:1;height:11px" />
              <div class="skeleton-row" style="width:20px;height:11px" />
            </div>
            <div class="dist-track"><div class="skeleton-row" style="height:100%;width:100%" /></div>
          </div>
        </template>
        <template v-else>
          <div v-for="item in priorityItems" :key="item.key" class="dist-row">
            <div class="dist-meta">
              <span class="dist-dot" :style="`background:${item.hex}`" />
              <span class="dist-label">{{ item.label }}</span>
              <span class="dist-count">{{ item.value }}</span>
            </div>
            <div class="dist-track">
              <div class="dist-fill" :style="`width:${pct(item.value)}%;background:${item.hex}`" />
            </div>
          </div>
        </template>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { api } from 'boot/axios';
import { useQuasar } from 'quasar';

interface DashboardStats {
  total: number;
  overdue: number;
  by_status: Record<string, number>;
  by_priority: Record<string, number>;
}

const $q = useQuasar();
const stats   = ref<DashboardStats | null>(null);
const loading = ref(true);

const totalTickets = computed(() => stats.value?.total ?? 0);

function pct(val: number) {
  return totalTickets.value > 0 ? ((val / totalTickets.value) * 100).toFixed(1) : 0;
}

const statusItems = computed(() => [
  { key: 'open',        label: 'Abierto',    hex: '#3B82F6', value: stats.value?.by_status.open        ?? 0 },
  { key: 'in_progress', label: 'En progreso', hex: '#F4A261', value: stats.value?.by_status.in_progress ?? 0 },
  { key: 'resolved',    label: 'Resuelto',    hex: '#2A9D8F', value: stats.value?.by_status.resolved    ?? 0 },
  { key: 'closed',      label: 'Cerrado',     hex: '#96B4B2', value: stats.value?.by_status.closed      ?? 0 },
]);

const priorityItems = computed(() => [
  { key: 'critical', label: 'Crítica', hex: '#7C4DBE', value: stats.value?.by_priority.critical ?? 0 },
  { key: 'high',     label: 'Alta',    hex: '#E63946', value: stats.value?.by_priority.high     ?? 0 },
  { key: 'medium',   label: 'Media',   hex: '#F4A261', value: stats.value?.by_priority.medium   ?? 0 },
  { key: 'low',      label: 'Baja',    hex: '#2A9D8F', value: stats.value?.by_priority.low      ?? 0 },
]);

onMounted(async () => {
  try {
    const { data } = await api.get<DashboardStats>('/dashboard/stats');
    stats.value = data;
  } catch {
    $q.notify({ type: 'negative', message: 'Error al cargar estadísticas' });
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
/* KPI */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}
@media (max-width: 700px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }

.kpi-card {
  background: #F9FFFE;
  border: 1px solid rgba(42,157,143,0.09);
  border-left: 3px solid;
  border-radius: 7px;
  padding: 14px 14px 12px;
}
.kpi-skeleton { border-left-color: rgba(42,157,143,0.15); }
.kpi-total    { border-left-color: #2A9D8F; }
.kpi-overdue  { border-left-color: #E63946; }
.kpi-open     { border-left-color: #3B82F6; }
.kpi-progress { border-left-color: #F4A261; }

.kpi-value { font-size: 28px; font-weight: 700; color: #1C3035; letter-spacing: -0.02em; line-height: 1.1; }
.kpi-label { font-size: 11px; color: #96B4B2; margin-top: 3px; font-weight: 500; }

/* Distribution */
.dist-card {
  background: #F9FFFE;
  border: 1px solid rgba(42,157,143,0.09);
  border-radius: 7px;
  padding: 14px 16px;
}
.dist-title { font-size: 12px; font-weight: 600; color: #2D4A47; letter-spacing: 0.01em; margin-bottom: 12px; }
.dist-row   { margin-bottom: 10px; }
.dist-meta  { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.dist-dot   { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.dist-label { font-size: 12px; color: #5D7E7B; flex: 1; }
.dist-count { font-size: 12px; font-weight: 600; color: #2D4A47; font-variant-numeric: tabular-nums; }
.dist-track { height: 4px; background: #E5F4F2; border-radius: 2px; overflow: hidden; }
.dist-fill  { height: 100%; border-radius: 2px; transition: width 0.4s ease; }
</style>
