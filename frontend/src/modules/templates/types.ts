export type MailTemplate = {
  id: string;
  name: string;
  html_body: string;
  text_body: string;
  cv: {
    original_name: string;
    mime_type: string;
  } | null;
  created_at: string;
  updated_at: string;
};

export type TemplatesResponse = {
  items: MailTemplate[];
  allowed_variables: string[];
};

export type TemplateUpdatePayload = {
  html_body: string;
  text_body: string;
};
