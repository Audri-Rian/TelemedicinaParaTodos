# Changelog

Todas as alterações notáveis do projeto são documentadas neste arquivo.

A versão do projeto é definida em **package.json** (`version`) e segue [Semantic Versioning](https://semver.org/) (MAJOR.MINOR.PATCH).

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

---

## [1.1.0] - 2025-02-09

### Added

- **CodeRabbit**: Configuração super configurável para o projeto (PR #2).
- **Regras de negócio**: Novo sistema de regras de negócio, preparatório para centralizar em `telemedicine.php` como principal arquivo de configuração (PR #1).

---

## [1.0.0] - 2025-01-30

Versão inicial documentada. Corresponde ao estado atual do repositório com plataforma de telemedicina funcional.


### Observação

Ao implementar novas funcionalidades ou correções, atualize o **package.json** (campo `version`) e adicione uma nova seção neste CHANGELOG com a data e os itens em **Added**, **Changed**, **Fixed**, **Removed** ou **Security**, conforme o caso.

Exemplo para a próxima versão:

```markdown
## [1.1.0] - YYYY-MM-DD

### Added
- Descrição do que foi adicionado.

### Changed
- Descrição do que mudou em comportamento ou API.

### Fixed
- Descrição do que foi corrigido.
```
