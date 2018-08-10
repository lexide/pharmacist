<?php
namespace Lexide\Pharmacist\Parser;

class ComposerParser
{
    public function parse($filename, array $whitelist = [], $projectDirectory = null)
    {
        if (!file_exists($filename)) {
            throw new \Exception("No composer file exists at '{$filename}'");
        }

        if (empty($projectDirectory)) {
            $projectDirectory = dirname($filename);
        }

        $array = json_decode(file_get_contents($filename), true);

        if (!$array) {
            throw new \Exception("Could not decode '{$filename}'. ".json_last_error_msg());
        }

        $whitelist = array_replace($whitelist, $this->getSyringeWhitelist($array) ?: []);

        $result = new ComposerParserResult();
        $result->setName($array["name"]);
        $result->setNamespace($this->getNamespace($array));
        $result->setDirectory(dirname($filename));
        $result->setSyringeConfig($this->getSyringeConfig($array));
        $result->setChildren($this->getPuzzleChildren($projectDirectory, $whitelist));
        return $result;
    }

    protected function getPuzzleChildren($projectDirectory, $whitelist)
    {
        $composerFiles = glob($projectDirectory."/vendor/*/*/composer.json");
        $children = [];
        foreach ($composerFiles as $filename) {
            $parsedComposer = $this->parse($filename, $whitelist, $projectDirectory);
            if ($parsedComposer->usesSyringe() && in_array($parsedComposer->getName(), $whitelist)) {
                $children[] = $parsedComposer;
            }
        }
        return $children;
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

    protected function getSyringeWhitelist($array)
    {
        $paths = [
            "extra",
            ["lexide/puzzle-di", "downsider-puzzle-di"],
            "whitelist",
            "lexide/syringe"
        ];

        return $this->traverseConfigArray($paths, $array);
    }

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
}
