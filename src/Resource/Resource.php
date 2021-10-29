<?php

namespace Coinbase\Wallet\Resource;

class Resource
{
    /** @var string */
    private $id;

    /** @var string */
    private $resource;

    /** @var string */
    private $resourcePath;

    /** @var array */
    private $rawData;

    public function __construct($resourceType, $resourcePathOrAttrs = null, $id = null)
    {
        $this->resource = $resourceType;
        $this->id = $id;
        if(is_array($resourcePathOrAttrs))
        {
            $this->updateAttributes($resourcePathOrAttrs);
        }
        else
        {
            $this->resourcePath = $resourcePathOrAttrs;
            // extract id from resource path
            if (!$id && $resourcePathOrAttrs) {
                $parts = explode('/', $resourcePathOrAttrs);
                $this->id = array_pop($parts);
            }
        }
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getResourceType(): string
    {
        return $this->resource;
    }

    public function getResourcePath(): ?string
    {
        return $this->resourcePath;
    }

    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    public function isExpanded(): bool
    {
        return (Boolean) $this->rawData;
    }

    public function updateAttributes($attrHash)
    {
      foreach ($attrHash as $attr => $val)
      {
        $action = "set" . ucfirst(self::underscoreToCamelCase($attr));
        if(is_callable(array($this, $action)))
        {
          $this->$action($val);
        }
      }
    }

    private static function underscoreToCamelCase( $string )
    {
        $func = function ($c) { return strtoupper($c[1]); };
        return preg_replace_callback('/_([a-z])/', $func, $string);
    }
}
