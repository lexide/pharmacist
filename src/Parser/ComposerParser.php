<?php
namespace Lexide\Pharmacist\Parser;

class ComposerParser
{

    /**
     * @var ComposerParserResult[]
     */
    protected $vendorComposerConfigs;

    /**
     * @param $filename
     * @return ComposerParserResult
     * @throws \Exception
     */
    public function parse($filename)
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
    protected function loadVendorComposerConfigs(ComposerParserResult $composerConfig)
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
    protected function jsonDecodeFile($filename)
    {

        if (!file_exists($filename)) {
            throw new \Exception("No composer file exists at '{$filename}'");
        }

        $array = json_decode(file_get_contents($filename), true);

        if (!$array) {
            throw new \Exception("Could not decode '{$filename}'. ".json_last_error_msg());
        }

        $result = new ComposerParserResult();
        $result->setName($array["name"]);
        $result->setNamespace($this->getNamespace($array));
        $result->setDirectory(dirname($filename));
        $result->setSyringeConfig($this->getSyringeConfig($array));
        $result->setPuzzleWhitelist($this->getPuzzleWhitelist($array));

        return $result;
    }

    protected function getNamespace($array)
    {
        return str_replace("/", "_", $array["name"]);
    }

    protected function getSyringeConfig($array)
    {
        $paths = [
            "extra",
            ["lexide/puzzle-di", "downsider-puzzle-di"],
            "!files",
            "lexide/syringe",
            "path"
        ];

        return $this->traverseConfigArray($paths, $array);
    }

    protected function getPuzzleWhitelist($array)
    {
        $paths = [
            "extra",
            ["lexide/puzzle-di", "downsider-puzzle-di"],
            "whitelist",
            "lexide/syringe"
        ];

        return $this->traverseConfigArray($paths, $array);
    }

    /**
     * @param $paths
     * @param $array
     * @return bool|null|array
     */
    protected function traverseConfigArray($paths, $array)
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
                if (!isset($array[$directory])) {
                    continue;
                }
                $newArray = $array[$directory];
                break;
            }
            if (!isset($newArray)) {
                if ($isOptional) {
                    $newArray = $array;
                } else {
                    return false;
                }
            }
            $array = $newArray;
        }

        // Will return something like "config/syringe.yml"
        return $array;
    }

    protected function generateConfigList(ComposerParserResult $libraryConfig)
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
