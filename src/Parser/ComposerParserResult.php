<?php


namespace Lexide\Pharmacist\Parser;


class ComposerParserResult
{
    protected $name;
    protected $syringeConfig;
    protected $namespace;
    protected $directory;
    protected $puzzleWhitelist;
    protected $puzzleConfigList;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    public function setSyringeConfig($syringeConfig)
    {
        $this->syringeConfig = $syringeConfig;
    }

    /**
     * @return array
     */
    public function getPuzzleWhitelist()
    {
        return $this->puzzleWhitelist;
    }

    /**
     * @param array $puzzleWhitelist
     */
    public function setPuzzleWhitelist($puzzleWhitelist)
    {
        $this->puzzleWhitelist = $puzzleWhitelist;
    }

    public function usesSyringe()
    {
        return $this->syringeConfig !== false;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getSyringeConfig()
    {
        return $this->syringeConfig;
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function getAbsoluteSyringeConfig()
    {
        return $this->directory."/".$this->syringeConfig;
    }

    public function getPuzzleConfigList()
    {
        return $this->puzzleConfigList;
    }

    /**
     * @param array $puzzleConfigList
     */
    public function setPuzzleConfigList($puzzleConfigList)
    {
        $this->puzzleConfigList = $puzzleConfigList;
    }
}
