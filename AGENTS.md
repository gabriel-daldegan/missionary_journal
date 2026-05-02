# Agents.md

This file provides guidance to AI agents working on the SaaSykit project, including an overview of the project, development commands, architecture, coding standards, and environment setup.

## Project Overview

SaaSykit Tenancy is a  multi-tenant Laravel-based SaaS starter kit built with the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire). It provides a complete SaaS boilerplate with subscription management, payment processing, admin panels, and user dashboards powered by Filament.

SaaSykit Tenancy is a SaaS starter kit (boilerplate) that comes packed with all components required to run a modern SaaS software.

SaaSykit Tenancy is built with the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire), and offers an intuitive Filament admin panel that houses all the pre-built components like product, plans, discounts, payment providers, email providers, transactions, blog, user & role management, and much more.

### Features in a nutshell

* Multi-tenancy (SaaSykit Tenancy): Build multi-tenancy applications, seat-based subscriptions with a seamless checkout experience.
* Customize Styles: Customize the styles & colors, error page of your application to fit your brand.
* Product, Plans & Pricing: Create and manage your products, plans, and pricing from a beautiful and easy-to-use admin panel.
* Beautiful checkout process: Your customers can subscribe to your plans from a beautiful checkout process.
* Huge list of ready-to-use components: Plans & Pricing, hero section, features section, testimonials, FAQ, Call to action, tab slider, and much more.
* User authentication: Comes with user authentication out of the box, whether classic email/password or social login (Google, Facebook, Twitter, Github, LinkedIn, and more).
* Discounts: Create and manage your discounts and reward your customers.
* SaaS metric stats: View your MRR, Churn rates, ARPU, and other SaaS metrics.
* Multiple payment providers: Stripe, Paddle, Lemon Squeezy and Offline (manual) payments support out of the box.
* Multiple email providers: Mailgun, Postmark, Amazon SES, and more coming soon.
* Blog: Create and manage your blog posts.
* User & Role Management: Create and manage your users and roles, and assign permissions to your users.
* Fully translatable: Translate your application to any language you want.
* Sitemap & SEO: Sitemap and SEO optimization out of the box.
* Admin Panel: Manage your SaaS application from a beautiful admin panel powered by Filament.
* User Dashboard: Your customers can manage their subscriptions, change payment method, upgrade plan, cancel subscription, and more from a beautiful user dashboard powered by Filament.
* User Onboarding: Guide your users through the onboarding process with a beautiful onboarding wizard.
* Two-factor authentication: Secure your users' accounts with two-factor authentication.
* ReCaptcha: Protect your application from spam and abuse with Google reCAPTCHA.
* Roadmap: Let your users suggest features and vote on them and keep them updated on what's coming next.
* Automated Tests: Comes with automated tests for critical components of the application.
* One-line deployment: Provision your server and deploy your application easily with integrated Deployer support.
* Developer-friendly: Built with developers in mind, uses best coding practices.
*

## Development Commands

### Frontend Development
- `npm run dev` - Start Vite development server for asset compilation
- `npm run build` - Build assets for production

### Backend Development
- `php artisan serve` - Start Laravel development server
- `php artisan migrate` - Run database migrations
- `php artisan migrate:fresh --seed` - Fresh migration with seeders
- `php artisan queue:work` - Start queue worker
- `php artisan horizon` - Start Laravel Horizon for queue monitoring

### Testing & Quality
- `vendor/bin/phpunit` - Run PHPUnit tests
- `vendor/bin/phpunit --filter=TestName` - Run specific test
- `vendor/bin/phpstan analyse` - Run static analysis (level 3)
- `vendor/bin/pint` - Run Laravel Pint code formatter

### Deployment
- `php dep deploy` - Deploy using Deployer (configured in deploy.php)

## Architecture & Structure

### Core Directories
- `app/Filament/Admin/` - Admin panel resources and pages (Filament 4)
- `app/Filament/Dashboard/` - User dashboard resources (Filament 4)
- `app/Services/` - Business logic services (service layer pattern)
    - `PaymentProviders/` - Payment gateway implementations (Stripe, Paddle, Lemon Squeezy, Offline)
    - `VerificationProviders/` - User verification integrations
- `app/Models/` - Eloquent models with relationships
- `app/Livewire/` - Livewire components
    - `Auth/` - Authentication components
    - `Checkout/` - Checkout flow components
    - `Roadmap/` - Feature voting components
- `app/Http/` - Controllers and middleware
- `app/Notifications/` - Email/notification classes
- `app/Events/` - Domain events (Order, Subscription, User)
- `app/Listeners/` - Event listeners
- `app/Mail/` - Mailable classes (organized by domain)
- `app/Dto/` - Data Transfer Objects
- `app/Mapper/` - Data mappers
- `app/Constants/` - Application constants
- `app/Policies/` - Authorization policies
- `app/Validator/` - Custom validation rules
- `app/Console/Commands/` - Artisan commands
- `database/migrations/` - Database schema migrations
- `database/seeders/` - Database seeders
- `resources/views/` - Blade templates
- `resources/views/livewire/` - Livewire component views
- `tests/` - Automated tests (PHPUnit)

### Key Technologies
- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: Livewire 3 + Alpine.js + Tailwind CSS 4 + DaisyUI 5
- **Admin Interface**: Filament 4 with Spatie Media Library plugin
- **Asset Compilation**: Vite 7
- **Payments**: Stripe, Paddle, Lemon Squeezy, Offline (manual)
- **Queue System**: Laravel Horizon (Redis-based)
- **Authentication**:
    - Laravel Sanctum (API tokens)
    - Filament Breezy (auth UI)
    - Social login via Laravel Socialite (Google, Facebook, Twitter, GitHub, LinkedIn)
    - One-time passwords (Spatie)
    - Two-factor authentication (Laragear)
- **Email**: Supports Mailgun, Postmark, Amazon SES, Resend
- **SMS**: Twilio integration
- **Media**: Spatie Media Library + Intervention Image
- **Permissions**: Spatie Permission package (roles & permissions)
- **Testing**: PHPUnit, Static Analysis (PHPStan/Larastan level 3)
- **Code Quality**: Laravel Pint (PSR-12 formatting)
- **Debugging**: Laravel Telescope (dev), Laravel Debugbar (dev)
- **Deployment**: Deployer (automated deployment)

### Core Domain Models
Key models representing the business domain:
- **Tenant** - Tenants (for multi-tenancy)
- **User** - Users with roles, permissions, subscriptions
- **Product** - Products (subscription-based SaaS offerings)
- **Plan** - Subscription plans with pricing tiers
- **PlanPrice** - Pricing for plans (per interval/currency)
- **PlanMeter** - Usage-based billing meters
- **Subscription** - User subscriptions to plans
- **SubscriptionUsage** - Usage tracking for metered billing
- **OneTimeProduct** - One-time purchasable products
- **OneTimeProductPrice** - One-time product pricing
- **Order** - One-time product orders
- **Invoice** - Generated invoices (subscription/one-time)
- **Transaction** - Payment transactions
- **Discount** - Discount rules
- **DiscountCode** - Discount codes with redemption tracking
- **BlogPost** - Blog posts with categories
- **RoadmapItem** - Feature requests with user voting
- **Announcement** - User announcements
- **PaymentProvider** - Payment gateway configurations
- **EmailProvider** - Email service configurations
- **Currency** - Supported currencies
- **Address** - User/order addresses
- **Config** - Dynamic application configuration

### Service Layer
The application uses a service layer pattern. Key services:
- `TenantService` - Tenant management
- `TenantSubscriptionService` - Tenant subscription handling
- `TenantPermissionService` - Tenant roles/permissions
- `TenantCreationService` - Tenant onboarding
- `SubscriptionService` - Subscription lifecycle management
- `OrderService` - Order processing
- `CheckoutService` - Checkout flow logic
- `PlanService` - Plan management
- `DiscountService` - Discount application
- `InvoiceService` - Invoice generation
- `MetricsService` - SaaS metrics calculation (MRR, ARPU, churn)
- `TransactionService` - Transaction handling
- `UserService` - User management
- `LoginService` - Authentication logic
- `OneTimePasswordService` - OTP handling
- `BlogService` - Blog post management
- `RoadmapService` - Feature voting
- `CurrencyService` - Currency operations
- `ConfigService` - Dynamic configuration

### Payment Provider Architecture
Payment providers are abstracted via contracts in `app/Services/PaymentProviders/`:
- Each provider implements common interfaces
- Supports Stripe, Paddle, Lemon Squeezy, and Offline payments
- Provider-specific data stored in `*PaymentProviderData` models
- Webhooks handle provider callbacks

### Event-Driven Architecture
The application uses Laravel events for domain actions:
- **Order Events**: Order created, completed, failed
- **Subscription Events**: Created, updated, cancelled, renewed, trial started/ended
- **User Events**: Registered, verified, etc.
- Listeners handle side effects (emails, notifications, metrics)

## Coding Standards

### SaaSykit-Specific Conventions
- Services should be stateless and injected via dependency injection
- Use DTOs for complex data structures passed between layers
- Event/Listener pattern for side effects
- Filament for admin UI (avoid custom controllers when possible)
- Livewire for interactive frontend components
- Payment provider logic should be isolated in provider-specific classes
- All monetary amounts use the `Money` package
- Translations via `__()` function
- Use Spatie Permissions for authorization
- Queue long-running tasks (emails, webhooks, metrics)

### Database Conventions
- Use migrations for all schema changes
- Foreign keys with cascade/set null as appropriate
- Use proper indexes for performance
- Version history via `mpociot/versionable` where needed

### Testing Guidelines
- Feature tests for critical flows (subscription, checkout, payment)
- Unit tests for services with complex logic
- Use factories for test data
- Mock external services (payment providers, email)
- Run tests before committing: `vendor/bin/phpunit`

## Environment Setup

### First-Time Setup
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run dev
```

### Development Tools
- **Horizon Dashboard**: `/horizon` (queue monitoring)
- **Telescope Dashboard**: `/telescope` (debugging, dev only)
- **Admin Panel**: `/admin`
- **User Dashboard**: `/dashboard`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `configuring-horizon` — Use this skill whenever the user mentions Horizon by name in a Laravel context. Covers the full Horizon lifecycle: installing Horizon (horizon:install, Sail setup), configuring config/horizon.php (supervisor blocks, queue assignments, balancing strategies, minProcesses/maxProcesses), fixing the dashboard (authorization via Gate::define viewHorizon, blank metrics, horizon:snapshot scheduling), and troubleshooting production issues (worker crashes, timeout chain ordering, LongWaitDetected notifications, waits config). Also covers job tagging and silencing. Do not use for generic Laravel queues without Horizon, SQS or database drivers, standalone Redis setup, Linux supervisord, Telescope, or job batching.
- `socialite-development` — Manages OAuth social authentication with Laravel Socialite. Activate when adding social login providers; configuring OAuth redirect/callback flows; retrieving authenticated user details; customizing scopes or parameters; setting up community providers; testing with Socialite fakes; or when the user mentions social login, OAuth, Socialite, or third-party authentication.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`, `php artisan tinker --execute "..."`).
- Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- To execute PHP code for debugging, run `php artisan tinker --execute "your code here"` directly.
- To read configuration values, read the config files directly or run `php artisan config:show [key]`.
- To inspect routes, run `php artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== filament/filament rules ===

## Filament

- Filament is used by this application. Follow the existing conventions for how and where it is implemented.
- Filament is a Server-Driven UI (SDUI) framework for Laravel that lets you define user interfaces in PHP using structured configuration objects. Built on Livewire, Alpine.js, and Tailwind CSS.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Always inspect required options before running a command, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Actions encapsulate a button with an optional modal form and logic:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data))

</code-snippet>

### Testing

Always authenticate before testing panel functionality. Filament uses Livewire, so use `Livewire::test()` or `livewire()` (available when `pestphp/pest-plugin-livewire` is in `composer.json`):

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

<code-snippet name="Calling actions in pages" lang="php">
use Filament\Actions\DeleteAction;
use function Pest\Livewire\livewire;

livewire(EditUser::class, ['record' => $user->id])
    ->callAction(DeleteAction::class)
    ->assertNotified()
    ->assertRedirect();

</code-snippet>

<code-snippet name="Calling actions in tables" lang="php">
use Filament\Actions\Testing\TestAction;
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, and `Fieldset` do not span all columns by default. Explicitly set column spans when needed.

</laravel-boost-guidelines>
