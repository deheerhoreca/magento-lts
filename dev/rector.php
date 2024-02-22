<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;

return static function (RectorConfig $rectorConfig): void {
  // $rectorConfig->indent(' ', 2);
  $rectorConfig->phpVersion(PhpVersion::PHP_81);
  $rectorConfig->parallel($seconds = 600, $maxNumberOfProcess = 12, $jobSize = 30);

  // In case of errors:
  // $rectorConfig->disableParallel();
  
  $rectorConfig->paths([
    __DIR__ . '/../*.php',
    __DIR__ . '/../app',
    __DIR__ . '/../lib',
  ]);
  
  $rectorConfig->skip([
    __DIR__ . '/../vendor',
    __DIR__ . '/../media',
    __DIR__ . '/../apc_3Ga0.php',
    __DIR__ . '/../ocp_foobar.php',
    __DIR__ . '/../redis_3fA.php',
    __DIR__ . '/../lib/TM/Geoip/vendor',
    __DIR__ . '/../lib/Afterpay/vendor',
    
    // Usually not desirable:
    Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector::class,
    Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class,
    Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector::class,
    Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector::class,
    Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class,
    Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class,
    
    // Sometimes not desirable:
    // Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector::class,
    // Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector::class,
    Rector\Php54\Rector\Array_\LongArrayToShortArrayRector::class,
  ]);
  
  // Register a single rule
  // $rectorConfig->rule(Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class);
  
  // Define sets of rules
  $rectorConfig->sets([
    // LevelSetList::UP_TO_PHP_81,
    // LevelSetList::UP_TO_PHP_82,
    // LevelSetList::UP_TO_PHP_83,
    SetList::PHP_81,
    // SetList::PHP_82,
    // SetList::PHP_83,
    // SetList::DEAD_CODE,
    // SetList::CODE_QUALITY,
    // SetList::CODING_STYLE,
    // SetList::STRICT_BOOLEANS,
    // SetList::GMAGICK_TO_IMAGICK,
    // SetList::NAMING,
    // SetList::PRIVATIZATION,
    // SetList::EARLY_RETURN,
    // SetList::INSTANCEOF,
    
    // Broken:
    // SetList::TYPE_DECLARATION,
  ]);
  
  // Ensure file system caching is used instead of in-memory.
  $rectorConfig->cacheClass(FileCacheStorage::class);
  
  // Specify a path that works locally as well as on CI job runners.
  $uid = posix_getuid();
  $shell_user = posix_getpwuid($uid);
  $home_dir = $shell_user["dir"] ?? "~";
  $cache_dir = "{$home_dir}/tmp/rector/".basename(realpath(__DIR__."/.."));
  $rectorConfig->cacheDirectory($cache_dir);
  $rectorConfig->fileExtensions(['phtml', 'php']);
};
