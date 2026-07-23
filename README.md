# Andrade Isenções — site React + CMS

Site migrado do WordPress para React (Vite) com API Express e CMS visual.

## Desenvolvimento local

```bash
npm install
npm install --prefix frontend
npm install --prefix server
npm run dev
```

- Site: http://localhost:5200  
- API: http://localhost:3002  
- Admin: http://localhost:5200/admin (`admin` / `admin123`)

## Deploy na Vercel

1. Faça push deste repositório para o GitHub.
2. Em [vercel.com](https://vercel.com), **Add New Project** → importe `jorgepsendziuk/andrade`.
3. A Vercel detecta o `vercel.json` automaticamente.
4. Em **Environment Variables**, adicione:
   - `JWT_SECRET` — segredo forte para tokens do CMS
5. Deploy.

O frontend é estático; as rotas `/api/*` rodam como serverless function.

**Limitações na Vercel (serverless):** salvar conteúdo pelo CMS e upload de arquivos não persistem entre deploys. O site público funciona com o conteúdo em `server/data/site-content.json`. Para CMS em produção com persistência, use banco ou blob storage (ex.: Supabase, Vercel Blob).

## Estrutura

- `frontend/` — React + Tailwind  
- `server/` — API Express + `data/site-content.json`  
- `api/` — entrada serverless para Vercel  
