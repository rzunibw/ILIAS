<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilMailCachedAddressType
 */
class ilMailCachedAddressType implements ilMailAddressType
{
    /** @var array[] */
    protected static array $usrIdsByAddressCache = [];
    /** @var bool[] */
    protected static array $isValidCache = [];
    protected ilMailAddressType $inner;
    protected bool $useCache = true;

    public function __construct(ilMailAddressType $inner, bool $useCache)
    {
        $this->inner = $inner;
        $this->useCache = $useCache;
    }

    
    private function getCacheKey() : string
    {
        $address = $this->getAddress();
        return (string) $address;
    }

    /**
     * @inheritdoc
     */
    public function validate(int $senderId) : bool
    {
        $cacheKey = $this->getCacheKey();

        if (!$this->useCache || !isset(self::$isValidCache[$cacheKey])) {
            self::$isValidCache[$cacheKey] = $this->inner->validate($senderId);
        }

        return self::$isValidCache[$cacheKey];
    }

    /**
     * @inheritdoc
     */
    public function getErrors() : array
    {
        return $this->inner->getErrors();
    }

    /**
     * @inheritdoc
     */
    public function getAddress() : ilMailAddress
    {
        return $this->inner->getAddress();
    }

    /**
     * @inheritdoc
     */
    public function resolve() : array
    {
        $cacheKey = $this->getCacheKey();

        if (!$this->useCache || !isset(self::$usrIdsByAddressCache[$cacheKey])) {
            self::$usrIdsByAddressCache[$cacheKey] = $this->inner->resolve();
        }

        return self::$usrIdsByAddressCache[$cacheKey];
    }
}
