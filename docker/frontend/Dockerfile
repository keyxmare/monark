FROM node:22-alpine AS base

RUN corepack enable && corepack prepare pnpm@latest --activate

RUN addgroup -g 1000 app && \
    adduser -u 1000 -G app -s /bin/sh -D app

WORKDIR /app

FROM base AS dev

RUN chown -R app:app /app

USER app

EXPOSE 3000

FROM base AS prod

COPY frontend/package.json frontend/pnpm-lock.yaml* ./

RUN pnpm install --frozen-lockfile

COPY frontend/ .

RUN pnpm build

RUN chown -R app:app /app

USER app

RUN pnpm add serve

EXPOSE 3000

CMD ["pnpm", "exec", "serve", "-s", "dist", "-l", "3000"]
