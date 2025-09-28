<?php

namespace App\Repositories\Token;

use App\Models\User;

//este repository nao extend ao baseRespository fugindo do padrão do projeto
class Create
{
    /**
     * Id do usuário
     *
     * @var string
     */
    protected string $id;

    /**
     * Permissões
     *
     * @var array
     */
    protected array $permissions;

    /**
     * Model base para implementação
     *
     * @var string
     */
    protected string $model;

    public function __construct(string $id, array $permissions = [])
    {
        $this->id          = $id;
        $this->permissions = $permissions;
        $this->model       = User::class;
    }

    /**
     * Criação de token de acesso do usuário
     *
     * @return string
     */
    public function handle(): string
    {
        return app($this->model)
            ->findOrFail($this->id)
            ->createToken(config('auth.token_name'), $this->permissions)
            ->plainTextToken;
    }
}
