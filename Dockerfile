# --- Stage 1: Build Dependencies ---
FROM node:20-slim AS builder
WORKDIR /usr/src/app
COPY package.json package-lock.json ./
RUN npm install --omit=dev

# --- Stage 2: Final Runtime Image ---
FROM node:20-slim
WORKDIR /usr/src/app
COPY --from=builder /usr/src/app/node_modules ./node_modules
COPY . .
USER node
EXPOSE 8080
CMD ["node", "index.js"]
