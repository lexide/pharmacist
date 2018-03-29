<?php


namespace Lexide\Pharmacist\Parser;


class ComposerParserResult
{
    protected $name;
    protected $syringeConfig;
    protected $namespace;
    protected $directory;

    /**
     * @var ComposerParserResult[]
     */
    protected $children;

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

    public function setChildren($children)
    {
        $this->children = $children;
    }

    public function setSyringeConfig($syringeConfig)
    {
        $this->syringeConfig = $syringeConfig;
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

    public function getConfigList()
    {
        $configList = [];
        foreach ($this->children as $parser) {
            $configList[$parser->getNamespace()] = $parser->getAbsoluteSyringeConfig();
        }
        return $configList;
    }
}
