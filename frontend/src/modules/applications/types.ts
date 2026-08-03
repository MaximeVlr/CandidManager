export type JobApplication = {
  id: string;
  company: string;
  location: string;
  email: string;
  subject: string;
  custom_message: string;
  official_source_url: string;
  send_status: 'pending' | 'sending' | 'sent' | 'failed';
  response: 'none' | 'positive' | 'negative' | 'pending';
  follow_up: boolean;
  follow_up_count: number;
  sent_at: string | null;
  last_error: string | null;
  created_at: string;
  updated_at: string;
};

export type ApplicationListResponse = {
  items: JobApplication[];
  total: number;
  page: number;
  limit: number;
  pages: number;
};

export type ApplicationListFilters = {
  search: string;
  sendStatus: string;
  response: string;
  followUp: string;
  sort: string;
  direction: 'asc' | 'desc';
  page: number;
  limit: number;
};

export type JobApplicationUpdatePayload = {
  company: string;
  location: string;
  email: string;
  subject: string;
  custom_message: string;
  official_source_url: string;
  response: JobApplication['response'];
  follow_up: boolean;
};

export type ApplicationPreview = {
  subject: string;
  html_body: string;
  text_body: string;
};

export type SendApplicationResponse = {
  sent: boolean;
  message: string;
  application: JobApplication | null;
};

export type QueuedSendResponse = {
  queued: number;
  limit: number;
  queued_ids: string[];
};

export type ImportApplicationsResponse = {
  created: number;
  skipped: number;
  errors: Array<{
    index: number | null;
    field: string | null;
    message: string;
  }>;
};
