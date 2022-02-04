<?php

namespace Sidpt\BinderBundle;

use Claroline\KernelBundle\Bundle\ExternalPluginBundle;

use Sidpt\BinderBundle\Installation\AdditionalInstaller;
use Claroline\InstallationBundle\Additional\AdditionalInstallerInterface;

class SidptBinderBundle extends ExternalPluginBundle
{
    public function getAdditionalInstaller(): ?AdditionalInstallerInterface
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
