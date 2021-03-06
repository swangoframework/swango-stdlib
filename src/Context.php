<?php
use Swoole\Coroutine;
class Context {
    const CONTEXT_COUNTER_KEY = 'c';
    const CONTEXT_KEY = 'a';
    private static $size = 0, $static_context;
    private function __construct() {
        ++ self::$size;
    }
    public function __destruct() {
        -- self::$size;
    }
    private static function getContext() {
        $context = Coroutine::getContext();
        if (! isset($context)) {
            if (self::$static_context === null)
                self::$static_context = new \ArrayObject();
            return self::$static_context;
        }
        return $context;
    }
    public static function &get(string $key) {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $ob = $context->offsetGet(static::CONTEXT_KEY);
            if (property_exists($ob, $key)) {
                return $ob->{$key};
            }
        }
        $t = null;
        return $t;
    }
    public static function getAndDelete(string $key) {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $ob = $context->offsetGet(static::CONTEXT_KEY);
            if (property_exists($ob, $key)) {
                $ret = $ob->{$key};
                unset($ob->{$key});
                return $ret;
            }
        }
        return null;
    }
    public static function hGet(string $key, string $hash_key) {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $ob = $context->offsetGet(static::CONTEXT_KEY);
            if (property_exists($ob, $key)) {
                $arr = $ob->{$key};
                if (is_array($arr) && array_key_exists($hash_key, $arr))
                    return $arr[$hash_key];
            }
        }
        return null;
    }
    public static function has(string $key): bool {
        $context = self::getContext();
        return $context->offsetExists(static::CONTEXT_KEY) &&
             property_exists($context->offsetGet(static::CONTEXT_KEY), $key);
    }
    public static function hHas(string $key, string $hash_key): bool {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $ob = $context->offsetGet(static::CONTEXT_KEY);
            if (property_exists($ob, $key)) {
                $arr = $ob->{$key};
                if (is_array($arr) && array_key_exists($hash_key, $arr))
                    return true;
            }
        }
        return false;
    }
    public static function set(string $key, $value): void {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $context->offsetGet(static::CONTEXT_KEY)->{$key} = $value;
        } else {
            $ob = new \stdClass();
            $ob->{$key} = $value;
            $context->offsetSet(static::CONTEXT_KEY, $ob);
            if (! $context->offsetExists(static::CONTEXT_COUNTER_KEY))
                $context->offsetSet(static::CONTEXT_COUNTER_KEY, new self());
        }
    }
    public static function hSet(string $key, string $hash_key, $value): void {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $ob = $context->offsetGet(static::CONTEXT_KEY);
            if (property_exists($ob, $key)) {
                $arr = &$ob->{$key};
                if (is_array($arr)) {
                    $arr[$hash_key] = $value;
                } else {
                    $arr = [
                        $hash_key => $value
                    ];
                }
            } else {
                $ob->{$key} = [
                    $hash_key => $value
                ];
            }
        } else {
            $ob = new \stdClass();
            $ob->{$key} = [
                $hash_key => $value
            ];
            $context->offsetSet(static::CONTEXT_KEY, $ob);
            if (! $context->offsetExists(static::CONTEXT_COUNTER_KEY))
                $context->offsetSet(static::CONTEXT_COUNTER_KEY, new self());
        }
    }
    public static function push(string $key, $value): void {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $ob = $context->offsetGet(static::CONTEXT_KEY);
            if (property_exists($ob, $key)) {
                $arr = &$ob->{$key};
                if (is_array($arr)) {
                    $arr[] = $value;
                } else {
                    $arr = [
                        $value
                    ];
                }
            } else {
                $ob->{$key} = [
                    $value
                ];
            }
        } else {
            $ob = new \stdClass();
            $ob->{$key} = [
                $value
            ];
            $context->offsetSet(static::CONTEXT_KEY, $ob);
            if (! $context->offsetExists(static::CONTEXT_COUNTER_KEY))
                $context->offsetSet(static::CONTEXT_COUNTER_KEY, new self());
        }
    }
    public static function del(string $key): bool {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $ob = $context->offsetGet(static::CONTEXT_KEY);
            if (property_exists($ob, $key)) {
                unset($ob->{$key});
                return true;
            }
        }
        return false;
    }
    public static function hDel(string $key, string $hash_key): bool {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $ob = $context->offsetGet(static::CONTEXT_KEY);
            if (property_exists($ob, $key)) {
                $arr = &$ob->{$key};
                if (is_array($arr) && array_key_exists($hash_key, $arr)) {
                    unset($arr[$hash_key]);
                    return true;
                }
            }
        }
        return false;
    }
    public static function clear(?int $uid = null): bool {
        $context = self::getContext();
        if ($context->offsetExists(static::CONTEXT_KEY)) {
            $context->offsetUnset(static::CONTEXT_KEY);
            return true;
        }
        return false;
    }
    public static function getSize(): int {
        return self::$size;
    }
}