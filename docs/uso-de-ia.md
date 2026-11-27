# Uso de inteligência artificial

Registro vivo do que foi feito com auxílio de IA. A seção correspondente do `README.md` deve dizer o mesmo, quando o README existir.

O autor do projeto precisa revisar cada item e conseguir explicar a decisão na entrevista. Enquanto isso não acontece, o campo de revisão fica como `Pendente de revisão do autor`.

## 2026-11-27 — Planejamento

- Ferramenta: Cursor, Grok 4.7
- Etapa: definição do produto e quebra em épicos, antes do Ticket 01
- Feito com auxílio: `docs/epicos/`.
- Finalidade: organizar o Libris (Laravel, Inertia, Vue, MySQL, Juntos Se Faz em sandbox) para caber no edital sem perder o registro do que a IA produziu
- Revisão: Pendente de revisão do autor. Nenhum código da aplicação foi gerado nesta etapa

## 2026-11-27 — Yarn e Docker

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 01 e Ticket 02, ainda no planejamento
- Feito com auxílio: ajuste em `docs/epicos/01-fundacao-e-ambiente.md`, na decisão 14 do índice e na regra de documentação
- Finalidade: fixar Yarn como gerenciador do front e manter o Docker Compose, que é diferencial do edital
- Revisão: Pendente de revisão do autor

## 2026-11-27 — Ticket 01, criação do projeto

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 01
- Feito com auxílio: projeto criado com `laravel new libris --vue --pest --database=mysql --no-interaction` e movido para a raiz. Ajustes depois do instalador: nome Libris, locale `pt_BR`, fuso `America/Sao_Paulo`, scripts do Composer no Yarn, pastas `app/Services/Payments` e `app/Policies`, `README.md` e `.nvmrc`
- Finalidade: iniciar o Libris com o starter oficial de Vue e Inertia
- Revisão: Pendente de revisão do autor. O `pest --drift` do instalador falhou e o plugin temporário foi removido. O instalador gravou SQLite mesmo com `--database=mysql`

## 2026-11-27 — Status dos tickets

- Ferramenta: Cursor, Grok 4.7
- Etapa: planejamento
- Feito com auxílio: campo **Status** em todos os tickets de `docs/epicos/`
- Finalidade: distinguir o que já foi feito do que ainda está pendente. O Ticket 01 ficou `Concluído`; os demais, `Pendente`
- Revisão: Pendente de revisão do autor
