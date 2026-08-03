export type ApplicationStats = {
  total: number;
  pending: number;
  sending: number;
  sent: number;
  failed: number;
  follow_up: number;
  responses: {
    none: number;
    pending: number;
    positive: number;
    negative: number;
  };
  logs: {
    success: number;
    failure: number;
  };
  failure_rate: number;
};
