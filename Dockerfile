FROM php:8.1-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    bash \
    make

# PHP 8.1+ has json extension built-in, no additional extensions needed

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy source code
COPY . .

# Create non-root user for security
RUN addgroup -g 1000 -S laneful && \
    adduser -u 1000 -S laneful -G laneful

# Change ownership of app directory
RUN chown -R laneful:laneful /app

# Switch to non-root user
USER laneful

# Default command
CMD ["php", "examples/send_email.php"]
