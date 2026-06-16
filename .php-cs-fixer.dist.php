<?php

/**
 * @see https://mlocati.github.io/php-cs-fixer-configurator/
 */

declare(strict_types=1);

use GD75\DoubleQuoteFixer\DoubleQuoteFixer;
use Chefstore\Util\Fixer\BlankLineAfterClassOpeningFixer;
use Chefstore\Util\Fixer\BlankLineIndentationFixer;

$finder = PhpCsFixer\Finder::create()
  ->in([__DIR__."/"])
  ->exclude([
    "app/code/core/",
    "app/design/adminhtml/base/",
    "app/design/adminhtml/default/default/",
    "app/design/frontend/base/",
    "app/design/frontend/rwd/default/",
    "node_modules/",
    "vendor/",
  ])
  ->name(["*.php", "*.phtml"]);

/** @var PhpCsFixer\Config $config */
$config = (new PhpCsFixer\Config())
  ->registerCustomFixers([new DoubleQuoteFixer(), new BlankLineAfterClassOpeningFixer(), new BlankLineIndentationFixer()])
  ->setCacheFile(sys_get_temp_dir()."/.php-cs-fixer-intel.cache")
  ->setFinder($finder)
  ->setIndent("  ")
  ->setLineEnding("\n")
  ->setRiskyAllowed(true)
  ->setRules([
    "@PSR2"                                       => false,
    "@PSR12"                                      => false,
    "GD75/double_quote_fixer"                     => true,
    "align_multiline_comment"                     => ["comment_type" => "phpdocs_like"],
    "array_indentation"                           => true,
    "array_syntax"                                => ["syntax" => "short"],
    "assign_null_coalescing_to_coalesce_equal"    => true,
    "binary_operator_spaces"                      => [
      "default"   => "align_single_space",
      "operators" => [
        "=>"  => "align_single_space",
        "="   => "align_single_space",
        "=="  => "align_single_space",
        "===" => "align_single_space",
      ],
    ],
    "blank_line_after_namespace"                  => true,
    "blank_line_after_opening_tag"                => true,
    "blank_lines_before_namespace"                => true,
    "braces"                                      => false,
    "Chefstore/blank_line_after_class_opening"    => true,
    "Chefstore/blank_line_indentation"            => true,
    "braces_position"                             => [
      "allow_single_line_anonymous_functions"     => true,
      "allow_single_line_empty_anonymous_classes" => true,
      "anonymous_classes_opening_brace"           => "same_line",
      "anonymous_functions_opening_brace"         => "same_line",
      "classes_opening_brace"                     => "same_line",
      "control_structures_opening_brace"          => "same_line",
      "functions_opening_brace"                   => "same_line",
    ],
    "cast_spaces"                                 => true,
    "class_attributes_separation"                 => [
      "elements" => [
        "const"        => "one",
        "method"       => "one",
        "property"     => "one",
        "trait_import" => "none",
        "case"         => "none",
      ],
    ],
    "class_definition"                             => true,
    "class_reference_name_casing"                  => true,
    "clean_namespace"                              => true,
    "combine_consecutive_issets"                   => true,
    "combine_consecutive_unsets"                   => true,
    "compact_nullable_type_declaration"            => true,
    "concat_space"                                 => ["spacing" => "none"],
    "constant_case"                                => true,
    "control_structure_braces"                     => true,
    "control_structure_continuation_position"      => ["position" => "same_line"],
    "declare_equal_normalize"                      => true,
    "declare_parentheses"                          => true,
    "elseif"                                       => true,
    "encoding"                                     => true,
    "full_opening_tag"                             => true,
    "fully_qualified_strict_types"                 => true,
    "function_declaration"                         => false,
    "general_phpdoc_tag_rename"                    => true,
    "global_namespace_import"                      => false,
    "heredoc_to_nowdoc"                            => true,
    "include"                                      => true,
    "increment_style"                              => ["style" => "post"],
    "indentation_type"                             => true,
    "is_null"                                      => true,
    "line_ending"                                  => true,
    "linebreak_after_opening_tag"                  => true,
    "list_syntax"                                  => true,
    "lowercase_cast"                               => true,
    "lowercase_keywords"                           => true,
    "lowercase_static_reference"                   => true,
    "magic_constant_casing"                        => true,
    "magic_method_casing"                          => true,
    "method_argument_space"                        => true,
    "modernize_strpos"                             => true,
    "modernize_types_casting"                      => true,
    "modifier_keywords"                            => ["elements" => ["method", "property"]],
    "multiline_whitespace_before_semicolons"       => ["strategy" => "no_multi_line"],
    "native_function_casing"                       => true,
    "no_blank_lines_after_phpdoc"                  => true,
    "no_closing_tag"                               => true,
    "no_empty_phpdoc"                              => true,
    "no_empty_statement"                           => true,
    "no_leading_import_slash"                      => false,
    "no_leading_namespace_whitespace"              => true,
    "no_mixed_echo_print"                          => ["use" => "echo"],
    "no_multiline_whitespace_around_double_arrow"  => true,
    "no_short_bool_cast"                           => true,
    "no_singleline_whitespace_before_semicolons"   => true,
    "no_spaces_after_function_name"                => true,
    "no_spaces_around_offset"                      => true,
    "no_trailing_comma_in_singleline"              => true,
    "no_trailing_whitespace"                       => true,
    "no_trailing_whitespace_in_comment"            => true,
    "no_unneeded_control_parentheses"              => true,
    "no_unused_imports"                            => true,
    "no_useless_return"                            => true,
    "no_whitespace_before_comma_in_array"          => true,
    "no_whitespace_in_blank_line"                  => false,
    "normalize_index_brace"                        => true,
    "not_operator_with_successor_space"            => false,
    "object_operator_without_whitespace"           => true,
    "ordered_imports"                              => false,
    "phpdoc_indent"                                => true,
    "phpdoc_inline_tag_normalizer"                 => true,
    "phpdoc_no_access"                             => true,
    "phpdoc_no_package"                            => true,
    "phpdoc_no_useless_inheritdoc"                 => true,
    "phpdoc_scalar"                                => true,
    "phpdoc_single_line_var_spacing"               => false,
    "single_space_around_construct"                => [
      "constructs_contain_a_single_space"     => ["yield_from"],
      "constructs_followed_by_a_single_space" => [
        "abstract", "as", "attribute", "break", "case", "catch", "class", "clone", "comment", "const", "const_import", "continue",
        "echo", "enum", "extends", "final", "function_import", "global", "goto", "implements", "include",
        "include_once", "instanceof", "insteadof", "interface", "named_argument", "namespace", "new", "open_tag_with_echo",
        "php_doc", "php_open", "print", "private", "private_set", "protected", "protected_set", "public", "public_set",
        "readonly", "require", "require_once", "return", "static", "throw", "trait", "try", "type_colon", "use",
        "use_lambda", "use_trait", "var", "while", "yield", "yield_from",
      ],
      "constructs_preceded_by_a_single_space" => ["as", "use_lambda"],
    ],
    "phpdoc_tag_type"                             => true,
    "phpdoc_to_comment"                           => false,
    "phpdoc_trim"                                 => true,
    "phpdoc_types"                                => true,
    "phpdoc_var_without_name"                     => true,
    "short_scalar_cast"                           => true,
    "simple_to_complex_string_variable"           => true,
    "simplified_if_return"                        => true,
    "simplified_null_return"                      => false,
    "single_blank_line_at_eof"                    => true,
    "single_class_element_per_statement"          => true,
    "single_import_per_statement"                 => false,
    "single_line_after_imports"                   => true,
    "single_line_comment_style"                   => ["comment_types" => ["hash"]],
    "spaces_inside_parentheses"                   => ["space" => "none"],
    "standardize_not_equals"                      => true,
    "switch_case_semicolon_to_colon"              => true,
    "switch_case_space"                           => true,
    "ternary_operator_spaces"                     => true,
    "trailing_comma_in_multiline"                 => true,
    "trim_array_spaces"                           => true,
    "type_declaration_spaces"                     => true,
    "unary_operator_spaces"                       => true,
    "whitespace_after_comma_in_array"             => true,
  ]);

if(method_exists($config, "setParallelConfig")) {
  $config->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());
}

return $config->setUnsupportedPhpVersionAllowed(true);
