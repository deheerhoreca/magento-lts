<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\CodeQuality\Rector as CodeQuality;
use Rector\DeadCode\Rector as DeadCode;
use Rector\TypeDeclaration\Rector as TypeDeclaration;
use Rector\ValueObject\PhpVersion;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelLevelSetList;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector;
use Rector\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector;
use Rector\CodeQuality\Rector\Identical\SimplifyArraySearchRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
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
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;
use Rector\Transform\Rector\FuncCall\FuncCallToNewRector;
use Rector\Transform\Rector\FuncCall\FuncCallToStaticCallRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use RectorLaravel\Rector\StaticCall\MinutesToSecondsInCacheRector;
use Rector\Transform\Rector\String_\StringToClassConstantRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\ClassMethod\ExplicitReturnNullRector;
use Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Rector\DeadCode\Rector\If_\ReduceAlwaysFalseIfOrRector;
use Rector\DeadCode\Rector\Array_\RemoveDuplicatedArrayKeyRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector;
use Rector\DeadCode\Rector\Assign\RemoveDoubleAssignRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\Strict\Rector\If_\BooleanInIfConditionRuleFixerRector;
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
    __DIR__ . "/../app/design/frontend/rwd/dhh/",
    __DIR__ . "/../app/design/adminhtml/default/dhh/",
    __DIR__ . "/../app/code/local/DeHeerHoreca/",
    __DIR__ . "/../lib/Chefstore/",
    __DIR__ . "/../dev/",
    __DIR__ . "/../shell/",
    __DIR__ . "/../protected/",
    __DIR__ . "/../file.php",
    __DIR__ . "/../view.php",
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
  
  // $rectorConfig->rules([
    
  //   /* BASICS */
    
  //   NullToStrictStringFuncCallArgRector::class,
  //   LongArrayToShortArrayRector::class,
  //   SimplifyBoolIdenticalTrueRector::class,
  //   StringifyStrNeedlesRector::class,
    
  //   /* OPENMAGE RULES */
    
  //   CodeQuality\BooleanNot\ReplaceMultipleBooleanNotRector::class,
  //   CodeQuality\Foreach_\UnusedForeachValueToArrayKeysRector::class,
  //   CodeQuality\FuncCall\ChangeArrayPushToArrayAssignRector::class,
  //   CodeQuality\FuncCall\CompactToVariablesRector::class,
  //   CodeQuality\Identical\SimplifyArraySearchRector::class,
  //   CodeQuality\Identical\SimplifyConditionsRector::class,
  //   CodeQuality\Identical\StrlenZeroToIdenticalEmptyStringRector::class,
  //   CodeQuality\NotEqual\CommonNotEqualRector::class,
  //   CodeQuality\LogicalAnd\LogicalToBooleanRector::class,
  //   CodeQuality\Ternary\SimplifyTautologyTernaryRector::class,
  //   DeadCode\ClassMethod\RemoveUselessParamTagRector::class,
  //   DeadCode\ClassMethod\RemoveUselessReturnTagRector::class,
  //   DeadCode\Property\RemoveUselessVarTagRector::class,
  //   TypeDeclaration\ClassMethod\ReturnNeverTypeRector::class,
  // ]);
  
  // /* SETS */
  
  // $rectorConfig->sets([
    
  //   /* COMMON LISTS */
    
  //   LevelSetList::UP_TO_PHP_83,
  //   // SetList::DEAD_CODE,
  //   // SetList::CODE_QUALITY,
  //   // SetList::CODING_STYLE,
  //   // SetList::STRICT_BOOLEANS,
  //   // SetList::GMAGICK_TO_IMAGICK,
  //   // SetList::NAMING,
  //   // SetList::PRIVATIZATION,
  //   // SetList::EARLY_RETURN,
  //   // SetList::INSTANCEOF,
    
  //   // Broken:
  //   // SetList::TYPE_DECLARATION,
  // ]);

  /* SAFE RULES */

  $rectorConfig->rules([
    
    /* BASICS */
    NullToStrictStringFuncCallArgRector::class,
    LongArrayToShortArrayRector::class,
    SimplifyBoolIdenticalTrueRector::class,
    StringifyStrNeedlesRector::class,
    CombineIfRector::class,
    SimplifyIfElseToTernaryRector::class,
    RecastingRemovalRector::class,
    RenameFunctionRector::class,
    CountArrayToEmptyArrayComparisonRector::class,
    FuncCallToConstFetchRector::class,
    StrlenZeroToIdenticalEmptyStringRector::class,
    SimplifyConditionsRector::class,
    SimplifyArraySearchRector::class,
    ChangeArrayPushToArrayAssignRector::class,
    UnusedForeachValueToArrayKeysRector::class,
    Utf8DecodeEncodeToMbConvertEncodingRector::class,
    ReduceAlwaysFalseIfOrRector::class,
    RemoveDuplicatedArrayKeyRector::class,
    PostIncDecToPreIncDecRector::class,
    SimplifyDeMorganBinaryRector::class,
    
    /* RULES USED IN OPENMAGE'S RECTOR CONFIG */
    CodeQuality\BooleanNot\ReplaceMultipleBooleanNotRector::class,
    // UnusedForeachValueToArrayKeysRector::class,               // Duplicate
    // ChangeArrayPushToArrayAssignRector::class,                // Duplicate
    CodeQuality\FuncCall\CompactToVariablesRector::class,
    // SimplifyArraySearchRector::class,                         // Duplicate
    // SimplifyConditionsRector::class,                          // Duplicate
    // StrlenZeroToIdenticalEmptyStringRector::class,            // Duplicate
    CodeQuality\NotEqual\CommonNotEqualRector::class,
    CodeQuality\LogicalAnd\LogicalToBooleanRector::class,
    CodeQuality\Ternary\SimplifyTautologyTernaryRector::class,
    DeadCode\ClassMethod\RemoveUselessParamTagRector::class,
    DeadCode\ClassMethod\RemoveUselessReturnTagRector::class,
    DeadCode\Property\RemoveUselessVarTagRector::class,
    TypeDeclaration\ClassMethod\ReturnNeverTypeRector::class,
  ]);
  
  // List of sets: src/Set/ValueObject/SetList.php
  
  $rectorConfig->sets([
    
    /* OK */
    LevelSetList::UP_TO_PHP_82,
    LaravelLevelSetList::UP_TO_LARAVEL_110,
    SymfonySetList::SYMFONY_64,
    SymfonySetList::SYMFONY_71,
    
    /* OPTIONAL */
    SetList::DEAD_CODE,
    SetList::CODE_QUALITY,
    SetList::CODING_STYLE,
    SetList::STRICT_BOOLEANS,
    SetList::GMAGICK_TO_IMAGICK,
    SetList::PRIVATIZATION,
    SetList::EARLY_RETURN,
    SetList::INSTANCEOF,
    SetList::PHP_POLYFILLS,
    SetList::RECTOR_PRESET,
    
    // LaravelSetList::ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
    // LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
    // LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
    // LaravelSetList::LARAVEL_CODE_QUALITY,
    // LaravelSetList::LARAVEL_COLLECTION,
    // LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
    // LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
    // LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
    // LaravelSetList::LARAVEL_IF_HELPERS,
    // LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    // LaravelSetList::LARAVEL_STATIC_TO_INJECTION,
    
    // SymfonySetList::SYMFONY_CODE_QUALITY,
    // SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    // SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    // SymfonySetList::CONFIGS,

    /* WIP */
    // SetList::CARBON,
  ]);
};
