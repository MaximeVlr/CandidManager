export type MailerSettings = {
  provider: 'gmail' | 'brevo';
  enabled: boolean;
  from_email: string;
  from_name: string;
  username: string;
  has_password: boolean;
  host: string;
  port: number;
  encryption: 'tls' | 'ssl';
  updated_at: string;
};

export type MailerSettingsPayload = {
  provider: 'gmail' | 'brevo';
  enabled: boolean;
  from_email: string;
  from_name: string;
  username: string;
  password: string;
  host: string;
  port: number;
  encryption: 'tls' | 'ssl';
};
