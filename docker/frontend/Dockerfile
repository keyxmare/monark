FROM node:22-alpine AS base

RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /app

FROM base AS dev

RUN mkdir -p /app/node_modules && chown -R node:node /app

USER node

EXPOSE 3000

FROM base AS prod

COPY frontend/package.json frontend/pnpm-lock.yaml* ./

RUN pnpm install --frozen-lockfile

COPY frontend/ .

RUN pnpm build

RUN pnpm add serve

EXPOSE 3000

CMD ["pnpm", "exec", "serve", "-s", "dist", "-l", "3000"]
