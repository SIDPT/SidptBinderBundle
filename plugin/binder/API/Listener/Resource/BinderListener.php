<?php
/**
 *
 */

namespace Sidpt\BinderBundle\API\Listener\Resource;

use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Event\ExportObjectEvent;
use Claroline\CoreBundle\Event\ImportObjectEvent;
use Claroline\CoreBundle\Event\Resource\CopyResourceEvent;
use Claroline\CoreBundle\Event\Resource\LoadResourceEvent;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Sidpt\BinderBundle\Entity\Binder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 *
 */
class BinderListener
{
    /**
     * [$authorization description]
     *
     * @var [type]
     */
    private $authorization;

    /**
     * [$om description]
     *
     * @var [type]
     */
    private $om;

    /**
     * [$config description]
     *
     * @var [type]
     */
    private $config;

    /**
     * [$serializer description]
     *
     * @var [type]
     */
    private $serializer;
    

    /**
     * [__construct description]
     *
     * @param AuthorizationCheckerInterface $authorization [description]
     * @param ObjectManager                 $om            [description]
     * @param PlatformConfigurationHandler  $config        [description]
     * @param SerializerProvider            $serializer    [description]
     */
    public function __construct(
        AuthorizationCheckerInterface $authorization,
        ObjectManager $om,
        PlatformConfigurationHandler $config,
        SerializerProvider $serializer
    ) {
        $this->authorization = $authorization;
        $this->om = $om;
        $this->config = $config;
        $this->serializer = $serializer;
    }

    /**
     * [onLoad description]
     *
     * @param LoadResourceEvent $event [description]
     *
     * @return [type]                   [description]
     */
    public function onLoad(LoadResourceEvent $event)
    {
        /**
         * [$document description]
         *
         * @var [type]
         */
        $binder = $event->getResource();

        $event->setData(
            [
                'binder' => $this->serializer->serialize($binder)
            ]
        );
        $event->stopPropagation();
    }
}
