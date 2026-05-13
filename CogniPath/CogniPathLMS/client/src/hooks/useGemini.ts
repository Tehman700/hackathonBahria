import { useState } from 'react';
import axiosClient from '../api/axiosClient';
import type { PersonalizedPath, ModuleData, ChatMessage } from '../types/gemini';
import type { UserProfile } from '../types/models';

interface GeneratePathParams {
    userProfile: UserProfile;
    topic: string;
    goal: string;
    level: string;
    files?: File[];
}

// --- localStorage helpers ---

const PATHS_KEY = 'lms_paths';

function loadPaths(): (PersonalizedPath & { id: string; userId: string; createdAt: string })[] {
    try {
        return JSON.parse(localStorage.getItem(PATHS_KEY) || '[]');
    } catch {
        return [];
    }
}

function savePaths(paths: (PersonalizedPath & { id: string; userId: string; createdAt: string })[]) {
    localStorage.setItem(PATHS_KEY, JSON.stringify(paths));
}

function moduleKey(userId: string, pathId: string, moduleId: string) {
    return `lms_module_${userId}_${pathId}_${moduleId}`;
}

function messagesKey(userId: string, pathId: string, moduleId: string | null) {
    return `lms_msgs_${userId}_${pathId}_${moduleId ?? 'path'}`;
}

function generateId() {
    return Math.random().toString(36).slice(2) + Date.now().toString(36);
}

// --- Hook ---

export const useGemini = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const generatePath = async (params: GeneratePathParams): Promise<PersonalizedPath | null> => {
        setLoading(true);
        setError(null);
        try {
            const formData = new FormData();
            formData.append('name', params.userProfile.displayName || 'Learner');
            formData.append('goal', params.goal);
            formData.append('level', params.level);
            if (params.files && params.files.length > 0) {
                params.files.forEach((file) => formData.append('files', file));
            }
            const response = await axiosClient.post('/generate-path', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            return response.data as PersonalizedPath;
        } catch (err: any) {
            setError(err.message || 'Failed to generate learning path');
            return null;
        } finally {
            setLoading(false);
        }
    };

    const savePathToFirestore = async (userId: string, path: PersonalizedPath): Promise<string | null> => {
        const id = generateId();
        const paths = loadPaths();
        paths.unshift({ ...path, id, userId, createdAt: new Date().toISOString() });
        savePaths(paths);
        return id;
    };

    const getUserPaths = async (userId: string): Promise<(PersonalizedPath & { id: string; createdAt: string })[]> => {
        setLoading(true);
        try {
            return loadPaths().filter((p) => p.userId === userId);
        } finally {
            setLoading(false);
        }
    };

    const getPath = async (userId: string, pathId: string): Promise<PersonalizedPath | null> => {
        setLoading(true);
        try {
            const found = loadPaths().find((p) => p.id === pathId && p.userId === userId);
            return found ?? null;
        } finally {
            setLoading(false);
        }
    };

    const getModule = async (userId: string, pathId: string, moduleId: string): Promise<ModuleData | null> => {
        setLoading(true);
        try {
            const raw = localStorage.getItem(moduleKey(userId, pathId, moduleId));
            return raw ? JSON.parse(raw) : null;
        } finally {
            setLoading(false);
        }
    };

    const saveModule = async (userId: string, pathId: string, moduleData: ModuleData): Promise<void> => {
        const existing = localStorage.getItem(moduleKey(userId, pathId, moduleData.id));
        const merged = { ...(existing ? JSON.parse(existing) : {}), ...moduleData, lastAccessed: new Date().toISOString() };
        localStorage.setItem(moduleKey(userId, pathId, moduleData.id), JSON.stringify(merged));
    };

    const generateLesson = async (topic: string, description: string, userGoal: string): Promise<string> => {
        setLoading(true);
        try {
            const response = await axiosClient.post('/generate-lesson', { topic, description, userGoal });
            return response.data.content;
        } finally {
            setLoading(false);
        }
    };

    const contextChat = async (history: any[], message: string, context: any) => {
        const response = await axiosClient.post('/chat', { history, message, context });
        return response.data;
    };

    const saveMessageToFirestore = async (userId: string, pathId: string, moduleId: string | null, message: ChatMessage) => {
        const key = messagesKey(userId, pathId, moduleId);
        const existing: ChatMessage[] = JSON.parse(localStorage.getItem(key) || '[]');
        existing.push({ ...message, id: message.id || generateId(), createdAt: new Date().toISOString() } as any);
        localStorage.setItem(key, JSON.stringify(existing));
    };

    const getMessagesFromFirestore = async (userId: string, pathId: string, moduleId: string | null): Promise<ChatMessage[]> => {
        const key = messagesKey(userId, pathId, moduleId);
        return JSON.parse(localStorage.getItem(key) || '[]');
    };

    const deletePath = async (pathId: string, userId: string): Promise<boolean> => {
        setLoading(true);
        try {
            const paths = loadPaths().filter((p) => !(p.id === pathId && p.userId === userId));
            savePaths(paths);
            // Best-effort server call (may fail if server has no DB, that's fine)
            try {
                await axiosClient.delete(`/paths/${pathId}`, { params: { userId } });
            } catch {
                // server-side delete is optional without Firebase
            }
            return true;
        } catch (err: any) {
            setError(err.message || 'Failed to delete path');
            return false;
        } finally {
            setLoading(false);
        }
    };

    const regenerateLesson = async (
        pathId: string,
        moduleId: string,
        topic: string,
        description: string,
        userGoal: string,
        userId: string,
        feedback: string[]
    ): Promise<string> => {
        setLoading(true);
        try {
            // Clear local chat history for this module
            localStorage.removeItem(messagesKey(userId, pathId, moduleId));

            const response = await axiosClient.post(`/modules/${moduleId}/regenerate`, {
                topic, description, userGoal, feedback, pathId, userId,
            });
            return response.data.content;
        } finally {
            setLoading(false);
        }
    };

    return {
        generatePath,
        savePathToFirestore,
        getUserPaths,
        getPath,
        getModule,
        saveModule,
        generateLesson,
        regenerateLesson,
        deletePath,
        contextChat,
        saveMessageToFirestore,
        getMessagesFromFirestore,
        loading,
        error,
    };
};
