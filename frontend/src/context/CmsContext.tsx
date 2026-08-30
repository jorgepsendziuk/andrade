import { createContext, useContext, useState, useCallback, useEffect, type ReactNode } from 'react';
import type { SiteContent, SiteSection } from '../types/site';
import { fetchContent, saveContent, getToken, setToken, login as apiLogin, fetchMe } from '../lib/api';

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
  updateFooter: (data: Partial<NonNullable<SiteContent['footer']>>) => void;
  reorderSections: (sections: SiteSection[]) => void;
  toggleSection: (sectionId: string) => void;
  save: (payload?: SiteContent) => Promise<void>;
  login: (email: string, password: string) => Promise<{ role?: string }>;
  logout: () => void;
  getSection: (id: string) => SiteSection | undefined;
}

const CmsContext = createContext<CmsContextValue | null>(null);

export function CmsProvider({ children }: { children: ReactNode }) {
  const [content, setContent] = useState<SiteContent | null>(null);
  const [loading, setLoading] = useState(true);
  const [isEditing, setIsEditing] = useState(false);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [hasChanges, setHasChanges] = useState(false);

  useEffect(() => {
    fetchContent()
      .then(setContent)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    const token = getToken();
    if (!token) return;
    fetchMe()
      .then((user) => {
        if (user.role !== 'cliente') setIsAuthenticated(true);
      })
      .catch(() => {
        setToken(null);
        setIsAuthenticated(false);
      });
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

  const updateFooter = useCallback((data: Partial<NonNullable<SiteContent['footer']>>) => {
    updateContent((prev) => ({
      ...prev,
      footer: { ...(prev.footer ?? { description: '', columns: [], social: [] }), ...data },
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

  const save = useCallback(async (payload?: SiteContent) => {
    const token = getToken();
    const toSave = payload ?? content;
    if (!token || !toSave) return;
    setIsSaving(true);
    try {
      await saveContent(toSave, token);
      if (payload) setContent(payload);
      setHasChanges(false);
    } finally {
      setIsSaving(false);
    }
  }, [content]);

  const login = useCallback(async (email: string, password: string) => {
    const { token, user } = await apiLogin(email, password);
    setToken(token);
    if (user.role !== 'cliente') {
      setIsAuthenticated(true);
    }
    return user;
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
        updateFooter,
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
