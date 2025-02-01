<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector as CodeQuality;
use Rector\DeadCode\Rector as DeadCode;
use Rector\TypeDeclaration\Rector as TypeDeclaration;
use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Transform\Rector\FuncCall\FuncCallToConstFetchRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

return static function (RectorConfig $rectorConfig): void {
  
  // Specify a path that works locally as well as on CI job runners.
  $uid        = posix_getuid();
  $shell_user = posix_getpwuid($uid);
  
  $rectorConfig->indent(" ", 2);
  $rectorConfig->parallel($seconds = 600, $maxNumberOfProcess = 6, $jobSize = 30);
  $rectorConfig->fileExtensions(["phtml", "php"]);
  $rectorConfig->cacheClass(FileCacheStorage::class);
  $rectorConfig->cacheDirectory("/dev/shm/openmage/rector");
  $rectorConfig->importNames();
  
  $rectorConfig->phpVersion(PhpVersion::PHP_83);          // Only when deviating from composer.json
  $rectorConfig->removeUnusedImports(false);              // Maybe interesting sometimes
  
  // In case of errors:
  // $rectorConfig->disableParallel();
  
  $rectorConfig->paths([
    __DIR__ . "/../*.php",
    __DIR__ . "/../app",
    __DIR__ . "/../lib",
  ]);
  
  $rectorConfig->skip([
    __DIR__ . "/../**/vendor",
    // __DIR__ . "/../app/code/community/Pay/Payment/vendor",
    // __DIR__ . "/../lib/Afterpay/vendor",
    // __DIR__ . "/../lib/TM/Geoip/vendor",
    __DIR__ . "/../protected",
    // __DIR__ . "/../vendor",
    
    // Usually not desirable:
    // ReturnNeverTypeRector::class,
    
    /* SKIP AS NEEDED */
    
    // ShortenElseIfRector::class,
    // AddLiteralSeparatorToNumberRector::class,
    // RemoveUnusedVariableAssignRector::class,
    // RemoveDuplicatedCaseInSwitchRector::class,
    // SimplifyUselessVariableRector::class,
    // SwitchNegatedTernaryRector::class,
    // RemoveAlwaysTrueIfConditionRector::class,
    // RemoveExtraParametersRector::class,
    // LongArrayToShortArrayRector::class,
    
    /* ALWAYS SKIP */
    
    RemoveUnusedForeachKeyRector::class,                  // Nit-picking
    SimplifyEmptyCheckOnEmptyArrayRector::class,          // Increases complexity
    DisallowedEmptyRuleFixerRector::class,                // Nit-picking
    VariableInStringInterpolationFixerRector::class,      // Breaks bash-code
    ChangeSwitchToMatchRector::class,                     // Increases complexity
    EncapsedStringsToSprintfRector::class,                // Why even
    NewlineAfterStatementRector::class,                   // Get a life
    SplitDoubleAssignRector::class,                       // Get a life
    SymplifyQuoteEscapeRector::class,                     // Don't fix what ain't broken
    WrapEncapsedVariableInCurlyBracesRector::class,       // Breaks bash-code
    CompleteMissingIfElseBracketRector::class,            // Get a life
    SingleInArrayToCompareRector::class,                  // Get a life
    ChangeOrIfContinueToMultiContinueRector::class,       // Bullshit
    DeclareStrictTypesRector::class,                      // Adds strict_types without checking?
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
};
