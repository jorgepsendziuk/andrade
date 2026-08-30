# Testes automatizados — Andrade Isenções

## Visão geral

O projeto usa **Vitest** para testes unitários e de integração:

| Pacote   | Comando              | Escopo                                      |
|----------|----------------------|---------------------------------------------|
| Raiz     | `npm test`           | Server + frontend                           |
| Server   | `npm run test:server`| API, stores, utilitários, backup            |
| Frontend | `npm run test:frontend` | Funções puras (grid, paths)              |

## Executar

```bash
# Todos os testes
npm test

# Apenas backend
cd server && npm test

# Modo watch (desenvolvimento)
cd server && npm run test:watch
```

## Estrutura

```
server/src/__tests__/
  setup.ts                 # Isola dados em diretório temporário
  unit/                    # Funções e stores (modo arquivo)
  integration/api.test.ts  # Rotas HTTP com supertest

frontend/src/lib/
  *.test.ts                # Utilitários do painel admin
```

## Ambiente de teste

- `CONTACTS_STORAGE=file` — sem Firestore real
- `ANDRADE_DATA_DIR` — pasta temporária por execução
- Usuários seed: `admin@test.com` / `Test@1234` (role admin)

## Correção Firestore (`undefined`)

Campos opcionais (ex.: `rg`) não podem ser `undefined` no Firestore.

1. `stripUndefined()` em `server/src/firestore-utils.ts` — usado em todos os `.set()`
2. `ignoreUndefinedProperties: true` no cliente Firestore

## Adicionar novos testes

1. **Unitário (função pura):** `server/src/__tests__/unit/nome.test.ts`
2. **Store:** usar dados do `setup.ts`; CPF/e-mail únicos com `Date.now()`
3. **Rota API:** `server/src/__tests__/integration/api.test.ts` ou arquivo dedicado
4. **Frontend:** `frontend/src/**/*.test.ts` ao lado do módulo

## CI (sugestão)

```yaml
- run: npm test
  env:
    NODE_ENV: test
```

## Cobertura atual

- Utilitários: Firestore, slugs, docs, process-constants, auth
- Stores: clients (sem RG), processes, conductors, backup local
- API: health, login, users, portal register, processos, docs browse, backup cron, contato
- Frontend: process-grid-utils, storage-paths
