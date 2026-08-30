import { Accessibility } from 'lucide-react';
import { useAccessibility } from '../../context/AccessibilityContext';

interface AccessibilityControlProps {
  variant: 'header' | 'fab';
}

function ZoomRow() {
  const { settings, increaseFont, decreaseFont } = useAccessibility();
  const fontPercent = Math.round(settings.fontScale * 100);

  return (
    <div className="a11y-zoom-row" role="group" aria-label="Controle de zoom do texto">
      <button
        type="button"
        onClick={decreaseFont}
        disabled={settings.fontScale <= 0.875}
        className="a11y-zoom-btn"
        aria-label="Diminuir tamanho do texto"
      >
        A−
      </button>
      <span className="a11y-zoom-value" aria-live="polite">
        {fontPercent}%
      </span>
      <button
        type="button"
        onClick={increaseFont}
        disabled={settings.fontScale >= 1.5}
        className="a11y-zoom-btn"
        aria-label="Aumentar tamanho do texto"
      >
        A+
      </button>
    </div>
  );
}

export function AccessibilityControl({ variant }: AccessibilityControlProps) {
  const { accessibilityMode, toggleAccessibilityMode } = useAccessibility();

  if (variant === 'fab') {
    return (
      <div className={`a11y-fab-wrap ${accessibilityMode ? 'is-active' : ''}`}>
        <div className="a11y-fab-unit">
          {accessibilityMode && <ZoomRow />}
          <button
            type="button"
            onClick={toggleAccessibilityMode}
            aria-pressed={accessibilityMode}
            aria-label={
              accessibilityMode
                ? 'Desativar modo acessibilidade'
                : 'Ativar modo acessibilidade'
            }
            className="a11y-fab"
          >
            <Accessibility size={20} aria-hidden />
            <span className="a11y-fab-label">Acessibilidade</span>
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className={`a11y-header-wrap ${accessibilityMode ? 'is-active' : ''}`}>
      <div className="a11y-header-unit">
        <button
          type="button"
          onClick={toggleAccessibilityMode}
          aria-pressed={accessibilityMode}
          aria-label={
            accessibilityMode
              ? 'Desativar modo acessibilidade'
              : 'Ativar modo acessibilidade'
          }
          className="a11y-header-btn"
        >
          <Accessibility size={18} aria-hidden />
        </button>
        {accessibilityMode && <ZoomRow />}
      </div>
    </div>
  );
}
