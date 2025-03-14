import React, { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react';
import { User, AuthState, AuthContextType, Permission, hasPermission } from '../models/user.types';
import { authService } from '../services/authService';

/**
 * Authentication context providing authentication state and functionality
 * throughout the Documents View feature.
 */
const AuthContext = createContext<AuthContextType | null>(null);

/**
 * Initial authentication state with default values
 */
const initialAuthState: AuthState = {
  user: null,
  isAuthenticated: false,
  isLoading: true,
  error: null
};

/**
 * Helper function to check if a user has a specific permission
 * @param user The user to check permissions for
 * @param permission The permission to check
 * @returns True if the user has the permission, false otherwise
 */
const checkUserPermission = (user: User | null, permission: Permission): boolean => {
  return hasPermission(user, permission);
};

/**
 * Provider component that wraps the application to provide authentication context
 * @param children React children to be wrapped by the provider
 * @returns The provider component with context
 */
const AuthProvider = ({ children }: { children: ReactNode }): JSX.Element => {
  // Initialize authentication state with default values
  const [state, setState] = useState<AuthState>(initialAuthState);

  // Logout function - Handles user logout and state cleanup
  const logout = useCallback(async (): Promise<void> => {
    try {
      // Set loading state during logout
      setState(prev => ({ ...prev, isLoading: true }));
      
      // Call logout service to invalidate token on server
      await authService.logout();
      
      // Reset authentication state after logout
      setState({
        user: null,
        isAuthenticated: false,
        isLoading: false,
        error: null
      });
    } catch (error) {
      // Handle logout error but still clear local state for security
      console.error('Logout error:', error);
      setState({
        user: null,
        isAuthenticated: false,
        isLoading: false,
        error: typeof error === 'string' ? error : 'Logout failed'
      });
    }
  }, []);

  // Check for existing token and validate on component mount
  useEffect(() => {
    const validateAuth = async () => {
      try {
        // Set loading state during validation
        setState(prev => ({ ...prev, isLoading: true }));
        
        // Validate existing token with the server
        const isValid = await authService.validateToken();
        
        if (isValid) {
          // Get current user data if token is valid
          const user = await authService.getCurrentUser();
          
          setState({
            user,
            isAuthenticated: Boolean(user),
            isLoading: false,
            error: null
          });
        } else {
          // Clear state if token is invalid or missing
          setState({
            user: null,
            isAuthenticated: false,
            isLoading: false,
            error: null
          });
        }
      } catch (error) {
        // Handle validation error
        console.error('Authentication validation error:', error);
        setState({
          user: null,
          isAuthenticated: false,
          isLoading: false,
          error: typeof error === 'string' ? error : 'Authentication failed'
        });
      }
    };

    validateAuth();
  }, []);

  // Set up token refresh interval to extend session
  useEffect(() => {
    // Only set up refresh interval if user is authenticated
    if (!state.isAuthenticated || !state.user) {
      return;
    }

    const tokenRefreshInterval = setInterval(async () => {
      try {
        const refreshed = await authService.refreshToken();
        if (!refreshed) {
          // If refresh fails, log the user out
          console.warn('Token refresh failed, logging out user');
          logout();
        }
      } catch (error) {
        console.error('Token refresh error:', error);
        logout();
      }
    }, 15 * 60 * 1000); // Refresh every 15 minutes

    // Clean up interval on unmount or when authentication state changes
    return () => {
      clearInterval(tokenRefreshInterval);
    };
  }, [state.isAuthenticated, state.user, logout]);

  // Login function - Authenticates user with server
  const login = useCallback(async (
    username: string, 
    password: string, 
    rememberMe: boolean = false
  ): Promise<boolean> => {
    try {
      // Set loading state during login
      setState(prev => ({ ...prev, isLoading: true, error: null }));
      
      // Call login service to authenticate with server
      const response = await authService.login(username, password, rememberMe);
      
      // Update state with authenticated user data
      setState({
        user: response.user,
        isAuthenticated: true,
        isLoading: false,
        error: null
      });
      
      return true;
    } catch (error) {
      // Handle login error
      console.error('Login error:', error);
      setState(prev => ({
        ...prev,
        isAuthenticated: false,
        isLoading: false,
        error: typeof error === 'string' ? error : 'Login failed. Please check your credentials.'
      }));
      
      return false;
    }
  }, []);

  // Check if current user has a specific permission
  const checkPermission = useCallback((permission: Permission): boolean => {
    return checkUserPermission(state.user, permission);
  }, [state.user]);

  // Create context value object with state and functions
  const contextValue: AuthContextType = {
    state,
    login,
    logout,
    hasPermission: checkPermission
  };

  // Return AuthContext.Provider with the context value
  return (
    <AuthContext.Provider value={contextValue}>
      {children}
    </AuthContext.Provider>
  );
};

/**
 * Custom hook to access the authentication context
 * @returns The authentication context value
 * @throws Error if used outside of AuthProvider
 */
const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  
  // Throw error if used outside of AuthProvider
  if (context === null) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  
  return context;
};

export { AuthProvider, useAuth, AuthContext };