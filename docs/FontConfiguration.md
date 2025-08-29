# Configuração da Fonte Spline Sans

## Resumo das Alterações

Este documento descreve as alterações feitas para configurar a fonte **Bold Spline Sans** como fonte principal do sistema.

## Arquivos Modificados

### 1. `resources/views/app.blade.php`
- **Linha 8**: Atualizado o link do Google Fonts para incluir o peso Bold (700) da Spline Sans
- **Antes**: `family=Spline+Sans:wght@300..700`
- **Depois**: `family=Spline+Sans:wght@300..700;1,700`

### 2. `tailwind.config.js`
- **Linha 20**: Alterada a fonte padrão de "Open Sans" para "Spline Sans"
- **Adicionada**: Nova classe `font-spline-bold` para uso específico

```javascript
fontFamily: {
  sans: ["Spline Sans", "ui-sans-serif", "system-ui", "sans-serif"], // Fonte padrão
  "spline-bold": ["Spline Sans", "sans-serif"], // Classe específica
  // ... outras fontes
}
```

### 3. `resources/css/app.css`
- **Linha 1**: Atualizado o import do Google Fonts
- **Linha 11**: Alterada a variável CSS `--font-sans` para usar Spline Sans
- **Linha 82**: Atualizada a variável CSS no layer utilities

### 4. `routes/web.php`
- **Adicionada**: Nova rota `/font-test` para testar as fontes

### 5. Componentes de Teste
- **`resources/js/components/FontTest.vue`**: Componente para visualizar todas as fontes
- **`resources/js/pages/FontTest.vue`**: Página de teste

## Como Usar

### Fonte Padrão
A fonte Spline Sans agora é aplicada automaticamente a todos os elementos que usam a classe `font-sans` (padrão do Tailwind).

### Fonte Bold
Para usar especificamente a versão Bold da Spline Sans:

```html
<!-- Usando classes do Tailwind -->
<p class="font-bold">Texto em Spline Sans Bold</p>

<!-- Usando a classe específica -->
<p class="font-spline-bold">Texto em Spline Sans</p>
```

### Outras Fontes Disponíveis
- `font-display` → Knewave (para títulos)
- `font-lato` → Lato
- `font-montserrat` → Montserrat
- `font-outfit` → Outfit
- `font-raleway` → Raleway
- `font-spline` → Spline Sans (classe específica)

## Pesos da Fonte Spline Sans
- **300**: Light
- **400**: Normal
- **500**: Medium
- **600**: Semibold
- **700**: Bold

## Testando as Alterações

1. Acesse a rota `/font-test` para visualizar todas as fontes
2. Verifique se o texto padrão está usando Spline Sans
3. Teste diferentes pesos da fonte

## Rebuild Necessário

Após as alterações, é necessário:

1. **Recompilar o CSS**: `npm run build` ou `npm run dev`
2. **Limpar cache**: `php artisan cache:clear`
3. **Limpar cache do navegador** para carregar as novas fontes

## Compatibilidade

- ✅ Laravel 11
- ✅ Tailwind CSS v4
- ✅ Inertia.js
- ✅ Vue.js 3
- ✅ Vite

## Notas Importantes

- A fonte Spline Sans é carregada do Google Fonts
- O peso Bold (700) está disponível para uso
- Todas as outras fontes permanecem disponíveis
- A mudança é aplicada globalmente em todo o sistema
