import { createContext, useContext, useState, useCallback, useEffect, type ReactNode } from 'react';
import type { SiteContent, SiteSection } from '../types/site';
import { fetchContent, saveContent, getToken, setToken, login as apiLogin } from '../lib/api';

interface CmsContextValue {
  content: SiteContent | null;
  loading: boolean;
  isEditing: boolean;
  isAuthenticated: boolean;
  isSaving: boolean;
  hasChanges: boolean;
  setIsEditing: (v: boolean) => void;
  updateSection: (sectionId: string, data: Record<string, unknown>) => void;
  updateSite: (data: Partial<SiteContent['site']>) => void;
  reorderSections: (sections: SiteSection[]) => void;
  toggleSection: (sectionId: string) => void;
  save: () => Promise<void>;
  login: (username: string, password: string) => Promise<void>;
  logout: () => void;
  getSection: (id: string) => SiteSection | undefined;
}

const CmsContext = createContext<CmsContextValue | null>(null);

export function CmsProvider({ children }: { children: ReactNode }) {
  const [content, setContent] = useState<SiteContent | null>(null);
  const [loading, setLoading] = useState(true);
  const [isEditing, setIsEditing] = useState(false);
  const [isAuthenticated, setIsAuthenticated] = useState(!!getToken());
  const [isSaving, setIsSaving] = useState(false);
  const [hasChanges, setHasChanges] = useState(false);

  useEffect(() => {
    fetchContent()
      .then(setContent)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const updateContent = useCallback((updater: (prev: SiteContent) => SiteContent) => {
    setContent((prev) => {
      if (!prev) return prev;
      const next = updater(prev);
      setHasChanges(true);
      return next;
    });
  }, []);

  const updateSection = useCallback((sectionId: string, data: Record<string, unknown>) => {
    updateContent((prev) => ({
      ...prev,
      sections: prev.sections.map((s) =>
        s.id === sectionId ? { ...s, data: { ...s.data, ...data } } : s
      ),
    }));
  }, [updateContent]);

  const updateSite = useCallback((data: Partial<SiteContent['site']>) => {
    updateContent((prev) => ({
      ...prev,
      site: { ...prev.site, ...data },
    }));
  }, [updateContent]);

  const reorderSections = useCallback((sections: SiteSection[]) => {
    updateContent((prev) => ({
      ...prev,
      sections: sections.map((s, i) => ({ ...s, order: i })),
    }));
  }, [updateContent]);

  const toggleSection = useCallback((sectionId: string) => {
    updateContent((prev) => ({
      ...prev,
      sections: prev.sections.map((s) =>
        s.id === sectionId ? { ...s, enabled: !s.enabled } : s
      ),
    }));
  }, [updateContent]);

  const save = useCallback(async () => {
    const token = getToken();
    if (!token || !content) return;
    setIsSaving(true);
    try {
      await saveContent(content, token);
      setHasChanges(false);
    } finally {
      setIsSaving(false);
    }
  }, [content]);

  const login = useCallback(async (username: string, password: string) => {
    const { token } = await apiLogin(username, password);
    setToken(token);
    setIsAuthenticated(true);
    setIsEditing(true);
  }, []);

  const logout = useCallback(() => {
    setToken(null);
    setIsAuthenticated(false);
    setIsEditing(false);
  }, []);

  const getSection = useCallback(
    (id: string) => {
      return content?.sections.find((s) => s.id === id);
    },
    [content]
  );

  return (
    <CmsContext.Provider
      value={{
        content,
        loading,
        isEditing,
        isAuthenticated,
        isSaving,
        hasChanges,
        setIsEditing,
        updateSection,
        updateSite,
        reorderSections,
        toggleSection,
        save,
        login,
        logout,
        getSection,
      }}
    >
      {children}
    </CmsContext.Provider>
  );
}

export function useCms() {
  const ctx = useContext(CmsContext);
  if (!ctx) throw new Error('useCms must be used within CmsProvider');
  return ctx;
}
