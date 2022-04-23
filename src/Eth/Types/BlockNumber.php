<?php

namespace App\Eth\Types;

class BlockNumber
{
    public const EARLIEST = 'earliest';
    public const PENDING = 'pending';
    public const LATEST = 'latest';

    private $tag;

    public static function latest(): BlockNumber
    {
        return new self(self::LATEST);
    }

    public static function earliest(): BlockNumber
    {
        return new self(self::EARLIEST);
    }

    public static function pending(): BlockNumber
    {
        return new self(self::PENDING);
    }

    public function __construct(string $tag = self::LATEST)
    {
        if (is_numeric($tag)) {
            $tag = '0x' . dechex($tag);
        } else {
            if (!in_array($tag, [self::LATEST, self::EARLIEST, self::PENDING])) {
                throw new \InvalidArgumentException('wrong BlockNumber');
            }
        }
        $this->tag = $tag;
    }

    public function __toString(): string
    {
        return $this->tag;
    }

    public function toString(): string
    {
        return $this->tag;
    }
}
