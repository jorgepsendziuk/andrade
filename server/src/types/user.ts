export type UserRole = 'admin' | 'editor' | 'comercial';

export interface AdminUser {
  id: string;
  email: string;
  name: string;
  passwordHash: string;
  role: UserRole;
  active: boolean;
  mustChangePassword: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface AdminUserPublic {
  id: string;
  email: string;
  name: string;
  role: UserRole;
  active: boolean;
  mustChangePassword: boolean;
  createdAt: string;
  updatedAt: string;
}

export function toPublicUser(user: AdminUser): AdminUserPublic {
  const { passwordHash: _, ...publicUser } = user;
  return publicUser;
}
