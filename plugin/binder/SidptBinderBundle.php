<?php

namespace Sidpt\BinderBundle;

use Claroline\KernelBundle\Bundle\ExternalPluginBundle;

use Sidpt\BinderBundle\Installation\AdditionalInstaller;

class SidptBinderBundle extends ExternalPluginBundle
{
    public function getAdditionalInstaller()
    {
        return new AdditionalInstaller();
    }

    // public function getPostInstallFixturesDirectory($environment)
    // {
    //     return 'DataFixtures/PostInstall';
    // }

    public function getRequiredPlugins()
    {
        return [
            'Sidpt\\VersioningBundle\\SidptVersioningBundle',
        ];
    }
}
