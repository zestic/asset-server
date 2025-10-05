# Zestic API Template

*A production-ready GraphQL API template built with Mezzio, FrankenPHP, and PostgreSQL*

[![Use this template](https://img.shields.io/badge/use%20this-template-blue?logo=github)](https://github.com/zestic/api-template/generate)

This template provides a complete foundation for building modern GraphQL APIs with authentication, database migrations, and comprehensive development tooling.

## 🚀 Quick Start with This Template

1. **[Use this template](https://github.com/zestic/api-template/generate)** to create a new repository
2. Clone your new repository locally
3. Follow the [Getting Started](#getting-started) instructions below
4. Customize the application for your specific needs

## Features

- **GraphQL-First Architecture**: All data operations through `/graphql` endpoint
- **Modern PHP Stack**: PHP 8.4 with FrankenPHP for high performance
- **Authentication Ready**: OAuth2 with PKCE, magic links, and JWT tokens
- **Database Migrations**: Automated PostgreSQL migrations with Phinx
- **Development Tools**: Docker, Xdebug, PHPUnit, PHPCS, PHPStan
- **CI/CD Ready**: GitHub Actions for testing and code quality
- **Vector Database**: Weaviate integration for AI/ML features

Built on [mezzio](https://github.com/mezzio/mezzio) which builds on
[laminas-stratigility](https://github.com/laminas/laminas-stratigility) to
provide a minimalist PSR-15 middleware framework for PHP with routing, DI
container, optional templating, and optional error handling capabilities.

## Development Environment

This project uses Docker for development to ensure consistent environments. The setup includes:

- PHP 8.4 with FrankenPHP (high-performance PHP application server)
- Built-in Caddy web server with HTTP/2 and automatic HTTPS
- Xdebug support for development debugging
- Local PostgreSQL database for authentication
- External Weaviate vector database access via Docker networks
- Development tools (PHPUnit, PHPCS, Psalm)

**Architecture**: This is a GraphQL-first API application. All data operations go through the `/graphql` endpoint rather than traditional REST endpoints.

### Getting Started

1. Start the development environment:
```bash
docker compose up -d
```

2. Install dependencies:
```bash
docker compose exec app-api composer install
```

3. Set up environment variables (database configuration is now environment-based):
```bash
# Copy the example environment file
cp .env.example .env
# Edit .env with your database credentials
```

4. Run database migrations:
```bash
docker compose exec app-api composer migrate
```

5. Access the application:
```bash
# The application will be available at:
http://localhost:8088

# Health check endpoint (returns JSON with PostgreSQL status):
http://localhost:8088/health

# Ping endpoint:
http://localhost:8088/ping

# GraphQL endpoint:
http://localhost:8088/graphql
```

### Health Check

The `/health` endpoint provides comprehensive health status information:

```json
{
  "status": "ok",
  "timestamp": 1640995200,
  "checks": {
    "postgres": {
      "status": "ok",
      "message": "PostgreSQL database connection successful"
    }
  }
}
```

**Status Codes:**
- `200 OK` - All health checks pass
- `503 Service Unavailable` - One or more health checks fail

**Configuration:**
- Docker health checks are disabled in development (commented out in Dockerfile)
- Uncomment the `HEALTHCHECK` directive in production for monitoring

**Future Enhancements:**
- Weaviate vector database connectivity check will be added

### Services and Ports

The development environment exposes the following services:

| Service | Internal Port | External Port | URL |
|---------|---------------|---------------|-----|
| API Application | 80 | 8088 | http://localhost:8088 |
| PostgreSQL Database | 5432 | 5434 | localhost:5434 |

**Note**: External Weaviate access is configured via Docker networks and doesn't expose ports directly.

### Development Tools

All development tools run inside the Docker container to ensure consistency:

```bash
# Run all checks (code style, static analysis, and tests)
docker compose exec app-api composer check

# Individual tools
docker compose exec app-api composer cs-check     # Code style check
docker compose exec app-api composer cs-fix       # Code style fix
docker compose exec app-api composer test         # Unit tests
docker compose exec app-api composer static-analysis  # Static analysis

# Database migrations
docker compose exec app-api composer migrate         # Run migrations
docker compose exec app-api composer migrate-status  # Check migration status
docker compose exec app-api composer migrate-rollback # Rollback last migration

# Database seeds
docker compose exec app-api composer seed            # Run all seeds
docker compose exec app-api composer seed-create     # Create a new seed file
```

### Development Mode

This project includes [laminas-development-mode](https://github.com/laminas/laminas-development-mode).

```bash
# Enable development mode
docker compose exec app-api composer development-enable

# Disable development mode
docker compose exec app-api composer development-disable

# Check status
docker compose exec app-api composer development-status
```

**Note:** Development mode enables:
- Detailed error pages with Whoops
- Configuration caching disabled
- Development-specific middleware

### Xdebug Configuration

Xdebug is pre-configured for development debugging. To use it with your IDE:

#### VS Code
1. Install the "PHP Debug" extension
2. Create a `.vscode/launch.json` file:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/app": "${workspaceFolder}"
            }
        }
    ]
}
```

#### PhpStorm
1. Go to Settings → PHP → Debug
2. Set Xdebug port to 9003
3. Configure path mappings: `/app` → your project root
4. Set the IDE key to `PHPSTORM` in your environment:
```bash
export XDEBUG_IDEKEY=PHPSTORM
```

#### Environment Variables
You can customize Xdebug behavior with these environment variables:
- `XDEBUG_MODE`: Set debug modes (default: `debug,develop`)
- `XDEBUG_CLIENT_HOST`: IDE host (default: `host.docker.internal`)
- `XDEBUG_CLIENT_PORT`: IDE port (default: `9003`)
- `XDEBUG_IDEKEY`: IDE identifier (default: `VSCODE`)



### Database Configuration

The development environment includes a local PostgreSQL database with the following default settings:

- **Database Name**: `zestic_api`
- **Username**: `zestic`
- **Password**: `password1`
- **Host**: `postgres` (container name)
- **Port**: `5434` (external), `5432` (internal)

The database configuration is environment-based and will automatically use the environment variables you set in your `.env` file. The configuration will fail fast if any required database environment variables are not set.

#### Connecting from Host Machine

To connect to the database from your host machine (e.g., using a database client):

```bash
# Connection details for external access
Host: localhost
Port: 5434
Database: zestic_api
Username: zestic
Password: password1
```

#### Environment Variables

You can customize the database configuration using these environment variables in your `.env` file:

```bash
# PostgreSQL Docker container configuration
POSTGRES_DB=zestic_api
POSTGRES_USER=zestic
POSTGRES_PASSWORD=password1

# Application database connection configuration
DB_HOST=postgres
DB_PORT=5432
DB_NAME=zestic_api
DB_USER=zestic
DB_PASSWORD=password1
DB_SCHEMA=public
```

### External Services

#### Weaviate Vector Database

The application is configured to connect to an external Weaviate instance via Docker networks:

- **Default URL**: `http://weaviate:8080`
- **Network**: `zestic-api-network`

To connect to a different Weaviate instance, set the `WEAVIATE_URL` environment variable:

```bash
WEAVIATE_URL=http://your-weaviate-host:8080
```

**Note**: The Weaviate service should be on the same Docker network (`zestic-api-network`) for container-to-container communication.

### Adding PHP Extensions

The FrankenPHP Docker image includes common PHP extensions, but you may need to add additional ones. To add PHP extensions:

1. **Install system dependencies** (if required) in the Dockerfile:
```dockerfile
RUN apt-get update && apt-get install -y \
    libicu-dev \        # For intl extension
    libxslt1-dev \      # For xsl extension
    libxml2-dev \       # For xml-related extensions
    && rm -rf /var/lib/apt/lists/*
```

2. **Install PHP extensions** using `docker-php-ext-install`:
```dockerfile
RUN docker-php-ext-install \
    intl \
    xsl \
    bcmath \
    gd
```

3. **For PECL extensions** (like xdebug):
```dockerfile
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
```

4. **Rebuild the container** after adding extensions:
```bash
docker compose down
docker compose up -d --build
```

**Current Extensions**: The template includes `pdo`, `pdo_pgsql`, `intl`, `xsl`, and `xdebug` extensions.

## Configuration Caching

By default, the skeleton will create a configuration cache in
`data/config-cache.php`. When in development mode, the configuration cache is
disabled, and switching in and out of development mode will remove the
configuration cache.

You may need to clear the configuration cache in production when deploying if
you deploy to the same directory. You may do so using the following:

```bash
docker compose exec app-api composer clear-config-cache
```

## Contributing

Before contributing read [the contributing guide](https://github.com/mezzio/.github/blob/master/CONTRIBUTING.md).

## Troubleshooting

If you encounter issues:

1. Ensure Docker is running and containers are healthy:
```bash
docker compose ps
```

2. Check container logs:
```bash
docker compose logs
```

3. Try rebuilding the containers:
```bash
docker compose down
docker compose up -d --build
```

4. Clear Composer's cache:
```bash
docker compose exec app-api composer clear-cache
```

For more detailed troubleshooting, refer to the [Mezzio documentation](https://docs.mezzio.dev/).
