# Build frontend
FROM node:22-alpine AS frontend-build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./frontend/
RUN npm ci --prefix frontend --ignore-scripts
COPY frontend ./frontend
ARG VITE_SITE_URL=https://andradeisencoes.com.br
ARG VITE_GA_MEASUREMENT_ID=G-BM0PH4LDQG
ARG VITE_GA4_PROPERTY_ID=347102827
ARG BUILD_CACHE_BUST=1
RUN echo "cache bust: ${BUILD_CACHE_BUST}" && printf 'VITE_SITE_URL=%s\nVITE_GA_MEASUREMENT_ID=%s\nVITE_GA4_PROPERTY_ID=%s\n' \
    "$VITE_SITE_URL" "$VITE_GA_MEASUREMENT_ID" "$VITE_GA4_PROPERTY_ID" > frontend/.env.production
RUN echo "GA4 measurement: ${VITE_GA_MEASUREMENT_ID}" && npm run build --prefix frontend

# Build server
FROM node:22-alpine AS server-build
WORKDIR /app
COPY server/package.json server/package-lock.json ./server/
RUN npm ci --prefix server --ignore-scripts
COPY server ./server
RUN npm run build --prefix server

# Runtime
FROM node:22-alpine
WORKDIR /app
ENV NODE_ENV=production
ENV PORT=8080

COPY server/package.json server/package-lock.json ./server/
RUN npm ci --prefix server --omit=dev --ignore-scripts

COPY server/data ./server/data
COPY --from=server-build /app/server/dist ./server/dist
COPY --from=frontend-build /app/frontend/dist ./frontend/dist

EXPOSE 8080
WORKDIR /app/server
CMD ["node", "dist/index.js"]
