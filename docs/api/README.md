# API Documentation

The API specification is defined in [openapi.yaml](openapi.yaml) using OpenAPI 3.1.

## Base URL

- Development: `http://localhost:8000/api`
- Production: `https://api.monark.dev/api`

## Authentication

API uses JWT Bearer tokens. Obtain a token via `POST /api/auth/login`.
