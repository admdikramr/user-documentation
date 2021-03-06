<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace HHVM\UserDocumentation;

use Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\Vec;

final class RawYAMLBuildStep extends BuildStep {
  <<__Override>>
  public function buildAll(): void {
    $exts = Set { 'php', 'hhi' };

    Log::i("\nRawYAMLBuild");

    // Builtins
    $sources = (Vector { })
      ->addAll(self::findSources(BuildPaths::HHVM_TREE.'/hphp/system/php/', $exts))
      ->addAll(self::findSources(BuildPaths::HHVM_TREE.'/hphp/runtime/ext/', $exts));
    $this->buildSources(BuildPaths::SYSTEMLIB_YAML, $sources);
    $this->buildSources(
      BuildPaths::HHI_YAML,
      self::findSources(BuildPaths::HHVM_TREE.'/hphp/hack/hhi/', $exts),
    );

    // Extra libraries
    $this->buildSources(
      BuildPaths::APIDOCS_DATA.'/'.APIProduct::HSL,
      self::findSources(BuildPaths::HSL_TREE.'/src/', $exts),
    );
  }

  private function buildSources(
    string $output_dir,
    Traversable<string> $sources,
  ): void {
    if (!\is_dir($output_dir)) {
      \mkdir($output_dir, /* mode = */ 0755, /* recursive = */ true);
    }

    Log::i("\nBuild sources for %s", $output_dir);

    $files = Vec\map($sources, $filename ==> {
      Log::v(".");
      $source = shape(
        'type' => DocumentationSourceType::FILE,
        'name' => $filename,
        'mtime' => \stat($filename)['mtime'],
      );
      $bytes = \file_get_contents($filename);
      $parser = FileParser::FromData($bytes, $filename);
      return (new ScannedDefinitionsYAMLBuilder($source, $parser, $output_dir))
        ->addFilter($x ==> ScannedDefinitionFilters::IsHHSpecific($x))
        ->addFilter($x ==> !ScannedDefinitionFilters::ShouldNotDocument($x))
        ->build();
    }) |> Vec\flatten($$);
    \file_put_contents(
      $output_dir.'/index.json',
      JSON\encode_shape(DirectoryIndex::class, shape('files' => $files)),
    );
  }
}
