<?php

namespace Lexide\Pharmacist;

use Lexide\Syringe\ServiceFactory as BaseFactory;

class ServiceFactory extends BaseFactory
{

    public function createStub($key, $definition)
    {
        // if this stub's definition includes a class, use a mock of that class as the service
        if (!empty($definition["class"]) && class_exists($definition["class"])) {
            return \Mockery::mock($definition["class"]);
        }
        return parent::createStub($key, $definition);
    }

}
