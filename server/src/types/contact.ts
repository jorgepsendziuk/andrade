export type ContactStatus = 'new' | 'read' | 'archived';

export interface ContactSubmission {
  id: string;
  name: string;
  email: string;
  phone: string;
  message: string;
  createdAt: string;
  emailSent: boolean;
  status: ContactStatus;
}

export interface CreateContactInput {
  name: string;
  email: string;
  phone: string;
  message: string;
  emailSent: boolean;
}
