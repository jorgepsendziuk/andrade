import { useEffect, useState } from 'react';
import { Loader2, Plus, Shield, UserCheck, UserX } from 'lucide-react';
import {
  changePassword,
  createUser,
  deactivateUser,
  fetchMe,
  fetchUsers,
  updateUser,
} from '../../lib/api';
import type { AdminUser, UserRole } from '../../types/user';
import { SeoHead } from '../../components/seo/SeoHead';

const ROLE_LABEL: Record<UserRole, string> = {
  admin: 'Administrador',
  editor: 'Editor',
  comercial: 'Comercial',
};

export function AdminUsersPage() {
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [me, setMe] = useState<AdminUser | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ email: '', name: '', password: '', role: 'comercial' as UserRole });
  const [pwdForm, setPwdForm] = useState({ current: '', next: '', confirm: '' });
  const [pwdMsg, setPwdMsg] = useState('');

  const load = () => {
    setLoading(true);
    Promise.all([fetchUsers(), fetchMe()])
      .then(([u, m]) => {
        setUsers(u);
        setMe(m);
      })
      .catch((e) => setError(e instanceof Error ? e.message : 'Erro'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    try {
      await createUser(form);
      setShowForm(false);
      setForm({ email: '', name: '', password: '', role: 'comercial' });
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao criar');
    }
  };

  const handlePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setPwdMsg('');

    if (pwdForm.next.length < 8) {
      setPwdMsg('A nova senha deve ter no mínimo 8 caracteres.');
      return;
    }

    if (pwdForm.next !== pwdForm.confirm) {
      setPwdMsg('A confirmação deve ser igual à nova senha (não à senha atual).');
      return;
    }

    try {
      await changePassword(pwdForm.current, pwdForm.next);
      setPwdMsg('Senha alterada com sucesso!');
      setPwdForm({ current: '', next: '', confirm: '' });
      load();
    } catch (err) {
      setPwdMsg(err instanceof Error ? err.message : 'Erro ao alterar senha');
    }
  };

  const passwordFields = (
    <>
      <p className="text-sm text-slate-500">
        Informe sua senha atual e escolha uma nova senha. Os dois últimos campos devem ser iguais entre si.
      </p>
      {pwdMsg && (
        <p
          className={`text-sm px-3 py-2 rounded-lg ${
            pwdMsg.includes('sucesso')
              ? 'bg-green-50 text-green-700'
              : 'bg-amber-50 text-amber-800'
          }`}
        >
          {pwdMsg}
        </p>
      )}
      <div className="space-y-3">
        <label className="block">
          <span className="text-sm font-medium text-slate-700">Senha atual</span>
          <input
            type="password"
            value={pwdForm.current}
            onChange={(e) => {
              setPwdForm((p) => ({ ...p, current: e.target.value }));
              setPwdMsg('');
            }}
            className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
            autoComplete="current-password"
            required
          />
        </label>
        <label className="block">
          <span className="text-sm font-medium text-slate-700">Nova senha</span>
          <input
            type="password"
            value={pwdForm.next}
            onChange={(e) => {
              setPwdForm((p) => ({ ...p, next: e.target.value }));
              setPwdMsg('');
            }}
            className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
            autoComplete="new-password"
            required
            minLength={8}
          />
          <span className="text-xs text-slate-400 mt-0.5 block">Mínimo de 8 caracteres</span>
        </label>
        <label className="block">
          <span className="text-sm font-medium text-slate-700">Confirmar nova senha</span>
          <input
            type="password"
            value={pwdForm.confirm}
            onChange={(e) => {
              setPwdForm((p) => ({ ...p, confirm: e.target.value }));
              setPwdMsg('');
            }}
            className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
            autoComplete="new-password"
            required
            minLength={8}
          />
        </label>
      </div>
      <button type="submit" className="w-full bg-brand-500 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-brand-600">
        Salvar nova senha
      </button>
    </>
  );

  if (me && me.role !== 'admin') {
    return (
      <div className="max-w-lg mx-auto">
        <SeoHead title="Minha conta | Portal Andrade" description="Painel administrativo da conta." noindex />
        <h1 className="text-2xl font-bold text-brand-800 mb-6">Minha conta</h1>
        <form onSubmit={handlePassword} className="bg-white rounded-xl border p-6 space-y-4">
          <h2 className="font-semibold text-brand-800">Alterar minha senha</h2>
          {passwordFields}
        </form>
      </div>
    );
  }

  return (
    <>
      <SeoHead title="Usuários | Portal Andrade" description="Painel administrativo de usuários." noindex />
      <div className="max-w-4xl mx-auto space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-2xl font-bold text-brand-800">Usuários</h1>
            <p className="text-slate-500 text-sm">Gerencie quem acessa o painel</p>
          </div>
          <button
            type="button"
            onClick={() => setShowForm(!showForm)}
            className="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg text-sm font-medium"
          >
            <Plus size={16} />
            Novo usuário
          </button>
        </div>

        {error && <div className="bg-red-50 text-red-700 px-4 py-3 rounded-lg text-sm">{error}</div>}

        {showForm && (
          <form onSubmit={handleCreate} className="bg-white rounded-xl border p-5 grid sm:grid-cols-2 gap-4">
            <input
              type="email"
              placeholder="E-mail (login)"
              value={form.email}
              onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
              className="border rounded-lg px-3 py-2 text-sm"
              required
            />
            <input
              type="text"
              placeholder="Nome"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              className="border rounded-lg px-3 py-2 text-sm"
              required
            />
            <input
              type="password"
              placeholder="Senha inicial"
              value={form.password}
              onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
              className="border rounded-lg px-3 py-2 text-sm"
              required
            />
            <select
              value={form.role}
              onChange={(e) => setForm((f) => ({ ...f, role: e.target.value as UserRole }))}
              className="border rounded-lg px-3 py-2 text-sm"
            >
              <option value="admin">Administrador</option>
              <option value="editor">Editor</option>
              <option value="comercial">Comercial</option>
            </select>
            <button type="submit" className="sm:col-span-2 bg-brand-500 text-white py-2 rounded-lg text-sm font-medium">
              Criar usuário
            </button>
          </form>
        )}

        <div className="bg-white rounded-xl border p-5 space-y-4">
          <h2 className="font-semibold flex items-center gap-2 text-brand-800">
            <Shield size={18} />
            Alterar minha senha
          </h2>
          <form onSubmit={handlePassword} className="space-y-4 max-w-lg">
            {passwordFields}
          </form>
        </div>

        {loading ? (
          <div className="flex justify-center py-12">
            <Loader2 className="animate-spin text-brand-500" />
          </div>
        ) : (
          <div className="bg-white rounded-xl border overflow-hidden">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-slate-500">
                <tr>
                  <th className="text-left px-4 py-3 font-medium">Nome</th>
                  <th className="text-left px-4 py-3 font-medium hidden sm:table-cell">E-mail</th>
                  <th className="text-left px-4 py-3 font-medium">Perfil</th>
                  <th className="text-left px-4 py-3 font-medium">Status</th>
                  <th className="px-4 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y">
                {users.map((u) => (
                  <tr key={u.id} className={!u.active ? 'opacity-50' : ''}>
                    <td className="px-4 py-3 font-medium">{u.name}</td>
                    <td className="px-4 py-3 text-slate-500 hidden sm:table-cell">{u.email}</td>
                    <td className="px-4 py-3">
                      <select
                        value={u.role}
                        disabled={u.id === me?.id}
                        onChange={(e) =>
                          updateUser(u.id, { role: e.target.value as UserRole }).then(load)
                        }
                        className="border rounded px-2 py-1 text-xs"
                      >
                        <option value="admin">{ROLE_LABEL.admin}</option>
                        <option value="editor">{ROLE_LABEL.editor}</option>
                        <option value="comercial">{ROLE_LABEL.comercial}</option>
                      </select>
                    </td>
                    <td className="px-4 py-3">
                      <span
                        className={`text-xs px-2 py-0.5 rounded-full ${
                          u.active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'
                        }`}
                      >
                        {u.active ? 'Ativo' : 'Inativo'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right">
                      {u.id !== me?.id && u.active && (
                        <button
                          type="button"
                          onClick={() => deactivateUser(u.id).then(load)}
                          className="text-red-500 hover:text-red-700 p-1"
                          title="Desativar"
                        >
                          <UserX size={16} />
                        </button>
                      )}
                      {u.id !== me?.id && !u.active && (
                        <button
                          type="button"
                          onClick={() => updateUser(u.id, { active: true }).then(load)}
                          className="text-green-600 hover:text-green-800 p-1"
                          title="Ativar"
                        >
                          <UserCheck size={16} />
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  );
}
