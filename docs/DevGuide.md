Sempre que abrir o Cursor para uma task, dê contexto:

“Considere as regras em docs/SystemRules.md e o guia em docs/DevGuide.md para criar este código.”

O intuito do dev guide é manter a reponsabilidade de cada logica estrutural de comunicação dos meus arquivos, como por exemplo as comunicação entre Migrations, Models, DTOs, Services, Controllers Request e assim por diante

# Aqui temos de instrução de configuração de responsabilidade e manuseamento da arquitetura para manter o SOLID e bem estruturado o projeto.

# Arquitetura de comunicação padrão: 
[Migrations] → definem a estrutura do banco de dados
         ↘
[Eloquent Models] → schema, relacionamentos, casts, scopes, accessors
         ↘
[DTOs] ↔ (entrada/saída entre Controller e Service)
         ↘
[Services] → contém lógica de negócio, orquestra repositórios/modelos
         ↘
[Repositories] (opcional, evite usar) → abstração de acesso aos dados
         ↘
[Database / APIs externas]

- Controllers recebem as requisições, constroem DTOs e interagem com Services.

- DTOs encapsulam dados de forma clara e segura entre as camadas.

- Services agregam regras de negócio, coordenam fluxos complexos e usam repositórios ou modelos.

- Repositories lidam com persistência, queries e abstração de fontes de dados

- Padrões de código: Use PSR-12, nomeação em inglês consistente, e migrations com timestamps.

- Testes: Todo método crítico deve ter teste unitário.

## Responsabilidade da Model.

Sua model deve assumir apenas suas responsabilidades fundamentais. Idealmente, ela inclui:

Atributos $fillable e $casts

Constantes (status, enums)

Relacionamentos Eloquent (belongsTo, hasMany etc.)

Scopes reutilizáveis (scopeActive, scopeByGender etc.)

Accessors e Mutators para formatação e saneamento de dados

Lógicas complexas de negócio (como operações que envolvem vários modelos ou fluxos específicos) devem ser movidas para outras camadas. Manter essas responsabilidades fora do model melhora a organização, testabilidade e aderência aos padrões de design (como Single Responsibility)

## Responsabilidade do DTO

DTOs devem servir ao transporte de dados entre camadas, mas a conversão de Model para DTO — ou vice-versa — é mais apropriada na Service Layer, não no Repository, para respeitar a Single Responsibility Principle



