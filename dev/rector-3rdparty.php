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
    __DIR__ . "/../app/code/community",
    __DIR__ . "/../app/code/local",
    __DIR__ . "/../lib/Afterpay",
    __DIR__ . "/../lib/Amasty",
    __DIR__ . "/../lib/Ebizmarts",
    __DIR__ . "/../lib/FireGento",
    __DIR__ . "/../lib/Google",
    __DIR__ . "/../lib/Mandrill",
    __DIR__ . "/../lib/MaxMind",
    __DIR__ . "/../lib/Mpdf",
    __DIR__ . "/../lib/n98-magerun",
    __DIR__ . "/../lib/Net",
    __DIR__ . "/../lib/SimpleHtmlDoom",
    __DIR__ . "/../lib/TM",
    __DIR__ . "/../tool_apc.php",
    __DIR__ . "/../tool_opc.php",
    __DIR__ . "/../tool_redis.php",
    __DIR__ . "/../shell/aoe_classpathcache.php",
    __DIR__ . "/../shell/getMailchimpResponse.php",
    __DIR__ . "/../shell/rewrites_doctor.php",
    __DIR__ . "/../shell/sooqr.php",
  ]);
  
  $rectorConfig->skip([
    __DIR__ . "/../vendor",
    __DIR__ . "/../lib/TM/Geoip/vendor",
    __DIR__ . "/../lib/Afterpay/vendor",
    __DIR__ . "/../app/code/community/Pay/Payment/vendor",
    
    // These are bad:
    Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class,
    
    // Usually not desirable:
    Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector::class,
    Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class,
    Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector::class,
    Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector::class,
    Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class,
    Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,                    // Harder to read code
    Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector::class,
    Rector\Php54\Rector\Array_\LongArrayToShortArrayRector::class,                      // Harder to read code
    
    // Enable these to do only minimally invasive changes (3rd party code): 
    Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector::class,                       // Harder to read code
    Rector\Php71\Rector\ClassConst\PublicConstantVisibilityRector::class,               // Not necessary for 3rd party code
    Rector\Php53\Rector\Ternary\TernaryToElvisRector::class,                            // Not necessary for 3rd party code
    Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector::class,           // Not necessary for 3rd party code
    Rector\Php80\Rector\FunctionLike\MixedTypeRector::class,                            // Non-sensical for 3rd party code
    Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class,  // Too invasive
    Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector::class,                    // Too invasive
    Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector::class,                // Too invasive
  ]);
  
  // Register a single rule:
  // $rectorConfig->rule(Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class);
  // $rectorConfig->rule(Rector\Php54\Rector\Array_\LongArrayToShortArrayRector::class);
  // $rectorConfig->rule(Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector::class);
  // $rectorConfig->rule(Rector\Php80\Rector\NotIdentical\StrContainsRector::class);
  
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
