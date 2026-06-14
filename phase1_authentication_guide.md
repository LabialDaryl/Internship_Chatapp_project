# Phase 1: Authentication — Step-by-Step Build Guide

> This guide covers **Login**, **Registration**, and **Profile Dashboard** for the Chatapp.
> Follow each step in order. The backend (Laravel) is built first, then the frontend (Vue 3).

---

## Prerequisites

Before starting, ensure the following are installed:

| Tool | Version | Check Command |
|:-----|:--------|:-------------|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18+ | `node -v` |
| npm | 9+ | `npm -v` |
| PostgreSQL | 15+ | `psql --version` |

---

## Step 1: Create the Project Skeleton

### 1.1 — Initialize Laravel Backend

```bash
cd c:\Users\daryl\Chatapp
composer create-project laravel/laravel backend
```

This creates `Chatapp/backend/` with the full Laravel 11 scaffold.

### 1.2 — Initialize Vue 3 Frontend

```bash
cd c:\Users\daryl\Chatapp
npm create vite@latest frontend -- --template vue
cd frontend
npm install
```

This creates `Chatapp/frontend/` with a minimal Vite + Vue 3 project.

### 1.3 — Expected Folder Structure After This Step

```text
Chatapp/
├── backend/          ← Laravel 11
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── .env
│   └── composer.json
├── frontend/         ← Vue 3 + Vite
│   ├── src/
│   ├── index.html
│   ├── vite.config.js
│   └── package.json
├── implementation_plan.md
└── phase1_authentication_guide.md   ← this file
```

---

## Step 2: Configure the Backend Environment

### 2.1 — Create PostgreSQL Database

```sql
-- Run in psql or pgAdmin:
CREATE DATABASE chatapp;
CREATE USER chatapp_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE chatapp TO chatapp_user;
```

### 2.2 — Update `backend/.env`

```env
APP_NAME=Chatapp
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=chatapp
DB_USERNAME=chatapp_user
DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Sanctum — allow Vue dev server as a trusted origin
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost

# CORS — the Vue frontend URL
FRONTEND_URL=http://localhost:5173
```

### 2.3 — Install Laravel Sanctum

```bash
cd c:\Users\daryl\Chatapp\backend
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

---

## Step 3: Backend — User Migration Update

### 3.1 — Modify `database/migrations/xxxx_create_users_table.php`

Add `avatar_url`, `last_seen_at`, and `is_online` columns to the default users table.

**File:** `backend/database/migrations/0001_01_01_000000_create_users_table.php`

Find the `Schema::create('users', ...)` block and update the columns:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->string('avatar_url')->nullable();          // NEW
    $table->timestamp('last_seen_at')->nullable();      // NEW
    $table->boolean('is_online')->default(false);       // NEW
    $table->rememberToken();
    $table->timestamps();
});
```

### 3.2 — Run Migrations

```bash
cd c:\Users\daryl\Chatapp\backend
php artisan migrate
```

---

## Step 4: Backend — User Model

### 4.1 — Update `app/Models/User.php`

**File:** `backend/app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'last_seen_at',
        'is_online',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_online' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Update the user's online presence status.
     */
    public function updatePresence(bool $online): void
    {
        $this->update([
            'is_online' => $online,
            'last_seen_at' => now(),
        ]);
    }
}
```

---

## Step 5: Backend — Form Requests (Validation)

### 5.1 — Create `RegisterRequest`

**File:** `backend/app/Http/Requests/RegisterRequest.php`

```bash
php artisan make:request RegisterRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route — anyone can register
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

### 5.2 — Create `LoginRequest`

**File:** `backend/app/Http/Requests/LoginRequest.php`

```bash
php artisan make:request LoginRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

### 5.3 — Create `UpdateProfileRequest`

**File:** `backend/app/Http/Requests/UpdateProfileRequest.php`

```bash
php artisan make:request UpdateProfileRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'email'      => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
        ];
    }
}
```

---

## Step 6: Backend — API Resource

### 6.1 — Create `UserResource`

**File:** `backend/app/Http/Resources/UserResource.php`

```bash
php artisan make:resource UserResource
```

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'avatar_url'  => $this->avatar_url,
            'is_online'   => $this->is_online,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
```

---

## Step 7: Backend — Auth Controller

### 7.1 — Create `AuthController`

**File:** `backend/app/Http/Controllers/Api/AuthController.php`

```bash
php artisan make:controller Api/AuthController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and return an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Authenticate and return an API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Mark user as online
        $user->updatePresence(true);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'user'    => new UserResource($user->fresh()),
            'message' => 'Profile updated successfully.',
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Revoke the current token and mark user as offline.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->updatePresence(false);
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
```

---

## Step 8: Backend — API Routes

### 8.1 — Update `routes/api.php`

**File:** `backend/routes/api.php`

```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (no authentication required)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (require Sanctum token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

---

## Step 9: Backend — CORS Configuration

### 9.1 — Update `config/cors.php`

**File:** `backend/config/cors.php`

Ensure the Vue dev server (localhost:5173) is allowed to make cross-origin requests:

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

---

## Step 10: Backend — Verify API Works

### 10.1 — Start Laravel Server

```bash
cd c:\Users\daryl\Chatapp\backend
php artisan serve
```

### 10.2 — Test Endpoints (use Postman, Insomnia, or curl)

**Register:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

**Get Profile (use token from login response):**
```bash
curl http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

> ✅ **Checkpoint:** All three endpoints should return valid JSON responses before proceeding to the frontend.

---

## Step 11: Frontend — Install Dependencies

### 11.1 — Install Required Packages

```bash
cd c:\Users\daryl\Chatapp\frontend
npm install vue-router@4 pinia axios
npm install -D @vitejs/plugin-vue
```

### 11.2 — Expected `package.json` Dependencies

```json
{
  "dependencies": {
    "axios": "^1.x",
    "pinia": "^2.x",
    "vue": "^3.x",
    "vue-router": "^4.x"
  }
}
```

---

## Step 12: Frontend — Environment & Vite Config

### 12.1 — Create `frontend/.env`

```env
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=Chatapp
```

### 12.2 — Update `frontend/vite.config.js`

```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    // Proxy API calls during development (optional alternative to CORS)
    // proxy: {
    //   '/api': {
    //     target: 'http://localhost:8000',
    //     changeOrigin: true,
    //   },
    // },
  },
})
```

---

## Step 13: Frontend — API Client Layer

### 13.1 — Create `src/api/client.js`

**File:** `frontend/src/api/client.js`

```javascript
import axios from 'axios'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Attach Bearer token to every request if available
client.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Handle 401 responses globally (token expired/invalid)
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default client
```

### 13.2 — Create `src/api/auth.js`

**File:** `frontend/src/api/auth.js`

```javascript
import client from './client'

export const authApi = {
  register(data) {
    return client.post('/register', data)
  },

  login(data) {
    return client.post('/login', data)
  },

  logout() {
    return client.post('/logout')
  },

  getUser() {
    return client.get('/user')
  },

  updateProfile(data) {
    return client.put('/user/profile', data)
  },

  updatePassword(data) {
    return client.put('/user/password', data)
  },
}
```

---

## Step 14: Frontend — Pinia Auth Store

### 14.1 — Create `src/stores/auth.js`

**File:** `frontend/src/stores/auth.js`

```javascript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const loading = ref(false)
  const errors = ref({})

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const userName = computed(() => user.value?.name || '')
  const userEmail = computed(() => user.value?.email || '')
  const userAvatar = computed(() => user.value?.avatar_url || null)

  // Actions
  async function register(formData) {
    loading.value = true
    errors.value = {}
    try {
      const { data } = await authApi.register(formData)
      token.value = data.token
      user.value = data.user
      localStorage.setItem('auth_token', data.token)
      return data
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {}
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function login(formData) {
    loading.value = true
    errors.value = {}
    try {
      const { data } = await authApi.login(formData)
      token.value = data.token
      user.value = data.user
      localStorage.setItem('auth_token', data.token)
      return data
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {}
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const { data } = await authApi.getUser()
      user.value = data.user
    } catch {
      logout()
    }
  }

  async function updateProfile(formData) {
    loading.value = true
    errors.value = {}
    try {
      const { data } = await authApi.updateProfile(formData)
      user.value = data.user
      return data
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {}
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updatePassword(formData) {
    loading.value = true
    errors.value = {}
    try {
      const { data } = await authApi.updatePassword(formData)
      return data
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {}
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await authApi.logout()
      }
    } catch {
      // Ignore errors on logout
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  function clearErrors() {
    errors.value = {}
  }

  return {
    // State
    user, token, loading, errors,
    // Getters
    isAuthenticated, userName, userEmail, userAvatar,
    // Actions
    register, login, fetchUser, updateProfile, updatePassword, logout, clearErrors,
  }
})
```

---

## Step 15: Frontend — Vue Router with Auth Guards

### 15.1 — Create `src/router/index.js`

**File:** `frontend/src/router/index.js`

```javascript
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/pages/RegisterPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/pages/DashboardPage.vue'),
    meta: { auth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/pages/ProfilePage.vue'),
    meta: { auth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: '/login',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  // If user has token but no user data loaded, fetch it
  if (auth.token && !auth.user) {
    await auth.fetchUser()
  }

  // Route requires authentication
  if (to.meta.auth && !auth.isAuthenticated) {
    return next({ name: 'login' })
  }

  // Route is for guests only (don't show login to logged-in users)
  if (to.meta.guest && auth.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  next()
})

export default router
```

---

## Step 16: Frontend — Global Styles

### 16.1 — Create `src/assets/styles/main.css`

**File:** `frontend/src/assets/styles/main.css`

This defines the full design system — colors, typography, spacing, and component styles.

```css
/* ============================================
   CHATAPP — Design System & Global Styles
   ============================================ */

/* ---------- Google Font ---------- */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* ---------- CSS Variables ---------- */
:root {
  /* Brand Colors */
  --color-primary: #6366f1;
  --color-primary-hover: #4f46e5;
  --color-primary-light: #818cf8;
  --color-primary-bg: rgba(99, 102, 241, 0.08);

  /* Neutral Palette */
  --color-bg-primary: #0f0f23;
  --color-bg-secondary: #1a1a3e;
  --color-bg-tertiary: #252552;
  --color-bg-card: rgba(30, 30, 60, 0.7);
  --color-bg-input: rgba(255, 255, 255, 0.05);
  --color-bg-input-focus: rgba(255, 255, 255, 0.08);

  /* Text */
  --color-text-primary: #f1f5f9;
  --color-text-secondary: #94a3b8;
  --color-text-muted: #64748b;
  --color-text-inverse: #0f0f23;

  /* Borders */
  --color-border: rgba(255, 255, 255, 0.08);
  --color-border-focus: rgba(99, 102, 241, 0.5);

  /* Status */
  --color-success: #22c55e;
  --color-error: #ef4444;
  --color-warning: #f59e0b;
  --color-info: #3b82f6;

  /* Spacing */
  --space-xs: 0.25rem;
  --space-sm: 0.5rem;
  --space-md: 1rem;
  --space-lg: 1.5rem;
  --space-xl: 2rem;
  --space-2xl: 3rem;
  --space-3xl: 4rem;

  /* Border Radius */
  --radius-sm: 0.375rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --radius-xl: 1rem;
  --radius-full: 9999px;

  /* Shadows */
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
  --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.4);
  --shadow-glow: 0 0 20px rgba(99, 102, 241, 0.15);

  /* Transitions */
  --transition-fast: 150ms ease;
  --transition-base: 250ms ease;
  --transition-slow: 400ms ease;

  /* Typography */
  --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --font-size-2xl: 1.5rem;
  --font-size-3xl: 2rem;
  --font-size-4xl: 2.5rem;
}

/* ---------- Reset & Base ---------- */
*,
*::before,
*::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: var(--font-family);
  font-size: var(--font-size-base);
  line-height: 1.6;
  color: var(--color-text-primary);
  background: var(--color-bg-primary);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

a {
  color: var(--color-primary-light);
  text-decoration: none;
  transition: color var(--transition-fast);
}

a:hover {
  color: var(--color-primary);
}

/* ---------- Auth Layout ---------- */
.auth-layout {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: var(--space-lg);
  background:
    radial-gradient(ellipse at 20% 50%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(139, 92, 246, 0.06) 0%, transparent 50%),
    var(--color-bg-primary);
}

.auth-card {
  width: 100%;
  max-width: 440px;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-2xl);
  backdrop-filter: blur(20px);
  box-shadow: var(--shadow-lg);
  animation: fadeInUp 0.5s ease forwards;
}

.auth-card__header {
  text-align: center;
  margin-bottom: var(--space-2xl);
}

.auth-card__logo {
  font-size: var(--font-size-3xl);
  font-weight: 700;
  background: linear-gradient(135deg, var(--color-primary-light), var(--color-primary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: var(--space-sm);
}

.auth-card__subtitle {
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
}

.auth-card__footer {
  text-align: center;
  margin-top: var(--space-lg);
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.auth-card__footer a {
  font-weight: 500;
}

/* ---------- Form Controls ---------- */
.form-group {
  margin-bottom: var(--space-lg);
}

.form-label {
  display: block;
  font-size: var(--font-size-sm);
  font-weight: 500;
  color: var(--color-text-secondary);
  margin-bottom: var(--space-xs);
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  font-family: var(--font-family);
  font-size: var(--font-size-base);
  color: var(--color-text-primary);
  background: var(--color-bg-input);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  outline: none;
  transition: all var(--transition-fast);
}

.form-input::placeholder {
  color: var(--color-text-muted);
}

.form-input:focus {
  background: var(--color-bg-input-focus);
  border-color: var(--color-border-focus);
  box-shadow: var(--shadow-glow);
}

.form-input.is-error {
  border-color: var(--color-error);
}

.form-error {
  display: block;
  font-size: var(--font-size-xs);
  color: var(--color-error);
  margin-top: var(--space-xs);
}

/* ---------- Buttons ---------- */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  padding: 0.75rem 1.5rem;
  font-family: var(--font-family);
  font-size: var(--font-size-base);
  font-weight: 600;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  text-decoration: none;
  line-height: 1;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn--primary {
  width: 100%;
  color: #ffffff;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}

.btn--primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(99, 102, 241, 0.4);
}

.btn--primary:active:not(:disabled) {
  transform: translateY(0);
}

.btn--secondary {
  color: var(--color-text-primary);
  background: var(--color-bg-input);
  border: 1px solid var(--color-border);
}

.btn--secondary:hover:not(:disabled) {
  background: var(--color-bg-input-focus);
  border-color: var(--color-border-focus);
}

.btn--danger {
  color: #ffffff;
  background: var(--color-error);
}

.btn--danger:hover:not(:disabled) {
  background: #dc2626;
}

.btn--sm {
  padding: 0.5rem 1rem;
  font-size: var(--font-size-sm);
}

/* ---------- App Layout (Dashboard/Profile) ---------- */
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.app-navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-md) var(--space-xl);
  background: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border);
  backdrop-filter: blur(12px);
  position: sticky;
  top: 0;
  z-index: 100;
}

.app-navbar__brand {
  font-size: var(--font-size-xl);
  font-weight: 700;
  background: linear-gradient(135deg, var(--color-primary-light), var(--color-primary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.app-navbar__nav {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.app-navbar__link {
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-md);
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
  font-weight: 500;
  transition: all var(--transition-fast);
}

.app-navbar__link:hover,
.app-navbar__link.active {
  color: var(--color-text-primary);
  background: var(--color-primary-bg);
}

.app-content {
  flex: 1;
  padding: var(--space-2xl);
  max-width: 900px;
  margin: 0 auto;
  width: 100%;
}

/* ---------- Cards ---------- */
.card {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-2xl);
  backdrop-filter: blur(20px);
  box-shadow: var(--shadow-md);
}

.card__title {
  font-size: var(--font-size-xl);
  font-weight: 600;
  margin-bottom: var(--space-xs);
}

.card__subtitle {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  margin-bottom: var(--space-xl);
}

/* ---------- Avatar ---------- */
.avatar {
  width: 80px;
  height: 80px;
  border-radius: var(--radius-full);
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--font-size-2xl);
  font-weight: 700;
  color: #ffffff;
  flex-shrink: 0;
  overflow: hidden;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar--sm {
  width: 40px;
  height: 40px;
  font-size: var(--font-size-base);
}

/* ---------- Alerts / Toast ---------- */
.alert {
  padding: var(--space-md);
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  margin-bottom: var(--space-lg);
  animation: fadeInUp 0.3s ease forwards;
}

.alert--success {
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: var(--color-success);
}

.alert--error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: var(--color-error);
}

/* ---------- Animations ---------- */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.spinner {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

/* ---------- Responsive ---------- */
@media (max-width: 640px) {
  .auth-card {
    padding: var(--space-xl);
  }

  .app-content {
    padding: var(--space-lg);
  }

  .app-navbar {
    padding: var(--space-md);
  }
}
```

---

## Step 17: Frontend — App Entry Point

### 17.1 — Update `src/main.js`

**File:** `frontend/src/main.js`

```javascript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router/index.js'
import App from './App.vue'
import './assets/styles/main.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
```

### 17.2 — Update `src/App.vue`

**File:** `frontend/src/App.vue`

```vue
<template>
  <router-view />
</template>

<script setup>
// App root — router-view renders the active page
</script>
```

### 17.3 — Update `index.html`

**File:** `frontend/index.html`

```html
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Chatapp — Real-time messaging application" />
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <title>Chatapp</title>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
```

---

## Step 18: Frontend — Layout Components

### 18.1 — Create `src/layouts/AuthLayout.vue`

**File:** `frontend/src/layouts/AuthLayout.vue`

```vue
<template>
  <div class="auth-layout">
    <slot />
  </div>
</template>

<script setup>
// Auth layout — centered card on gradient background
</script>
```

### 18.2 — Create `src/layouts/AppLayout.vue`

**File:** `frontend/src/layouts/AppLayout.vue`

```vue
<template>
  <div class="app-layout">
    <!-- Navbar -->
    <nav class="app-navbar">
      <div class="app-navbar__brand">Chatapp</div>
      <div class="app-navbar__nav">
        <router-link to="/dashboard" class="app-navbar__link" active-class="active">
          Dashboard
        </router-link>
        <router-link to="/profile" class="app-navbar__link" active-class="active">
          Profile
        </router-link>
        <button class="btn btn--secondary btn--sm" @click="handleLogout" :disabled="loading">
          Logout
        </button>
      </div>
    </nav>

    <!-- Page Content -->
    <main class="app-content">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(false)

async function handleLogout() {
  loading.value = true
  await auth.logout()
  router.push({ name: 'login' })
}
</script>
```

---

## Step 19: Frontend — Pages

### 19.1 — Create `src/pages/LoginPage.vue`

**File:** `frontend/src/pages/LoginPage.vue`

```vue
<template>
  <AuthLayout>
    <div class="auth-card">
      <div class="auth-card__header">
        <h1 class="auth-card__logo">Chatapp</h1>
        <p class="auth-card__subtitle">Sign in to your account</p>
      </div>

      <!-- Error alert -->
      <div v-if="auth.errors.email" class="alert alert--error">
        {{ auth.errors.email[0] }}
      </div>

      <form @submit.prevent="handleLogin" id="login-form">
        <div class="form-group">
          <label for="login-email" class="form-label">Email</label>
          <input
            id="login-email"
            v-model="form.email"
            type="email"
            class="form-input"
            :class="{ 'is-error': auth.errors.email }"
            placeholder="you@example.com"
            required
            autocomplete="email"
          />
        </div>

        <div class="form-group">
          <label for="login-password" class="form-label">Password</label>
          <input
            id="login-password"
            v-model="form.password"
            type="password"
            class="form-input"
            :class="{ 'is-error': auth.errors.password }"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          />
          <span v-if="auth.errors.password" class="form-error">
            {{ auth.errors.password[0] }}
          </span>
        </div>

        <button
          type="submit"
          class="btn btn--primary"
          :disabled="auth.loading"
          id="login-submit"
        >
          <span v-if="auth.loading" class="spinner"></span>
          <span v-else>Sign In</span>
        </button>
      </form>

      <p class="auth-card__footer">
        Don't have an account?
        <router-link to="/register">Create one</router-link>
      </p>
    </div>
  </AuthLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AuthLayout from '@/layouts/AuthLayout.vue'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})

async function handleLogin() {
  auth.clearErrors()
  try {
    await auth.login(form)
    router.push({ name: 'dashboard' })
  } catch {
    // Errors are handled by the store
  }
}
</script>
```

### 19.2 — Create `src/pages/RegisterPage.vue`

**File:** `frontend/src/pages/RegisterPage.vue`

```vue
<template>
  <AuthLayout>
    <div class="auth-card">
      <div class="auth-card__header">
        <h1 class="auth-card__logo">Chatapp</h1>
        <p class="auth-card__subtitle">Create your account</p>
      </div>

      <form @submit.prevent="handleRegister" id="register-form">
        <div class="form-group">
          <label for="register-name" class="form-label">Full Name</label>
          <input
            id="register-name"
            v-model="form.name"
            type="text"
            class="form-input"
            :class="{ 'is-error': auth.errors.name }"
            placeholder="John Doe"
            required
            autocomplete="name"
          />
          <span v-if="auth.errors.name" class="form-error">
            {{ auth.errors.name[0] }}
          </span>
        </div>

        <div class="form-group">
          <label for="register-email" class="form-label">Email</label>
          <input
            id="register-email"
            v-model="form.email"
            type="email"
            class="form-input"
            :class="{ 'is-error': auth.errors.email }"
            placeholder="you@example.com"
            required
            autocomplete="email"
          />
          <span v-if="auth.errors.email" class="form-error">
            {{ auth.errors.email[0] }}
          </span>
        </div>

        <div class="form-group">
          <label for="register-password" class="form-label">Password</label>
          <input
            id="register-password"
            v-model="form.password"
            type="password"
            class="form-input"
            :class="{ 'is-error': auth.errors.password }"
            placeholder="Minimum 8 characters"
            required
            autocomplete="new-password"
          />
          <span v-if="auth.errors.password" class="form-error">
            {{ auth.errors.password[0] }}
          </span>
        </div>

        <div class="form-group">
          <label for="register-password-confirm" class="form-label">Confirm Password</label>
          <input
            id="register-password-confirm"
            v-model="form.password_confirmation"
            type="password"
            class="form-input"
            placeholder="Repeat your password"
            required
            autocomplete="new-password"
          />
        </div>

        <button
          type="submit"
          class="btn btn--primary"
          :disabled="auth.loading"
          id="register-submit"
        >
          <span v-if="auth.loading" class="spinner"></span>
          <span v-else>Create Account</span>
        </button>
      </form>

      <p class="auth-card__footer">
        Already have an account?
        <router-link to="/login">Sign in</router-link>
      </p>
    </div>
  </AuthLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AuthLayout from '@/layouts/AuthLayout.vue'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

async function handleRegister() {
  auth.clearErrors()
  try {
    await auth.register(form)
    router.push({ name: 'dashboard' })
  } catch {
    // Errors are handled by the store
  }
}
</script>
```

### 19.3 — Create `src/pages/DashboardPage.vue`

**File:** `frontend/src/pages/DashboardPage.vue`

```vue
<template>
  <AppLayout>
    <div class="dashboard">
      <div class="dashboard__welcome card">
        <div class="dashboard__welcome-row">
          <div class="avatar">
            <img v-if="auth.userAvatar" :src="auth.userAvatar" :alt="auth.userName" />
            <span v-else>{{ initials }}</span>
          </div>
          <div class="dashboard__welcome-text">
            <h1 class="card__title">Welcome back, {{ auth.userName }}!</h1>
            <p class="card__subtitle" style="margin-bottom: 0">
              You're signed in as <strong>{{ auth.userEmail }}</strong>
            </p>
          </div>
        </div>
      </div>

      <div class="dashboard__grid">
        <div class="card dashboard__stat-card">
          <div class="dashboard__stat-icon">💬</div>
          <div class="dashboard__stat-label">Conversations</div>
          <div class="dashboard__stat-value">—</div>
          <p class="dashboard__stat-note">Chat feature coming soon</p>
        </div>

        <div class="card dashboard__stat-card">
          <div class="dashboard__stat-icon">👥</div>
          <div class="dashboard__stat-label">Contacts</div>
          <div class="dashboard__stat-value">—</div>
          <p class="dashboard__stat-note">Contact list coming soon</p>
        </div>

        <div class="card dashboard__stat-card">
          <div class="dashboard__stat-icon">📎</div>
          <div class="dashboard__stat-label">Shared Files</div>
          <div class="dashboard__stat-value">—</div>
          <p class="dashboard__stat-note">File sharing coming soon</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const auth = useAuthStore()

const initials = computed(() => {
  return auth.userName
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})
</script>

<style scoped>
.dashboard__welcome-row {
  display: flex;
  align-items: center;
  gap: var(--space-xl);
}

.dashboard__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: var(--space-lg);
  margin-top: var(--space-xl);
}

.dashboard__stat-card {
  text-align: center;
  padding: var(--space-xl);
}

.dashboard__stat-icon {
  font-size: 2.5rem;
  margin-bottom: var(--space-md);
}

.dashboard__stat-label {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: var(--space-xs);
}

.dashboard__stat-value {
  font-size: var(--font-size-3xl);
  font-weight: 700;
  color: var(--color-primary-light);
}

.dashboard__stat-note {
  font-size: var(--font-size-xs);
  color: var(--color-text-muted);
  margin-top: var(--space-sm);
}

@media (max-width: 640px) {
  .dashboard__welcome-row {
    flex-direction: column;
    text-align: center;
  }
}
</style>
```

### 19.4 — Create `src/pages/ProfilePage.vue`

**File:** `frontend/src/pages/ProfilePage.vue`

```vue
<template>
  <AppLayout>
    <div class="profile">
      <!-- Profile Info Card -->
      <div class="card">
        <h2 class="card__title">Profile Information</h2>
        <p class="card__subtitle">Update your name and email address.</p>

        <div v-if="profileSuccess" class="alert alert--success">
          {{ profileSuccess }}
        </div>

        <form @submit.prevent="handleUpdateProfile" id="profile-form">
          <div class="form-group">
            <label for="profile-name" class="form-label">Full Name</label>
            <input
              id="profile-name"
              v-model="profileForm.name"
              type="text"
              class="form-input"
              :class="{ 'is-error': auth.errors.name }"
              required
            />
            <span v-if="auth.errors.name" class="form-error">
              {{ auth.errors.name[0] }}
            </span>
          </div>

          <div class="form-group">
            <label for="profile-email" class="form-label">Email</label>
            <input
              id="profile-email"
              v-model="profileForm.email"
              type="email"
              class="form-input"
              :class="{ 'is-error': auth.errors.email }"
              required
            />
            <span v-if="auth.errors.email" class="form-error">
              {{ auth.errors.email[0] }}
            </span>
          </div>

          <button
            type="submit"
            class="btn btn--primary"
            :disabled="auth.loading"
            id="profile-submit"
          >
            <span v-if="auth.loading" class="spinner"></span>
            <span v-else>Save Changes</span>
          </button>
        </form>
      </div>

      <!-- Change Password Card -->
      <div class="card" style="margin-top: var(--space-xl)">
        <h2 class="card__title">Change Password</h2>
        <p class="card__subtitle">Ensure your account uses a strong password.</p>

        <div v-if="passwordSuccess" class="alert alert--success">
          {{ passwordSuccess }}
        </div>
        <div v-if="passwordError" class="alert alert--error">
          {{ passwordError }}
        </div>

        <form @submit.prevent="handleUpdatePassword" id="password-form">
          <div class="form-group">
            <label for="current-password" class="form-label">Current Password</label>
            <input
              id="current-password"
              v-model="passwordForm.current_password"
              type="password"
              class="form-input"
              required
            />
          </div>

          <div class="form-group">
            <label for="new-password" class="form-label">New Password</label>
            <input
              id="new-password"
              v-model="passwordForm.password"
              type="password"
              class="form-input"
              placeholder="Minimum 8 characters"
              required
            />
          </div>

          <div class="form-group">
            <label for="confirm-new-password" class="form-label">Confirm New Password</label>
            <input
              id="confirm-new-password"
              v-model="passwordForm.password_confirmation"
              type="password"
              class="form-input"
              required
            />
          </div>

          <button
            type="submit"
            class="btn btn--primary"
            :disabled="auth.loading"
            id="password-submit"
          >
            <span v-if="auth.loading" class="spinner"></span>
            <span v-else>Update Password</span>
          </button>
        </form>
      </div>

      <!-- Danger Zone -->
      <div class="card" style="margin-top: var(--space-xl); border-color: rgba(239, 68, 68, 0.2)">
        <h2 class="card__title" style="color: var(--color-error)">Danger Zone</h2>
        <p class="card__subtitle">Permanently delete your account (not yet implemented).</p>
        <button class="btn btn--danger btn--sm" disabled>Delete Account</button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const auth = useAuthStore()

// Profile form
const profileForm = reactive({
  name: '',
  email: '',
})

const profileSuccess = ref('')

// Password form
const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const passwordSuccess = ref('')
const passwordError = ref('')

// Populate form with current user data
onMounted(() => {
  if (auth.user) {
    profileForm.name = auth.user.name
    profileForm.email = auth.user.email
  }
})

async function handleUpdateProfile() {
  auth.clearErrors()
  profileSuccess.value = ''
  try {
    const data = await auth.updateProfile(profileForm)
    profileSuccess.value = data.message || 'Profile updated successfully.'
  } catch {
    // Errors handled by store
  }
}

async function handleUpdatePassword() {
  auth.clearErrors()
  passwordSuccess.value = ''
  passwordError.value = ''
  try {
    const data = await auth.updatePassword(passwordForm)
    passwordSuccess.value = data.message || 'Password updated successfully.'
    // Clear form
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors
      passwordError.value = Object.values(errors).flat()[0] || 'Validation failed.'
    }
  }
}
</script>
```

---

## Step 20: Frontend — Vercel Config

### 20.1 — Create `frontend/vercel.json`

**File:** `frontend/vercel.json`

```json
{
  "rewrites": [
    { "source": "/(.*)", "destination": "/" }
  ]
}
```

This ensures all routes are handled by Vue Router (SPA behavior).

---

## Step 21: Run & Verify

### 21.1 — Start Both Servers

**Terminal 1 — Backend:**
```bash
cd c:\Users\daryl\Chatapp\backend
php artisan serve
```
→ Runs at `http://localhost:8000`

**Terminal 2 — Frontend:**
```bash
cd c:\Users\daryl\Chatapp\frontend
npm run dev
```
→ Runs at `http://localhost:5173`

### 21.2 — Test Checklist

| # | Test | Expected Result |
|:--|:-----|:----------------|
| 1 | Visit `http://localhost:5173/login` | Login form renders with dark theme |
| 2 | Click "Create one" link | Navigates to `/register` |
| 3 | Fill in registration form + submit | Redirects to `/dashboard` with welcome message |
| 4 | Refresh page | Still logged in (token persists in localStorage) |
| 5 | Click "Profile" in navbar | Navigates to `/profile` with pre-filled form |
| 6 | Update name + save | Success alert appears, navbar reflects change |
| 7 | Change password | Success alert, form clears |
| 8 | Click "Logout" | Redirects to `/login`, token removed |
| 9 | Visit `/dashboard` while logged out | Redirects to `/login` (auth guard) |
| 10 | Visit `/login` while logged in | Redirects to `/dashboard` (guest guard) |

---

## File Checklist — All Files Created in This Phase

### Backend (`Chatapp/backend/`)

| File | Action | Status |
|:-----|:-------|:-------|
| `.env` | MODIFY | ☐ |
| `database/migrations/xxxx_create_users_table.php` | MODIFY | ☐ |
| `app/Models/User.php` | MODIFY | ☐ |
| `app/Http/Requests/RegisterRequest.php` | NEW | ☐ |
| `app/Http/Requests/LoginRequest.php` | NEW | ☐ |
| `app/Http/Requests/UpdateProfileRequest.php` | NEW | ☐ |
| `app/Http/Resources/UserResource.php` | NEW | ☐ |
| `app/Http/Controllers/Api/AuthController.php` | NEW | ☐ |
| `routes/api.php` | MODIFY | ☐ |
| `config/cors.php` | MODIFY | ☐ |

### Frontend (`Chatapp/frontend/`)

| File | Action | Status |
|:-----|:-------|:-------|
| `.env` | NEW | ☐ |
| `vite.config.js` | MODIFY | ☐ |
| `index.html` | MODIFY | ☐ |
| `src/main.js` | MODIFY | ☐ |
| `src/App.vue` | MODIFY | ☐ |
| `src/assets/styles/main.css` | NEW | ☐ |
| `src/api/client.js` | NEW | ☐ |
| `src/api/auth.js` | NEW | ☐ |
| `src/stores/auth.js` | NEW | ☐ |
| `src/router/index.js` | NEW | ☐ |
| `src/layouts/AuthLayout.vue` | NEW | ☐ |
| `src/layouts/AppLayout.vue` | NEW | ☐ |
| `src/pages/LoginPage.vue` | NEW | ☐ |
| `src/pages/RegisterPage.vue` | NEW | ☐ |
| `src/pages/DashboardPage.vue` | NEW | ☐ |
| `src/pages/ProfilePage.vue` | NEW | ☐ |
| `vercel.json` | NEW | ☐ |

---

## What's Next (Phase 2)

Once authentication is working:
1. **Database migrations** — conversations, participants, messages, attachments, read_receipts
2. **Chat models & relationships**
3. **Chat API endpoints**
4. **Laravel Reverb** — real-time WebSocket broadcasting
5. **Vue chat UI** — conversation list, message thread, file upload
