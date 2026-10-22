FROM node:22-alpine

# Installi l-CLI
RUN npm install -g @angular/cli

WORKDIR /app


COPY . .


RUN mkdir -p /app/.angular/cache && \
    chmod -R 777 /app/.angular /app/node_modules

EXPOSE 4200

# Lanz
CMD ["ng", "serve", "--host", "0.0.0.0", "--poll", "2000"]