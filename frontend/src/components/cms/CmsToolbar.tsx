import { useState } from 'react';
import { useCms } from '../../context/CmsContext';
import {
  Save,
  LogOut,
  Eye,
  Pencil,
  GripVertical,
  EyeOff,
  Loader2,
} from 'lucide-react';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import type { SiteSection } from '../../types/site';

function SortableSectionItem({ section }: { section: SiteSection }) {
  const { toggleSection } = useCms();
  const { attributes, listeners, setNodeRef, transform, transition } = useSortable({
    id: section.id,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className="flex items-center gap-2 bg-white rounded-lg px-3 py-2 shadow-sm border border-slate-200"
    >
      <button {...attributes} {...listeners} className="cursor-grab text-slate-400 hover:text-slate-600">
        <GripVertical size={16} />
      </button>
      <span className="flex-1 text-sm font-medium text-slate-700 capitalize">
        {section.type.replace('-', ' ')}
      </span>
      <button
        onClick={() => toggleSection(section.id)}
        className={`p-1 rounded ${section.enabled ? 'text-green-600' : 'text-slate-400'}`}
        title={section.enabled ? 'Ocultar seção' : 'Mostrar seção'}
      >
        {section.enabled ? <Eye size={16} /> : <EyeOff size={16} />}
      </button>
    </div>
  );
}

export function CmsToolbar() {
  const {
    isAuthenticated,
    isEditing,
    setIsEditing,
    save,
    logout,
    isSaving,
    hasChanges,
    content,
    reorderSections,
  } = useCms();

  const [showPanel, setShowPanel] = useState(false);

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  );

  if (!isAuthenticated) return null;

  const sections = [...(content?.sections ?? [])].sort((a, b) => a.order - b.order);

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;
    const oldIndex = sections.findIndex((s) => s.id === active.id);
    const newIndex = sections.findIndex((s) => s.id === over.id);
    reorderSections(arrayMove(sections, oldIndex, newIndex));
  };

  return (
    <>
      <div className="fixed bottom-6 right-6 z-[9999] flex flex-col items-end gap-3">
        {showPanel && isEditing && (
          <div className="bg-slate-900 text-white rounded-2xl shadow-2xl p-4 w-72 mb-2">
            <h3 className="font-semibold text-sm mb-3 text-slate-300">Reordenar seções</h3>
            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
              <SortableContext items={sections.map((s) => s.id)} strategy={verticalListSortingStrategy}>
                <div className="space-y-2 max-h-64 overflow-y-auto">
                  {sections.map((section) => (
                    <SortableSectionItem key={section.id} section={section} />
                  ))}
                </div>
              </SortableContext>
            </DndContext>
            <p className="text-xs text-slate-500 mt-3">
              Clique nos textos para editar. Arraste para reordenar.
            </p>
          </div>
        )}

        <div className="flex items-center gap-2 bg-slate-900 text-white rounded-full shadow-2xl px-2 py-2">
          <button
            onClick={() => setIsEditing(!isEditing)}
            className={`flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-colors ${
              isEditing ? 'bg-brand-500 hover:bg-brand-600' : 'hover:bg-slate-800'
            }`}
          >
            {isEditing ? <Pencil size={16} /> : <Eye size={16} />}
            {isEditing ? 'Editando' : 'Visualizar'}
          </button>

          {isEditing && (
            <>
              <button
                onClick={() => setShowPanel(!showPanel)}
                className="px-3 py-2 rounded-full text-sm hover:bg-slate-800"
              >
                Seções
              </button>
              <button
                onClick={save}
                disabled={!hasChanges || isSaving}
                className="flex items-center gap-2 px-4 py-2 rounded-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-sm font-medium"
              >
                {isSaving ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />}
                Salvar
              </button>
            </>
          )}

          <button
            onClick={logout}
            className="flex items-center gap-2 px-3 py-2 rounded-full hover:bg-slate-800 text-sm"
            title="Sair"
          >
            <LogOut size={16} />
          </button>
        </div>
      </div>

      {isEditing && (
        <div className="fixed top-0 left-0 right-0 z-[9998] bg-brand-500 text-white text-center py-1.5 text-sm font-medium">
          Modo de edição ativo — clique em qualquer texto para editar
        </div>
      )}
    </>
  );
}