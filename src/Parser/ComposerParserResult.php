<?php

namespace Lexide\Pharmacist\Parser;

class ComposerParserResult
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $syringeConfig;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $directory;

    /**
     * @var array
     */
    protected $puzzleWhitelist;

    /**
     * @var array
     */
    protected $puzzleConfigList;

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function setDirectory(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param string $syringeConfig
     */
    public function setSyringeConfig(string $syringeConfig)
    {
        $this->syringeConfig = $syringeConfig;
    }

    /**
     * @return array
     */
    public function getPuzzleWhitelist(): array
    {
        return $this->puzzleWhitelist;
    }

    /**
     * @param array $puzzleWhitelist
     */
    public function setPuzzleWhitelist(array $puzzleWhitelist): void
    {
        $this->puzzleWhitelist = $puzzleWhitelist;
    }

    /**
     * @return bool
     */
    public function usesSyringe(): bool
    {
        return !empty($this->syringeConfig);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getSyringeConfig(): string
    {
        return $this->syringeConfig;
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getAbsoluteSyringeConfig(): string
    {
        return $this->directory."/".$this->syringeConfig;
    }

    /**
     * @return array
     */
    public function getPuzzleConfigList(): array
    {
        return $this->puzzleConfigList;
    }

    /**
     * @param array $puzzleConfigList
     */
    public function setPuzzleConfigList(array $puzzleConfigList): void
    {
        $this->puzzleConfigList = $puzzleConfigList;
    }
}
