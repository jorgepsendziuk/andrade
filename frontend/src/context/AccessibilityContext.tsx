import { createContext, useContext, useEffect, useState, useCallback, type ReactNode } from 'react';

export interface AccessibilitySettings {
  fontScale: number;
  highContrast: boolean;
  underlineLinks: boolean;
  reduceMotion: boolean;
  readableFont: boolean;
}

const STORAGE_KEY = 'andrade-a11y-settings';

const DEFAULT_SETTINGS: AccessibilitySettings = {
  fontScale: 1,
  highContrast: false,
  underlineLinks: false,
  reduceMotion: false,
  readableFont: false,
};

const MODE_BASE_SCALE = 1.125;

const MODE_FEATURES: Omit<AccessibilitySettings, 'fontScale'> = {
  highContrast: true,
  underlineLinks: true,
  reduceMotion: true,
  readableFont: true,
};

interface StoredA11y {
  accessibilityMode: boolean;
  fontScale: number;
}

interface AccessibilityContextValue {
  accessibilityMode: boolean;
  settings: AccessibilitySettings;
  toggleAccessibilityMode: () => void;
  increaseFont: () => void;
  decreaseFont: () => void;
}

const AccessibilityContext = createContext<AccessibilityContextValue | null>(null);

function loadStored(): StoredA11y {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (typeof parsed.accessibilityMode === 'boolean') {
        return {
          accessibilityMode: parsed.accessibilityMode,
          fontScale: parsed.fontScale ?? MODE_BASE_SCALE,
        };
      }
      // migração do formato antigo
      if (parsed.highContrast || parsed.fontScale > 1) {
        return { accessibilityMode: true, fontScale: parsed.fontScale ?? MODE_BASE_SCALE };
      }
    }
  } catch {
    /* ignore */
  }
  return { accessibilityMode: false, fontScale: MODE_BASE_SCALE };
}

function settingsFromMode(mode: boolean, fontScale: number): AccessibilitySettings {
  if (!mode) return { ...DEFAULT_SETTINGS };
  return { fontScale, ...MODE_FEATURES };
}

function applyToDocument(settings: AccessibilitySettings, mode: boolean) {
  const html = document.documentElement;
  html.style.setProperty('--a11y-font-scale', String(settings.fontScale));
  html.classList.toggle('a11y-high-contrast', settings.highContrast);
  html.classList.toggle('a11y-underline-links', settings.underlineLinks);
  html.classList.toggle('a11y-reduce-motion', settings.reduceMotion);
  html.classList.toggle('a11y-readable-font', settings.readableFont);
  html.classList.toggle('a11y-mode-active', mode);
}

export function AccessibilityProvider({ children }: { children: ReactNode }) {
  const [stored, setStored] = useState<StoredA11y>(loadStored);
  const settings = settingsFromMode(stored.accessibilityMode, stored.fontScale);

  useEffect(() => {
    applyToDocument(settings, stored.accessibilityMode);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
  }, [settings, stored]);

  const toggleAccessibilityMode = useCallback(() => {
    setStored((prev) => {
      if (prev.accessibilityMode) {
        return { accessibilityMode: false, fontScale: MODE_BASE_SCALE };
      }
      return { accessibilityMode: true, fontScale: MODE_BASE_SCALE };
    });
  }, []);

  const increaseFont = useCallback(() => {
    setStored((prev) => {
      if (!prev.accessibilityMode) return prev;
      return {
        ...prev,
        fontScale: Math.min(1.5, +(prev.fontScale + 0.125).toFixed(3)),
      };
    });
  }, []);

  const decreaseFont = useCallback(() => {
    setStored((prev) => {
      if (!prev.accessibilityMode) return prev;
      return {
        ...prev,
        fontScale: Math.max(0.875, +(prev.fontScale - 0.125).toFixed(3)),
      };
    });
  }, []);

  const value: AccessibilityContextValue = {
    accessibilityMode: stored.accessibilityMode,
    settings,
    toggleAccessibilityMode,
    increaseFont,
    decreaseFont,
  };

  return <AccessibilityContext.Provider value={value}>{children}</AccessibilityContext.Provider>;
}

export function useAccessibility() {
  const ctx = useContext(AccessibilityContext);
  if (!ctx) throw new Error('useAccessibility must be used within AccessibilityProvider');
  return ctx;
}
