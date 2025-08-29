Vou listar de coisas que estamos utilizando nessa documentação para podermos colocar no documento principal.

1- Lazy Loading em fotos que ficam depois da dobra.
2- Lazy Hydratyon
3- Cache busting.

# Vamos primeiramente aprender sobre Reatividade em Vue.js.

Na grande maioria das vezes criamos propriedades

interface propst{

}

para poderdemos colocar os atributos reativos em determinados caso, como por exemplo a minha logo
que poderia ser alterada diretamente na logo e fds.

# Agora vamos aprender sobre performaces nas paginas.

## Metodos de Carregamento com Lazy Loading e associados.

### Lazy Loading em fotos

Temos o Lazy Loading que é aplicado nas imagens. Ele meio que ao inves de carregar tudo de uma vez logo na abertura do site
ele so carrega quando for realmnete nescessario, assim deixando o site mais rapido no primeiro acesso, porque o navegador baixa
apenas o essencial.

Exemplo se eu tiver uma pagina com 100 imagens, mas so 5 aparecem na tela, proque baixar todas logo no inicio?
Com Lazy loading, o navegador so baixa as visiveis e vai carregando as outras quando o usuario rolar a tela.

loading="lazy"

E Temos o seu inverso que é o Eager Login, que é pra carregar por imediato porém é quase nunca usado pois ja é o padrão do proprio app.


### Lazy Hydartyion

Utilização do SSR(Server-Side Rendering), oque isso faz?
Ele gera o HTML pronto e nevia pro navegador, e isso faz a página aparecer rapidamente, depois o vue no navegador precisa apenas reativar esse HTML estatico conectando eventos.
É uma forma de carregar components pesados rapidamente, mas a logica Vue so entra em ação quando realmente nescessario.

hydrateOnVisible() → hidrata quando o usuário rola até o componente.
hydrateOnInteraction() → hidrata só quando o usuário interage (ex: clica).
hydrateOnIdle() → espera até que o navegador esteja “em repouso”.
hydrateNever() → nunca hidrata (só HTML estático, sem interatividade)

Lazy Loading (componentes/rotas): decide quando baixar o código JS.

Lazy Hydration: o código já está lá (porque foi renderizado no servidor), mas você decide quando ativar a interatividade.

#### Quando o Lazy Hydration ajuda
Ele é útil em componentes que:

São pesados (tipo gráficos, mapas, carrosséis complexos).

Ficam fora da primeira tela (só aparecem se o usuário rolar).

Ou têm interatividade opcional (botão que abre modal, chat flutuante).

## Sistema de Cache com Vue.js

No vue existem basicamente 2 niveis onde pensamos no cache.

1- Cache de componentes (UI)
Vue tem o componente especial <KeepAlive>
Ele serve para guardar o estado de um component mesmo depois de sair dele.
Exemplo:
<KeepAlive>
  <router-view />
</KeepAlive>

Isso faz com que quando você trocar de rota no SPA, os componentes antigos não sejam destruidos apenas "pausados"

Bom para formularios longos, abas de navegação, telas que o usuario pode voltar logo em seguida.
Evita perder estado (como inputs preenchidos)

2- Cache de dados(APIs / Stores)

O Vue em sim não faz cache automatico de dados. Mas você pode implementar com:

Pinia ou Vuex (store): guardar dados buscados da API na store global.

Bibliotecas como Vue Query (tanstack-query): oferecem cache de requisições HTTP.

3- Cache de Assets(Navegador)
Isso não é “Vue específico”, mas super importante em SPAs.
Você configura via headers do servidor (HTTP Cache-Control, ETag).

Imagens, CSS, JS podem ficar guardados no navegador.

Vue gera arquivos com hash no nome (app.abc123.js) → se mudar o código, o hash muda e o cache é atualizado.

4- Cache Busting(A que estamos usando)

Além do padrão de cache que temos no navegadores vamos utilizar uma tecnica que serve para enganhar o cache do navegador, mudando o nome do arquivo da sua URL quando você atualiza ele. Assim o navegador entende que é "um novo recurso" e baixa de novo. Vamos utilizar sempre que tivermos uma versão nova no Vite que é builder que estamos utilizando.
Ou seja sempre que fizermos o build do aplicativo ele vai precisar recarregar novamente.

<img :src="`/storage/photos/Teste.png?v=${import.meta.env.VITE_APP_VERSION}`" />

