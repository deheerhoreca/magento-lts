<?php

declare(strict_types=1);

use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;

return static function (Rector\Config\RectorConfig $rectorConfig): void {
  
  // Specify a path that works locally as well as on CI job runners.
  $uid = posix_getuid();
  $shell_user = posix_getpwuid($uid);
  $home_dir = $shell_user["dir"] ?? "~";
  $cache_dir = "{$home_dir}/tmp/rector/".basename(realpath(__DIR__."/.."));
  
  $rectorConfig->indent(" ", 2);
  // $rectorConfig->phpVersion(Rector\ValueObject\PhpVersion::PHP_81); // Use if different then composer.json
  $rectorConfig->parallel($seconds = 600, $maxNumberOfProcess = 12, $jobSize = 30);
  $rectorConfig->fileExtensions(["phtml", "php"]);
  $rectorConfig->cacheClass(Rector\Caching\ValueObject\Storage\FileCacheStorage::class);
  
  // In case of errors:
  // $rectorConfig->disableParallel();
  
  $rectorConfig->paths([
    __DIR__ . "/../*.php",
    __DIR__ . "/../app",
    __DIR__ . "/../lib",
  ]);
  
  $rectorConfig->skip([
    // __DIR__ . "/../vendor",
    __DIR__ . "/../media",
    __DIR__ . "/../tool_*.php",
    __DIR__ . "/../lib/TM/Geoip/vendor",
    __DIR__ . "/../lib/Afterpay/vendor",
    
    // These are bad:
    Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class,
    
    // Usually not desirable:
    Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector::class,
    Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class,
    Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector::class,
    Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector::class,
    Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class,
    Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,              // Harder to read code
    Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector::class,
    Rector\Php54\Rector\Array_\LongArrayToShortArrayRector::class,                // Harder to read code
    
    // Sometimes not desirable:
    Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector::class,
    // Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector::class,
  ]);
  
  // Register a single rule:
  // $rectorConfig->rule(Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class);
  // $rectorConfig->rule(Rector\Php54\Rector\Array_\LongArrayToShortArrayRector::class);
  // $rectorConfig->rule(Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector::class);
  
  // Define sets of rules
  $rectorConfig->sets([
    // SetList::PHP_81,
    // LevelSetList::UP_TO_PHP_81,
    // LevelSetList::UP_TO_PHP_82,
    LevelSetList::UP_TO_PHP_83,
    SetList::DEAD_CODE,
    SetList::CODE_QUALITY,
    SetList::CODING_STYLE,
    SetList::STRICT_BOOLEANS,
    SetList::GMAGICK_TO_IMAGICK,
    SetList::NAMING,
    SetList::PRIVATIZATION,
    SetList::EARLY_RETURN,
    SetList::INSTANCEOF,
    
    // Broken:
    // SetList::TYPE_DECLARATION,
  ]);
};
