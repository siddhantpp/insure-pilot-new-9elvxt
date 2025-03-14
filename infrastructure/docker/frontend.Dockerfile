# Build stage for the React application
FROM node:16-alpine as build
WORKDIR /app

# Copy package files first for better layer caching
COPY src/web/package.json src/web/package-lock.json ./

# Install dependencies with npm ci for reproducible builds
RUN npm ci --silent

# Copy application source
COPY src/web/ ./

# Set environment variables for production build
ENV NODE_ENV=production
ENV REACT_APP_API_URL=/api

# Build the application
RUN npm run build

# Final stage for production deployment
FROM nginx:alpine
# Copy NGINX configuration
COPY infrastructure/docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copy built application from the build stage
COPY --from=build /app/build /usr/share/nginx/html

# Create health check endpoints for Kubernetes
RUN mkdir -p /usr/share/nginx/html/health
RUN echo 'OK' > /usr/share/nginx/html/health/index.html
RUN echo 'OK' > /usr/share/nginx/html/health/ready

# Expose port for the web server
EXPOSE 80

# Configure health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD wget --quiet --tries=1 --spider http://localhost:80/health || exit 1

# Start NGINX server in foreground
CMD ["nginx", "-g", "daemon off;"]