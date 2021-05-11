<?php

namespace Lexide\Pharmacist\Parser;

class ComposerParser
{

    /**
     * @var ComposerParserResult[]
     */
    protected $vendorComposerConfigs;

    /**
     * @param string $filename
     * @return ComposerParserResult
     * @throws \Exception
     */
    public function parse(string $filename): ComposerParserResult
    {
        if (!file_exists($filename)) {
            throw new \Exception("No composer file exists at '{$filename}'");
        }

        $libraryConfig = $this->jsonDecodeFile($filename);

        $this->loadVendorComposerConfigs($libraryConfig);

        $libraryConfig->setPuzzleConfigList($this->generateConfigList($libraryConfig));

        return $libraryConfig;
    }

    /**
     * @param ComposerParserResult $composerConfig
     * @throws \Exception
     */
    protected function loadVendorComposerConfigs(ComposerParserResult $composerConfig): void
    {
        $this->vendorComposerConfigs = [];

        $composerFiles = glob($composerConfig->getDirectory() . "/vendor/*/*/composer.json");

        foreach ($composerFiles as $filename) {
            $config = $this->jsonDecodeFile($filename);

            $this->vendorComposerConfigs[$config->getName()] = $config;
        }
    }

    /**
     * @param $filename
     * @return ComposerParserResult
     * @throws \Exception
     */
    protected function jsonDecodeFile(string $filename): ComposerParserResult
    {

        if (!file_exists($filename)) {
            throw new \Exception("No composer file exists at '{$filename}'");
        }

        $composerData = json_decode(file_get_contents($filename), true);

        if (!$composerData) {
            throw new \Exception("Could not decode '{$filename}'. ".json_last_error_msg());
        }

        $result = new ComposerParserResult();
        $result->setName($composerData["name"]);
        $result->setNamespace($this->getNamespace($composerData));
        $result->setDirectory(dirname($filename));
        $result->setSyringeConfig($this->getSyringeConfig($composerData));
        $result->setPuzzleWhitelist($this->getPuzzleWhitelist($composerData));

        return $result;
    }

    /**
     * @param array $composerData
     * @return string
     */
    protected function getNamespace(array $composerData): string
    {
        return str_replace("/", "_", $composerData["name"]);
    }

    /**
     * @param array $composerData
     * @return string
     */
    protected function getSyringeConfig(array $composerData): string
    {
        $paths = [
            "extra",
            ["lexide/puzzle-di", "downsider-puzzle-di"],
            "!files",
            "lexide/syringe",
            "path"
        ];

        return $this->traverseConfigArray($paths, $composerData) ?: "";
    }

    /**
     * @param array $composerData
     * @return array
     */
    protected function getPuzzleWhitelist(array $composerData): array
    {
        $paths = [
            "extra",
            ["lexide/puzzle-di", "downsider-puzzle-di"],
            "whitelist",
            "lexide/syringe"
        ];

        return $this->traverseConfigArray($paths, $composerData);
    }

    /**
     * @param array $paths
     * @param array $composerData
     * @return array|string
     */
    protected function traverseConfigArray(array $paths, array $composerData): array|string
    {
        foreach ($paths as $directories) {
            if (!is_array($directories)) {
                $directories = [$directories];
            }
            $newArray = null;
            $isOptional = false;
            foreach ($directories as $directory) {

                $isOptional = false;
                if ($directory[0] == "!") {
                    $directory = substr($directory, 1);
                    $isOptional = true;
                }

                if (!isset($composerData[$directory])) {
                    continue;
                }
                $newArray = $composerData[$directory];
                break;
            }
            if (!isset($newArray)) {
                if ($isOptional) {
                    $newArray = $composerData;
                } else {
                    return [];
                }
            }
            $composerData = $newArray;
        }

        return $composerData;
    }

    /**
     * @param ComposerParserResult $libraryConfig
     * @return array
     */
    protected function generateConfigList(ComposerParserResult $libraryConfig): array
    {
        $list = [];
        foreach ($libraryConfig->getPuzzleWhitelist() as $repoName) {

            if (empty($this->vendorComposerConfigs[$repoName])) {
                continue;
            }
            $whitelistedConfig = $this->vendorComposerConfigs[$repoName];

            $list[$whitelistedConfig->getNamespace()] = $whitelistedConfig->getAbsoluteSyringeConfig();
            $list = array_replace($list, $this->generateConfigList($whitelistedConfig));

        }
        return $list;
    }
}
