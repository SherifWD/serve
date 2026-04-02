# POS Dashboard

## Requirements

- Node.js 18+
- npm

## Local Development

```bash
npm install
VITE_API_BASE_URL=http://127.0.0.1:8000/api npm run dev
```

## Production Build

```bash
npm install
VITE_API_BASE_URL=https://your-backend-domain/api npm run build
```

Deploy the generated files from `dist/`.

Do not use `npm run dev` as the production server on shared hosting. `vite dev` is only for local development.
