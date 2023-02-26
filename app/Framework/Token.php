<?php

namespace app\Framework;

use app\Framework\Util\Random;

class Token
{
    const TYPE_HTTP = 1;
    const TYPE_WS = 2;

    static protected $list = [];

    public function __construct(
        public string $token,
        public array $permissions,
        public array $data,
        public int $created_at,
        public int $expire_at
    ) {
    }

    public function isPermit(string $permission)
    {
        return in_array($permission, $this->permissions);
    }

    public function isExpired()
    {
        return time() > $this->expire_at;
    }

    public function __toString()
    {
        return $this->token;
    }

    static public function Get(string $token): self
    {
        if (!isset(self::$list[$token]))
            throw new \app\Framework\Exception\TokenNotFoundException();
        return self::$list[$token];
    }

    static public function Create(array $permissions, array $data, int $created_at, int $expire_at)
    {
        $token = new self(
            Random::String(32),
            $permissions,
            $data,
            $created_at,
            $expire_at
        );
        self::$list[$token->token] = $token;

        return $token;
    }

    static public function Purge()
    {
        /** @param Token $token */
        foreach (self::$list as $key => $token) {
            if ($token->isExpired()) {
                unset(self::$list[$key]);
            }
        }
    }
}
