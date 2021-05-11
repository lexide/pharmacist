<?php

namespace Lexide\Pharmacist;

use Lexide\Syringe\ContainerBuilder;
use Lexide\Syringe\Loader\JsonLoader;
use Lexide\Syringe\Loader\YamlLoader;
use Lexide\Syringe\ReferenceResolver;
use Lexide\Pharmacist\Parser\ComposerParser;
use Lexide\Pharmacist\Parser\ComposerParserResult;
use Pimple\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VerifyCommand extends Command
{
    /**
     * @var ComposerParser
     */
    protected $composerParser;

    /**
     * @param ComposerParser $composerParser
     */
    public function __construct(ComposerParser $composerParser)
    {
        parent::__construct();
        $this->composerParser = $composerParser;
    }

    public function configure(): void
    {
        // By setting the name as list, it's the default thing that will be run
        $this->setName("verify")
            ->addOption("configs", "c", InputOption::VALUE_IS_ARRAY + InputOption::VALUE_REQUIRED, "Any additional configs we want to add manually", [])
            ->addOption("force", "f", InputOption::VALUE_NONE, "Whether we want to force through trying it regardless of whether it looks like we're using Puzzle-DI in the parent project")
            ->addOption("allowStubs", "s", InputOption::VALUE_NONE, "If set, will not complain about stubbed services.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Work out what directory we're caring about
        $directory = getcwd();

        // 2. Work out what base config we're meant to be using
        $parserResult = $this->composerParser->parse($directory."/composer.json");

        if (!$parserResult->usesSyringe() && !$input->getOption("force")) {
            $output->writeln("<error>The project in this directory '{$directory}' is not a library includable by syringe via Puzzle-DI</error>");
            return 1;
        }

        $allowStubs = $input->getOption("allowStubs");
        $additionalConfigs = $input->getOption("configs");

        $container = $this->setupContainer($parserResult, $allowStubs, $additionalConfigs);

        $output->writeln("<comment>Attempting to build all services!</comment>");
        $output->writeln(count($container->keys())." services/parameters found!");
        /** @var \Exception[] $exceptions */
        $exceptions = [];
        foreach ($container->keys() as $key) {
            try{
                $build = $container[$key];
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }

        unset($build, $container);

        if (count($exceptions) > 0) {
            $output->writeln("<error>Failed to successfully build ".count($exceptions)." bits of DI test</error>");
            foreach ($exceptions as $e) {
                $output->writeln("  Message:".$e->getMessage().". File: ".$e->getFile().". Line: ".$e->getLine());
            }
            return 1;
        } else {
            $output->writeln("<info>Succeeded!</info>");
            return 0;
        }
    }

    /**
     * @param ComposerParserResult $parserResult
     * @param bool $allowStubs
     * @param array $additionalConfigs
     * @return Container
     */
    public function setupContainer(ComposerParserResult $parserResult, bool $allowStubs, array $additionalConfigs): Container
    {
        $directory = $parserResult->getDirectory();

        $resolver = new ReferenceResolver();
        $loaders = [
            new JsonLoader(),
            new YamlLoader()
        ];

        include($directory."/vendor/autoload.php");

        $serviceFactoryClass = $allowStubs? ServiceFactory::class: \Lexide\Syringe\ServiceFactory::class;

        $builder = new ContainerBuilder($resolver, [$directory], $serviceFactoryClass);
        foreach ($loaders as $loader) {
            $builder->addLoader($loader);
        }

        $builder->setApplicationRootDirectory($directory);

        // add vendor test files
        $builder->addConfigFiles($parserResult->getPuzzleConfigList());

        // add application test files
        if ($parserResult->usesSyringe()) {
            $builder->addConfigFile($parserResult->getAbsoluteSyringeConfig());

            // This is a hack regarding the somewhat naff way Namespaces can end up working
            $builder->addConfigFiles([
                $parserResult->getNamespace() => $parserResult->getAbsoluteSyringeConfig()
            ]);
        }

        foreach ($additionalConfigs as $config) {
            $builder->addConfigFile(realpath($config));
        }

        return $builder->createContainer();

    }
}
