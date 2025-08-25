# Sistema de Autenticação Laravel - Arquivo Didático

Este documento detalha todas as modificações realizadas para remover o sistema de autenticação padrão do Laravel, servindo como guia para entender como implementar um sistema de autenticação do zero.

## 📁 Arquivos Completamente Removidos

### 1. Rotas de Autenticação
**Arquivo:** `routes/auth.php`
- **Funcionalidade:** Contém todas as rotas relacionadas à autenticação
- **Rotas incluídas:**
  - Login/Logout
  - Registro de usuários
  - Redefinição de senha
  - Verificação de email
  - Confirmação de senha
- **Por que foi removido:** Elimina completamente o sistema de rotas de autenticação

### 2. Controllers de Autenticação
**Diretório:** `app/Http/Controllers/Auth/`
- **Arquivos removidos:**
  - `AuthenticatedSessionController.php` - Gerencia sessões de login/logout
  - `ConfirmablePasswordController.php` - Confirmação de senha
  - `EmailVerificationNotificationController.php` - Notificações de verificação
  - `EmailVerificationPromptController.php` - Prompt de verificação
  - `NewPasswordController.php` - Nova senha
  - `PasswordResetLinkController.php` - Link de redefinição
  - `RegisteredUserController.php` - Registro de usuários
  - `VerifyEmailController.php` - Verificação de email
- **Funcionalidades:** Todas as operações de autenticação

### 3. Controllers de Configurações
**Diretório:** `app/Http/Controllers/Settings/`
- **Arquivos removidos:**
  - `ProfileController.php` - Gerenciamento de perfil do usuário
  - `PasswordController.php` - Alteração de senha
- **Funcionalidades:** Configurações que dependiam de usuário autenticado

### 4. Requests de Validação
**Diretório:** `app/Http/Requests/Auth/`
- **Arquivo removido:** `LoginRequest.php`
- **Funcionalidade:** Validação de dados de login

**Diretório:** `app/Http/Requests/Settings/`
- **Arquivo removido:** `ProfileUpdateRequest.php`
- **Funcionalidade:** Validação de atualização de perfil

### 5. Testes
**Diretório:** `tests/Feature/Auth/`
- **Arquivos removidos:**
  - `AuthenticationTest.php` - Testes de login/logout
  - `EmailVerificationTest.php` - Testes de verificação de email
  - `PasswordConfirmationTest.php` - Testes de confirmação de senha
  - `PasswordResetTest.php` - Testes de redefinição de senha
  - `RegistrationTest.php` - Testes de registro

**Diretório:** `tests/Feature/Settings/`
- **Arquivos removidos:**
  - `ProfileUpdateTest.php` - Testes de atualização de perfil
  - `PasswordUpdateTest.php` - Testes de alteração de senha

## 🔧 Arquivos Modificados

### 1. Rotas Principais
**Arquivo:** `routes/web.php`
```php
// ANTES (com autenticação)
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';

// DEPOIS (sem autenticação)
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// require __DIR__.'/auth.php'; // Removido
```

### 2. Rotas de Configurações
**Arquivo:** `routes/settings.php`
```php
// ANTES (com autenticação)
Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});

// DEPOIS (sem autenticação)
Route::get('settings/appearance', function () {
    return Inertia::render('settings/Appearance');
})->name('appearance');
```

### 3. Middleware de Inertia
**Arquivo:** `app/Http/Middleware/HandleInertiaRequests.php`
```php
// ANTES (com autenticação)
public function share(Request $request): array
{
    [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'quote' => ['message' => trim($message), 'author' => trim($author)],
        'auth' => [
            'user' => $request->user(), // ← Removido
        ],
        'ziggy' => [
            ...(new Ziggy)->toArray(),
            'location' => $request->url(),
        ],
        'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
    ];
}

// DEPOIS (sem autenticação)
public function share(Request $request): array
{
    [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'quote' => ['message' => trim($message), 'author' => trim($author)],
        // 'auth' => [...] // ← Removido completamente
        'ziggy' => [
            ...(new Ziggy)->toArray(),
            'location' => $request->url(),
        ],
        'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
    ];
}
```

### 4. Modelo User
**Arquivo:** `app/Models/User.php`
```php
// ANTES (com autenticação)
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password', // ← Removido
    ];

    protected $hidden = [
        'password', // ← Removido
        'remember_token', // ← Removido
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // ← Removido
        ];
    }
}

// DEPOIS (sem autenticação)
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
    ];

    // $hidden removido completamente

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
}
```

### 5. Factory do User
**Arquivo:** `database/factories/UserFactory.php`
```php
// ANTES (com autenticação)
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'), // ← Removido
            'remember_token' => Str::random(10), // ← Removido
        ];
    }
}

// DEPOIS (sem autenticação)
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
        ];
    }
}
```

### 6. Migração de Usuários
**Arquivo:** `database/migrations/0001_01_01_000000_create_users_table.php`
```php
// ANTES (com autenticação)
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password'); // ← Removido
        $table->rememberToken(); // ← Removido
        $table->timestamps();
    });

    Schema::create('password_reset_tokens', function (Blueprint $table) { // ← Removido
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });

    Schema::create('sessions', function (Blueprint $table) { // ← Removido
        $table->string('id')->primary();
        $table->foreignId('user_id')->nullable()->index();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->longText('payload');
        $table->integer('last_activity')->index();
    });
}

// DEPOIS (sem autenticação)
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->timestamps();
    });
}
```

### 7. Teste do Dashboard
**Arquivo:** `tests/Feature/DashboardTest.php`
```php
// ANTES (com autenticação)
use App\Models\User;

class DashboardTest extends TestCase
{
    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }
}

// DEPOIS (sem autenticação)
class DashboardTest extends TestCase
{
    public function test_dashboard_page_is_accessible()
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }
}
```

### 8. Configurações do Sistema
**Arquivo:** `config/session.php`
```php
// ANTES (com autenticação)
'driver' => env('SESSION_DRIVER', 'database'),

// DEPOIS (sem autenticação)
'driver' => env('SESSION_DRIVER', 'file'),
```

**Arquivo:** `config/cache.php`
```php
// ANTES (com autenticação)
'default' => env('CACHE_STORE', 'database'),

// DEPOIS (sem autenticação)
'default' => env('CACHE_STORE', 'file'),
```

**Arquivo:** `config/queue.php`
```php
// ANTES (com autenticação)
'default' => env('QUEUE_CONNECTION', 'database'),

// DEPOIS (sem autenticação)
'default' => env('QUEUE_CONNECTION', 'sync'),
```

## 🎯 Funcionalidades Removidas

### Sistema de Login/Logout
- Autenticação de usuários
- Sessões de usuário
- Middleware de autenticação
- Redirecionamentos para login

### Registro de Usuários
- Criação de contas
- Validação de dados de registro
- Verificação de email

### Gerenciamento de Senhas
- Redefinição de senha
- Confirmação de senha
- Alteração de senha
- Tokens de redefinição

### Perfil de Usuário
- Edição de informações pessoais
- Exclusão de conta
- Verificação de email

### Sessões e Tokens
- Lembrança de login
- Tokens de sessão
- Invalidação de sessão

## 🚀 Como Implementar do Zero

### 1. Estrutura Básica
```bash
# Criar diretórios necessários
mkdir -p app/Http/Controllers/Auth
mkdir -p app/Http/Requests/Auth
mkdir -p app/Http/Middleware
mkdir -p resources/views/auth
mkdir -p routes
```

### 2. Modelo de Usuário
```php
// app/Models/User.php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### 3. Middleware de Autenticação
```php
// app/Http/Middleware/Authenticate.php
class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
```

### 4. Controllers de Autenticação
```php
// app/Http/Controllers/Auth/LoginController.php
class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Lógica de autenticação
    }

    public function logout(Request $request)
    {
        // Lógica de logout
    }
}
```

### 5. Rotas de Autenticação
```php
// routes/auth.php
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
```

### 6. Migrações
```php
// database/migrations/create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});

Schema::create('password_reset_tokens', function (Blueprint $table) {
    $table->string('email')->primary();
    $table->string('token');
    $table->timestamp('created_at')->nullable();
});
```

## 📚 Conceitos Importantes para Entender

### 1. Guards
- **Web Guard:** Autenticação baseada em sessão
- **API Guard:** Autenticação baseada em tokens
- **Sanctum:** Autenticação para SPAs e APIs

### 2. Providers
- **Eloquent Provider:** Usa modelos Eloquent para usuários
- **Database Provider:** Usa tabelas de banco de dados

### 3. Middleware
- **auth:** Verifica se o usuário está autenticado
- **guest:** Verifica se o usuário NÃO está autenticado
- **verified:** Verifica se o email foi verificado

### 4. Sessões
- **Driver:** file, database, redis, memcached
- **Lifetime:** Tempo de vida da sessão
- **Encryption:** Criptografia dos dados da sessão

### 5. Hashing de Senhas
- **Bcrypt:** Algoritmo padrão do Laravel
- **Argon2:** Algoritmo mais seguro (Laravel 10+)
- **Cost:** Fator de trabalho para hash

## 🔒 Segurança

### Boas Práticas
- Sempre usar HTTPS em produção
- Implementar rate limiting
- Validar e sanitizar inputs
- Usar CSRF tokens
- Implementar logout em todos os dispositivos
- Logs de auditoria para ações sensíveis

### Validações
- Email único e válido
- Senha forte (mínimo 8 caracteres, maiúsculas, números)
- Confirmação de senha
- Termos de uso e política de privacidade

## 📝 Próximos Passos

1. **Implementar sistema básico de login/logout**
2. **Adicionar registro de usuários**
3. **Implementar verificação de email**
4. **Adicionar redefinição de senha**
5. **Implementar middleware de autenticação**
6. **Criar views de autenticação**
7. **Adicionar validações e requests**
8. **Implementar testes**
9. **Configurar notificações por email**
10. **Adicionar autenticação social (OAuth)**

---

**Nota:** Este documento serve como referência para entender como o Laravel implementa autenticação e como você pode criar seu próprio sistema do zero. Cada funcionalidade pode ser implementada gradualmente, testando cada etapa antes de prosseguir. 