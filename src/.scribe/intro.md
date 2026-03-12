# Introduction

API RESTful para processamento de apostas e pagamentos. Autenticação via Bearer Token (Laravel Sanctum).

<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>

    Esta documentação descreve todos os endpoints disponíveis na Bet Backend API.

    ## Autenticação

    A maioria dos endpoints exige autenticação via **Bearer Token**. Para obter um token, utilize o endpoint `POST /api/auth/login` e inclua o token retornado no header `Authorization: Bearer {token}`.

    ## Roles disponíveis

    | Role | Permissões |
    |------|------------|
    | `admin` | Acesso total |
    | `manager` | Gerencia usuários e produtos |
    | `finance` | Leitura de clientes, transações e gestão de produtos |

    ## Valores monetários

    Todos os valores (`amount`) são expressos em **centavos** (integer). Exemplo: `9999` = R$ 99,99.

