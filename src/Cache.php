<?php

namespace Arrilot\BitrixCacher;

use AbortCacheException;
use Bitrix\Main\Data\StaticHtmlCache;
use Closure;
use CPHPCache;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * (c) https://github.com/arrilot/bitrix-cacher
 */
class Cache
{
    
    const INIT_DIR = '/citrus/smartform';

	/**
	 * Store closure's result in the cache for a given number of minutes.
	 *
	 * @param string  $key
	 * @param double  $minutes
	 * @param Closure $callback
	 * @param bool|string $initDir
	 *
	 * @return mixed
	 */
	public static function remember($key, $minutes, Closure $callback, $initDir = null)
	{
		global $CACHE_MANAGER;

		$initDir = self::INIT_DIR . '/' . $initDir . '/';

		$minutes = (double) $minutes;
		if ($minutes <= 0) {
			return $callback();
		}

		$obCache = new CPHPCache();
		if ($obCache->InitCache($minutes*60, md5(serialize($key)), $initDir)) {
			$vars = $obCache->GetVars();

			return $vars['cache'];
		}

		$obCache->StartDataCache();
		$CACHE_MANAGER->StartTagCache($initDir);
		$cache = $callback();
		$CACHE_MANAGER->EndTagCache();
		$obCache->EndDataCache(array('cache' => $cache));

		return $cache;
	}

	/**
	 * Store closure's result in the cache for a long time.
	 *
	 * @param string $key
	 * @param Closure $callback
	 * @param bool|string $initDir
	 *
	 * @return mixed
	 */
	public static function rememberForever($key, Closure $callback, $initDir = self::INIT_DIR)
	{
		return static::remember($key, 99999999, $callback, $initDir);
	}

	/**
	 * Flush cache for a specified dir.
	 *
	 * @param string $initDir
	 *
	 * @return bool
	 */
	public static function flush($initDir = self::INIT_DIR)
	{
		return BXClearCache(true, $initDir);
	}

	/**
	 * Flushes all bitrix cache.
	 *
	 * @return void
	 */
	public static function flushAll()
	{
		$GLOBALS["CACHE_MANAGER"]->cleanAll();
		$GLOBALS["stackCacheManager"]->cleanAll();
		$staticHtmlCache = StaticHtmlCache::getInstance();
		$staticHtmlCache->deleteAll();
		BXClearCache(true);
	}

	/**
	 * Set tag cache for iblock-dependent result to clear tag cache
	 *
	 * @param int $iblockId
	 */
	public static function registerIblockCacheTag($iblockId)
	{
		if (defined("BX_COMP_MANAGED_CACHE") && \CModule::IncludeModule('iblock'))
		{
			\CIBlock::registerWithTagCache($iblockId);
		}
	}
}
