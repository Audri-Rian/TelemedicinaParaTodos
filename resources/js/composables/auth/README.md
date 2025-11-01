# Composables de Autenticação

Composables Vue para gerenciar autenticação, controle de acesso e roteamento baseado em roles.

## 📁 Arquivos

- **`index.ts`** - Exportações centralizadas
- **`useAuth.ts`** - Gerenciamento de autenticação e verificação de roles
- **`useRoleRoutes.ts`** - Rotas dinâmicas baseadas no tipo de usuário
- **`useRouteGuard.ts`** - Proteção e redirecionamento automático de rotas

---

## 🚀 Início Rápido

### Importação

```typescript
// Importar tudo
import { useAuth, useRoleRoutes, useRouteGuard } from '@/composables/auth';

// Ou individual
import { useAuth } from '@/composables/auth/useAuth';
```

### Uso Básico

```vue
<script setup lang="ts">
import { useAuth } from '@/composables/auth';

const { user, isDoctor, isPatient, canAccess } = useAuth();
</script>

<template>
    <div>
        <h1>Olá, {{ user?.name }}</h1>
        
        <!-- Conteúdo específico -->
        <div v-if="canAccess('doctor')">Área do médico</div>
        <div v-if="canAccess('patient')">Área do paciente</div>
    </div>
</template>
```

---

## 📖 Composables

### useAuth

Gerencia dados de autenticação e verificação de roles.

**O que retorna:**
- `user` - Dados do usuário
- `role` - Role do usuário ('doctor', 'patient', 'user')
- `isDoctor`, `isPatient` - Verificações booleanas
- `canAccess(role)` - Método escalável de verificação
- `canAccessAny(roles[])` - Verificar múltiplos roles

**Quando usar:**
- Verificar se usuário está autenticado
- Mostrar/ocultar elementos baseado em role
- Acessar dados do perfil do usuário

---

### useRoleRoutes

Gerencia rotas de forma dinâmica baseado no role.

**O que retorna:**
- `routes` - Namespace completo de rotas do role atual
- `dashboardRoute()` - Rota do dashboard
- Outras rotas específicas

**Quando usar:**
- Criar links dinâmicos
- Navegar para dashboard correto
- Acessar rotas específicas do role

**Exemplo:**
```vue
<script setup>
import { useRoleRoutes } from '@/composables/auth';

const { routes } = useRoleRoutes();
</script>

<template>
    <Link :href="routes.dashboard()">Dashboard</Link>
    <Link :href="routes.searchConsultations()">Search Consultations</Link>
</template>
```

---

### useRouteGuard

Protege rotas e redireciona usuários não autorizados.

**O que retorna:**
- `canAccessDoctorRoute()` - Verifica e redireciona
- `canAccessPatientRoute()` - Verifica e redireciona
- `hasPermission(role)` - Verifica sem redirecionar

**Quando usar:**
- Proteger páginas sensíveis
- Redirecionar automaticamente
- Validar acesso sem bloquear

**Exemplo:**
```vue
<script setup>
import { onMounted } from 'vue';
import { useRouteGuard } from '@/composables/auth';

const { canAccessDoctorRoute } = useRouteGuard();

onMounted(() => {
    canAccessDoctorRoute(); // Protege a página
});
</script>
```

---

## 🎯 Padrões de Uso

### 1. Proteger Página

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouteGuard } from '@/composables/auth';

const { canAccessDoctorRoute } = useRouteGuard();

onMounted(() => {
    canAccessDoctorRoute();
});
</script>
```

### 2. Navegação Dinâmica

```vue
<script setup lang="ts">
import { useAuth } from '@/composables/auth';
import { useRoleRoutes } from '@/composables/auth';

const { canAccess } = useAuth();
const { routes } = useRoleRoutes();

const menuItems = computed(() => {
    if (canAccess('doctor')) {
        return [
            { label: 'Dashboard', href: routes.dashboard() },
            { label: 'Agenda', href: routes.appointments() },
        ];
    }
    return [];
});
</script>
```

### 3. Conteúdo Condicional

```vue
<script setup>
import { useAuth } from '@/composables/auth';

const { canAccess, canAccessAny } = useAuth();
</script>

<template>
    <!-- Um role específico -->
    <button v-if="canAccess('doctor')">Gerenciar</button>
    
    <!-- Múltiplos roles -->
    <button v-if="canAccessAny(['doctor', 'admin'])">Admin</button>
</template>
```

---

## 🔧 Escalabilidade

### Adicionar Novo Role

Para adicionar um novo tipo de usuário (ex: `admin`):

**1. Backend (primeiro):**
- Criar model, migration, middleware
- Ver: [RoleBasedAccess.md](../../../docs/modules/auth/RoleBasedAccess.md)

**2. Frontend:**

```typescript
// useRoleRoutes.ts
import * as adminRoutes from '@/routes/admin';

const routesByRole = {
    doctor: doctorRoutes,
    patient: patientRoutes,
    admin: adminRoutes, // ← Adicionar apenas isto!
    user: {},
};
```

**Pronto!** Todos os composables funcionam automaticamente.

---

## 📚 Documentação Completa

Para documentação detalhada, consulte:

**[Sistema de Roteamento Frontend](../../../docs/modules/auth/FrontendRouting.md)**

Este documento cobre:
- Exemplos completos de uso
- Fluxos de redirecionamento
- Integração backend ↔ frontend
- Troubleshooting
- Boas práticas

---

## 🔗 Links Úteis

- [RoleBasedAccess.md](../../../docs/modules/auth/RoleBasedAccess.md) - Backend
- [FrontendRouting.md](../../../docs/modules/auth/FrontendRouting.md) - Frontend
- [RegistrationLogic.md](../../../docs/modules/auth/RegistrationLogic.md) - Registro

