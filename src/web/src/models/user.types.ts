import { ApiResponse } from './api.types';

/**
 * Enum representing user roles in the system
 */
export enum UserRole {
  ADMIN = 'admin',
  MANAGER = 'manager',
  ADJUSTER = 'adjuster',
  UNDERWRITER = 'underwriter',
  SUPPORT = 'support',
  READONLY = 'readonly'
}

/**
 * Enum representing document-related permissions
 */
export enum Permission {
  VIEW_DOCUMENT = 'document.view',
  EDIT_METADATA = 'document.edit',
  PROCESS_DOCUMENT = 'document.process',
  TRASH_DOCUMENT = 'document.trash',
  RESTORE_DOCUMENT = 'document.restore',
  DELETE_DOCUMENT = 'document.delete',
  OVERRIDE_LOCK = 'document.override'
}

/**
 * Interface representing a user in the system
 */
export interface User {
  id: number;
  username: string;
  email: string;
  fullName: string;
  role: UserRole;
  roleName: string;
  userGroupId: number | null;
  userGroupName: string | null;
  isActive: boolean;
  lastLogin: string | null;
}

/**
 * Interface for login request payload
 */
export interface LoginCredentials {
  username: string;
  password: string;
  rememberMe: boolean;
}

/**
 * Interface for login response data
 */
export interface LoginResponse {
  token: string;
  user: User;
}

/**
 * Interface for authentication state
 */
export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}

/**
 * Interface for the authentication context
 */
export interface AuthContextType {
  state: AuthState;
  login: (username: string, password: string, rememberMe?: boolean) => Promise<boolean>;
  logout: () => Promise<void>;
  hasPermission: (permission: Permission) => boolean;
}

/**
 * Mapping of user roles to their permissions
 * Based on the role-based access control defined in the security architecture
 */
export const ROLE_PERMISSIONS: Record<UserRole, Permission[]> = {
  [UserRole.ADMIN]: [
    Permission.VIEW_DOCUMENT,
    Permission.EDIT_METADATA,
    Permission.PROCESS_DOCUMENT,
    Permission.TRASH_DOCUMENT,
    Permission.RESTORE_DOCUMENT,
    Permission.DELETE_DOCUMENT,
    Permission.OVERRIDE_LOCK
  ],
  [UserRole.MANAGER]: [
    Permission.VIEW_DOCUMENT,
    Permission.EDIT_METADATA,
    Permission.PROCESS_DOCUMENT,
    Permission.TRASH_DOCUMENT,
    Permission.RESTORE_DOCUMENT,
    Permission.OVERRIDE_LOCK
  ],
  [UserRole.ADJUSTER]: [
    Permission.VIEW_DOCUMENT,
    Permission.EDIT_METADATA,
    Permission.PROCESS_DOCUMENT
  ],
  [UserRole.UNDERWRITER]: [
    Permission.VIEW_DOCUMENT,
    Permission.EDIT_METADATA,
    Permission.PROCESS_DOCUMENT
  ],
  [UserRole.SUPPORT]: [
    Permission.VIEW_DOCUMENT,
    Permission.EDIT_METADATA
  ],
  [UserRole.READONLY]: [
    Permission.VIEW_DOCUMENT
  ]
};

/**
 * Utility function to check if a user has a specific permission
 * @param user The user to check permissions for
 * @param permission The permission to check
 * @returns True if the user has the permission, false otherwise
 */
export function hasPermission(user: User | null, permission: Permission): boolean {
  if (!user) return false;
  
  const permissions = ROLE_PERMISSIONS[user.role];
  return permissions.includes(permission);
}