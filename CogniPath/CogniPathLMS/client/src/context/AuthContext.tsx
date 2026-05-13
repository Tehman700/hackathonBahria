import { createContext, useContext, useState, type ReactNode } from 'react';
import type { UserProfile } from '../types/models';

interface AuthContextType {
    user: UserProfile | null;
    loading: boolean;
    loginWithGoogle: () => Promise<void>;
    logout: () => Promise<void>;
}

const DEFAULT_USER: UserProfile = {
    uid: 'local-user-1',
    displayName: 'Learner',
    email: 'learner@local.dev',
    photoURL: null,
};

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
    const [user] = useState<UserProfile | null>(DEFAULT_USER);
    const loading = false;

    const loginWithGoogle = async () => {
        // No-op: user is always logged in locally
    };

    const logout = async () => {
        // No-op: local mode has no session to clear
    };

    return (
        <AuthContext.Provider value={{ user, loading, loginWithGoogle, logout }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};
