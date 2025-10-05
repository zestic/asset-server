# Template Setup Guide

This guide helps you customize your new API project after creating it from the template.

## 1. Initial Customization

### Update Project Information
1. **Update `composer.json`**:
   ```bash
   # Change the name to your project
   "name": "your-org/your-api-name"
   
   # Update description
   "description": "Your API description"
   
   # Update homepage and support URLs
   "homepage": "https://github.com/your-org/your-api-name"
   ```

2. **Update `README.md`**:
   - Change the title from "Zestic API Template" to your project name
   - Update the description and features to match your project
   - Remove or update the template badge

3. **Update Environment Configuration**:
   ```bash
   # Copy and customize environment file
   cp .env.example .env
   
   # Update database names in .env:
   POSTGRES_DB=your_api_name
   DB_NAME=your_api_name
   
   # Update other configuration as needed
   ```

### Database Configuration
1. **Update database names** in these files:
   - `.env` (as shown above)
   - `.github/workflows/lint.yml` (line 18, 33)
   - `.github/workflows/test.yml` (line 18, 55)
   - `README.md` (database configuration section)

2. **Generate authentication keys**:
   ```bash
   # Generate private key for JWT tokens
   openssl genrsa -out private.key 2048
   
   # Update .env with the path to your private key
   AUTH_PRIVATE_KEY=/path/to/your/private.key
   
   # Generate encryption key
   AUTH_ENCRYPTION_KEY=$(openssl rand -base64 32)
   ```

## 2. Development Setup

1. **Start the development environment**:
   ```bash
   docker compose up -d
   ```

2. **Install dependencies**:
   ```bash
   docker compose exec app-api composer install
   ```

3. **Run database migrations**:
   ```bash
   docker compose exec app-api composer migrate
   ```

4. **Seed the database** (optional):
   ```bash
   docker compose exec app-api composer seed
   ```

## 3. Customization Areas

### GraphQL Schema
- Edit `resources/graphql/workspace.graphql` to define your API schema
- Add your domain-specific types, queries, and mutations

### Domain Logic
- Add your business logic in `src/Domain/`
- Create entities, value objects, and domain services
- Follow the existing Profile example as a pattern

### Infrastructure
- Add repositories in `src/Infrastructure/`
- Create database migrations in `resources/db/migrations/`
- Add seeds in `resources/db/seeds/`

### Application Layer
- Add GraphQL resolvers and handlers in `src/Application/`
- Configure new services in `config/autoload/`

## 4. Testing

1. **Run the test suite**:
   ```bash
   docker compose exec app-api composer test
   ```

2. **Add your own tests**:
   - Unit tests in `tests/Unit/`
   - Integration tests in `tests/Integration/`

## 5. Deployment Preparation

1. **Update GitHub Actions**:
   - Customize `.github/workflows/` for your deployment needs
   - Update environment variables and secrets

2. **Production Configuration**:
   - Create production-specific config files
   - Set up proper environment variables
   - Configure logging and monitoring

## 6. Clean Up

After customization, you can:
1. Delete this `TEMPLATE_SETUP.md` file
2. Update the README.md to remove template-specific content
3. Commit your initial customizations

## Need Help?

- Check the main [README.md](README.md) for detailed development instructions
- Review the existing code examples (Profile entity, authentication)
- Open an issue in the original template repository for template-specific questions
