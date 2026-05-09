import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from 'boot/axios';

export interface User {
  id: number;
  name: string;
  email: string;
}

export interface Ticket {
  id: number;
  title: string;
  description: string;
  priority: 'low' | 'medium' | 'high' | 'critical';
  status: 'open' | 'in_progress' | 'resolved' | 'closed';
  creator_id: number;
  assigned_to: number | null;
  due_date: string | null;
  created_at: string;
  updated_at: string;
  creator?: User;
  assignee?: User;
}

export interface TicketFilters {
  search: string;
  priority: string;
  status: string;
  assigned_to: number | null;
  date_from: string;
  date_to: string;
}

export interface Pagination {
  page: number;
  rowsPerPage: number;
  rowsNumber: number;
  sortBy: string;
  descending: boolean;
}

export interface TicketPayload {
  title: string;
  description: string;
  priority: string;
  status?: string;
  assigned_to?: number | null;
  due_date?: string | null;
}

export const useTicketStore = defineStore('ticket', () => {
  const tickets = ref<Ticket[]>([]);
  const loading = ref(false);

  const defaultPagination: Pagination = {
    page: 1,
    rowsPerPage: 15,
    rowsNumber: 0,
    sortBy: 'created_at',
    descending: true,
  };

  const pagination = ref<Pagination>({ ...defaultPagination });

  const defaultFilters: TicketFilters = {
    search: '',
    priority: '',
    status: '',
    assigned_to: null,
    date_from: '',
    date_to: '',
  };

  const filters = ref<TicketFilters>({ ...defaultFilters });

  async function fetchTickets() {
    loading.value = true;
    try {
      const params: Record<string, string | number> = {
        page: pagination.value.page,
        per_page: pagination.value.rowsPerPage,
        sort_by: pagination.value.sortBy,
        sort_dir: pagination.value.descending ? 'desc' : 'asc',
      };

      if (filters.value.search) params.search = filters.value.search;
      if (filters.value.priority) params.priority = filters.value.priority;
      if (filters.value.status) params.status = filters.value.status;
      if (filters.value.assigned_to) params.assigned_to = filters.value.assigned_to;
      if (filters.value.date_from) params.date_from = filters.value.date_from;
      if (filters.value.date_to) params.date_to = filters.value.date_to;

      const { data } = await api.get('/tickets', { params });
      tickets.value = data.data;
      pagination.value.rowsNumber = data.total;
    } finally {
      loading.value = false;
    }
  }

  async function createTicket(payload: TicketPayload): Promise<Ticket> {
    const { data } = await api.post<Ticket>('/tickets', payload);
    return data;
  }

  async function updateTicket(id: number, payload: Partial<TicketPayload>): Promise<Ticket> {
    const { data } = await api.put<Ticket>(`/tickets/${id}`, payload);
    return data;
  }

  async function deleteTicket(id: number): Promise<void> {
    await api.delete(`/tickets/${id}`);
  }

  async function getTicket(id: number): Promise<Ticket> {
    const { data } = await api.get<Ticket>(`/tickets/${id}`);
    return data;
  }

  function resetFilters() {
    filters.value = { ...defaultFilters };
    pagination.value = { ...defaultPagination };
  }

  return {
    tickets,
    loading,
    pagination,
    filters,
    fetchTickets,
    createTicket,
    updateTicket,
    deleteTicket,
    getTicket,
    resetFilters,
  };
});
