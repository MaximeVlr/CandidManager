export type SendLog = {
  id: string;
  application: {
    id: string;
    company: string;
    email: string;
  };
  status: 'success' | 'failure';
  smtp_code: string | null;
  error_message: string | null;
  duration_ms: number | null;
  created_at: string;
};

export type SendLogListResponse = {
  items: SendLog[];
  total: number;
  page: number;
  limit: number;
  pages: number;
};

export type SendLogFilters = {
  search: string;
  status: string;
  page: number;
  limit: number;
};
