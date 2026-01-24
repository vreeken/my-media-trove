FROM node:20-alpine

# Install git for potential npm packages that need it
RUN apk add --no-cache git

# Set working directory
WORKDIR /app

# Create node_modules directory and set ownership
RUN mkdir -p /app/node_modules && chown -R node:node /app

# Note: Running as root for npm install compatibility with mounted volumes
# The dev server will still work fine with root permissions in development

EXPOSE 5173

CMD ["npm", "run", "dev"]
