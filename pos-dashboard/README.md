# POS Dashboard

## Requirements

- Node.js 18+
- npm

## Local Development

```bash
npm install
VITE_DEPLOY_TARGET=local npm run dev
```

## Production Build

```bash
npm install
VITE_DEPLOY_TARGET=prod npm run build
```

Deploy the generated files from `dist/`.

Do not use `npm run dev` as the production server on shared hosting. `vite dev` is only for local development.
