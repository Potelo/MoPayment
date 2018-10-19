# MoPayment

## Introdução

MoPayment é baseado no Laravel Cashier e fornece uma interface para controlar assinaturas do moip.com.br

## Instalação Laravel 5.x

Instale esse pacote pelo composer:

```
composer require potelo/mo-payment
```

Se você não utiliza o [auto-discovery](https://laravel.com/docs/5.7/packages#package-discovery), Adicione o ServiceProvider em config/app.php

```php
Potelo\MoPayment\MoPaymentServiceProvider::class,
```

Agora, configure as variáveis utilizadas pelo GuPayment no seu .env:

```
MOIP_ENV=sandbox
MOIP_MODEL=App\User
MOIP_WEBHOOK_AUTHORIZATION=seu_webhook_authorization
MOIP_APITOKEN=seu_api_token
MOIP_APIKEY=seu_api_key
MOPAYMENT_SIGNATURE_TABLE=subscriptions
MOIP_MODEL_FOREIGN_KEY=user_id
MOIP_USER_MODEL_COLUMN=moip_id
MOIP_SUBSCRIPTION_MODEL_ID_COLUMN=moip_id
MOIP_SUBSCRIPTION_MODEL_PLAN_COLUMN=moip_plan
```

Antes de usar o MoPayment você precisa preparar o banco de dados. Primeiro você tem que publicar o migration.

```
php artisan vendor:publish --tag=migrations
```

Caso precise modificar ou acrescentar colunas na tabela de assinatura, basta editar os migrations publicados. Depois, basta rodar o comando php artisan migrate.

```php
use Potelo\MoPayment\MoPaymentTrait;

class User extends Authenticatable
{
    use MoPaymentTrait;
}
```

Agora vamos adicionar em config/services.php duas configurações. A classe do usuário, sua chave de api que o Iugu fornece 
e o nome da tabela utilizada para gerenciar as assinaturas, a mesma escolhida na criação do migration.

```php
    'moip' => [
        'model'  => App\User::class,
        'webhook_authorization' => env('MOIP_WEBHOOK_AUTHORIZATION'),
        'token' => env('MOIP_APITOKEN'),
        'key' => env('MOIP_APIKEY'),
        'signature_table' => env('MOPAYMENT_SIGNATURE_TABLE'),
        'env' => env('MOIP_ENV'),
        'model_foreign_key' => env('MOIP_MODEL_FOREIGN_KEY'),
        'subscription_model_id_column' => env('MOIP_SUBSCRIPTION_MODEL_ID_COLUMN'),
        'subscription_model_plan_column' => env('MOIP_SUBSCRIPTION_MODEL_PLAN_COLUMN'),
    ],
```

### Criar assinatura

Para criar uma assinatura, primeiro você precisa ter uma instância de um usuário que extende o MoPaymentTrait. Você então deve usar o método `newSubscription` para criar uma assinatura:

```php
$user = User::find(1);

$user->newSubscription('main',
    'plan_code',
    'CREDIT_CARD'
)->create();
```

### Sobrescrever valor do plano

Caso deseje sobrescrever o valor do plano, utilize o método `amount`. Ex: R$ 20,90 deve ser informado como "2090"

```php
$user = User::find(1);

$user->newSubscription('main',
    'plan_code',
    'CREDIT_CARD'
)->amount(2090)
->create();
```

### Cupom de desconto 

Se necessário, você pode associar cupons de descontos para oferecer aos assinantes do seus planos. Para informar um cupom de desconto ao criar uma assinatura utilize o método `coupon`.

```php
$user = User::find(1);

$user->newSubscription('main',
    'plan_code',
    'CREDIT_CARD'
)->coupon('codigo_cupom')
->create();
```

### Checar status da assinatura

Uma vez que o usuário assine um plano na sua aplicação, você pode verificar o status dessa assinatura através de alguns métodos. O método `subscribed` retorna **true** se o usuário possui uma assinatura ativa, mesmo se estiver no período trial:

```php
if ($user->subscribed('main')) {
    //
}
```

Se você precisa saber se um a assinatura de um usuário está no período trial, você pode usar o método `onTrial`. Esse método pode ser útil para informar ao usuário que ele está no período de testes, por exemplo:

```php
if ($user->subscription('main')->onTrial()) {
    //
}
```

Para saber se uma assinatura foi suspensa, basta usar o método `suspended` na assinatura:

```php
if ($user->subscription('main')->suspended()) {
    //
}
```

Você também pode checar se uma assinatura foi suspensa mas o usuário ainda se encontra no "período de carência". Por exemplo, se um usuário cancelar a assinatura no dia 5 de Março mas a data de vencimento é apenas no dia 10, ele está nesse período de carência até o dia 10. Para saber basta utilizar o método `onGracePeriod`:

```php
if ($user->subscription('main')->onGracePeriod()) {
    //
}
```

### Cancelar assinatura
Para cancelar uma assinatura, basta chamar o método `suspend` na assinatura do usuário:

```php
$user->subscription('main')->suspend();
```

### Reativar assinatura
Se um usuário tem uma assinatura suspensa e gostaria de reativá-la, basta utilizar o método `resume`.
```php
$user->subscription('main')->resume();
```

### Faturas
Você pode facilmente pegar as faturas de um usuário através do método `invoices`:

```php
$invoices = $user->invoices('subscription_code');
```


### Assinantes
Quando você utiliza o método `newSubscription` o cliente é criado automaticamente. Porém para criar um cliente manualmente, você pode utilizar o método `createAsMoipCustomer`.
```php
$options = [
    'fullname' => 'Joao Silva',
    'email' => 'joao_silva@example.com',
    'phone_area_code' => '11',
    'phone_number' => '999887766',
    'cpf' => '01234567891',
    'birthdate_day' => '2',
    'birthdate_month' => '2',
    'birthdate_year' => '2000',
    'address' => [
        'street' => 'Rua de Cima',
        'number' => '1000',
        'complement' => 'Casa',
        'district' => 'Bairro Azul',
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BRA',
        'zipcode' => '05015010',
    ],
    'billing_info' => [
        'credit_card' => [
            'holder_name' => 'Joao Silva',
            'number' => '4111111111111111',
            'expiration_month' => '08',
            'expiration_year' => '20'
        ]
    ]
];

// Criar assinante no Moip
$user->createAsMoipCustomer($options);
```

## Webhooks

[Webhooks](https://dev.moip.com.br/v1.5/reference#webhooks-1) são endereços (URLs) para onde o Moip dispara notificações para certos eventos que ocorrem na sua conta. Para utilizar você precisa configurar uma rota para o método `handleWebhook`, a mesma rota que você configurou no seu painel do Moip:
```php
Route::post('webhook', '\Potelo\MoPayment\Http\Controllers\WebhookController@handleWebhook');
```

O MoPayment tem métodos para atualizar o seu banco de dados caso uma assinatura seja suspensa. Apontando a rota para esse método, isso ocorrerá de forma automática.
Lembrando que você precisa desativar a [proteção CRSF](https://laravel.com/docs/5.7/csrf#csrf-excluding-uris) para essa rota. Você pode colocar a URL em `except` no middleware `VerifyCsrfToken`:
```php
protected $except = [
   'webhook',
];
```

### Outros webhooks
O Moip possui vários outros webhooks e para você criar para outros eventos basta estender o `WebhookController`. Seus métodos devem corresponder a **handle** + o nome do evento em "camelCase". Por exemplo, ao criar uma nova fatura, o Moip envia um gatilho com o seguinte evento: `invoice.created`, então basta você criar um método chamado `handleInvoiceCreated`.
```php
Route::post('webhook', 'MeuWebhookController@handleWebhook');
```

```php
<?php

namespace App\Http\Controllers;

use Potelo\GuPayment\Http\Controllers\WebhookController;

class MeuWebhookController extends WebhookController {

    public function handleInvoiceCreated(array $payload)
    {
        return 'Fatura criada: ' . $payload['resource']['id'];
    }
}
```

Caso queira testar os webhooks em ambiente local, você pode utilizar o [ngrok](https://ngrok.com/).
