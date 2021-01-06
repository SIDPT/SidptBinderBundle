<?php

namespace Sidpt\BinderBundle;

use Claroline\CoreBundle\Library\DistributionPluginBundle;
use Sidpt\BinderBundle\Installation\AdditionalInstaller;

class SidptBinderBundle extends DistributionPluginBundle
{
    public function getAdditionalInstaller()
    {
        return new AdditionalInstaller();
    }
    
}
