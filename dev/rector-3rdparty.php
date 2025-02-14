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
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php71\Rector\ClassConst\PublicConstantVisibilityRector;
use Rector\Php53\Rector\Ternary\TernaryToElvisRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;

return static function (RectorConfig $rectorConfig): void {
  
  $rectorConfig->parallel(processTimeout: 600, maxNumberOfProcess: 2, jobSize: 6);
  $rectorConfig->fileExtensions(["phtml", "php"]);
  $rectorConfig->cacheClass(FileCacheStorage::class);
  $rectorConfig->cacheDirectory("/dev/shm/openmage/rector");
  $rectorConfig->importNames();                               // Keeps short class names with "use" statements intact
  $rectorConfig->phpVersion(PhpVersion::PHP_82);              // Only when deviating from composer.json
  $rectorConfig->removeUnusedImports(false);                  // Maybe interesting sometimes, not by default
  // $rectorConfig->disableParallel();                           // In case of errors, disable parallel
  
  $rectorConfig->paths([
    // __DIR__ . '/../app',
    // __DIR__ . '/../dev',
    // __DIR__ . '/../errors',
    // __DIR__ . '/../lib',
    // __DIR__ . '/../pub',
    // __DIR__ . '/../shell',
    // __DIR__ . '/../tests',
    
    __DIR__ . "/../app/code/community",
    __DIR__ . "/../app/code/local",
    __DIR__ . "/../lib/Afterpay",
    __DIR__ . "/../lib/Amasty",
    __DIR__ . "/../lib/Ebizmarts",
    __DIR__ . "/../lib/FireGento",
    __DIR__ . "/../lib/Google",
    __DIR__ . "/../lib/Mandrill",
    __DIR__ . "/../lib/MaxMind",
    __DIR__ . "/../lib/Mollie",
    __DIR__ . "/../lib/Mpdf",
    __DIR__ . "/../lib/n98-magerun",
    __DIR__ . "/../lib/Net",
    __DIR__ . "/../lib/SimpleHtmlDoom",
    __DIR__ . "/../lib/TM",
    __DIR__ . "/../shell/scheduler.php",
  ]);
  
  $rectorConfig->skip([
    __DIR__.'/../.git',
    __DIR__.'/../.idea',
    __DIR__.'/../.vscode',
    __DIR__.'/../.well-known',
    __DIR__.'/../assets',
    __DIR__.'/../media',
    __DIR__.'/../var',
    __DIR__."/../vendor",
    __DIR__."/../**/vendor",
    __DIR__."/../protected",
    __DIR__."/../lib/n98-magerun",
    
    // Usually not desirable:
    // ReturnNeverTypeRector::class,
    
    /* SKIP AS NEEDED */
    // ShortenElseIfRector::class,                               // Makes things ugly
    // AddLiteralSeparatorToNumberRector::class,
    // RemoveUnusedVariableAssignRector::class,
    // RemoveDuplicatedCaseInSwitchRector::class,                // Removes comments
    // SimplifyUselessVariableRector::class,
    // SwitchNegatedTernaryRector::class,
    // RemoveExtraParametersRector::class,
    // LongArrayToShortArrayRector::class,                       // Rewritten code is a mess, but good detector for manual rewrite
    RenameClassRector::class,                                 // Don't disable permanently, but Symfony/Laravel is bullshitting with this
    StringToClassConstantRector::class,                       // Don't disable permanently, but Symfony/Laravel is bullshitting with this
    RemoveDoubleAssignRector::class,                          // Can be annoying
    RemoveAlwaysElseRector::class,                            // Doesn't always make it better
    BooleanInIfConditionRuleFixerRector::class,               // Can be pedantic

    // ALWAYS SKIP
    RemoveAlwaysTrueIfConditionRector::class,                 // Can disrupt code that is prepared for debuggging or temporary code
    RemoveUnusedForeachKeyRector::class,                      // Nit-picking
    SimplifyEmptyCheckOnEmptyArrayRector::class,              // Increases complexity
    DisallowedEmptyRuleFixerRector::class,                    // Nit-picking
    VariableInStringInterpolationFixerRector::class,          // Breaks bash-code
    ChangeSwitchToMatchRector::class,                         // Increases complexity
    EncapsedStringsToSprintfRector::class,                    // Why even
    NewlineAfterStatementRector::class,                       // Get a life
    SplitDoubleAssignRector::class,                           // Get a life
    SymplifyQuoteEscapeRector::class,                         // Don't fix what ain't broken
    WrapEncapsedVariableInCurlyBracesRector::class,           // Breaks bash-code
    CompleteMissingIfElseBracketRector::class,                // Get a life
    SingleInArrayToCompareRector::class,                      // Get a life
    ChangeOrIfContinueToMultiContinueRector::class,           // Bullshit
    DeclareStrictTypesRector::class,                          // Adds strict_types without checking?
    CompactToVariablesRector::class,                          // Makes it worse
    FuncCallToNewRector::class,                               // Makes it worse
    FuncCallToStaticCallRector::class,                        // Makes it worse
    ExplicitReturnNullRector::class,                          // Pedantic drone
    NewlineBeforeNewAssignSetRector::class,                   // Get a life
    RemoveUnusedVariableAssignRector::class,                  // Also also
    MinutesToSecondsInCacheRector::class,                     // Seems to do nothing and messes up whitespace
    AbsolutizeRequireAndIncludePathRector::class,             // Causes bugs with differences between pwd and __DIR__
    RemoveUnreachableStatementRector::class,                  // Can remove debug code, revert code or WIP code
    SetList::TYPE_DECLARATION,
    SetList::NAMING,
  ]);
  
  /* OVERRIDE RULES/SETS TEMPORARILY -- DON'T FORGET TO SKIP BEFORE "RETURN;" */
  
  // $rectorConfig->rules([SimplifyUselessVariableRector::class]);
  // $rectorConfig->rules([StringToClassConstantRector::class]);
  // $rectorConfig->rules([CountArrayToEmptyArrayComparisonRector::class]);
  // $rectorConfig->rules([InlineArrayReturnAssignRector::class]);
  // $rectorConfig->rules([SimplifyIfReturnBoolRector::class]);
  // $rectorConfig->rules([RenameFunctionRector::class]);
  // $rectorConfig->rules([AbsolutizeRequireAndIncludePathRector::class]);
  // $rectorConfig->rules([PostIncDecToPreIncDecRector::class]);
  // $rectorConfig->sets([LaravelLevelSetList::UP_TO_LARAVEL_110,]);
  // return;
  
  // /* SETS */
  
  $rectorConfig->sets([
    LevelSetList::UP_TO_PHP_82,
  ]);
};
