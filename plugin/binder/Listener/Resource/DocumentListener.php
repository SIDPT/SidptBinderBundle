<?php

namespace Sidpt\BinderBundle\Listener\Resource;

use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Event\ExportObjectEvent;
use Claroline\CoreBundle\Event\ImportObjectEvent;
use Claroline\CoreBundle\Event\Resource\CopyResourceEvent;
use Claroline\CoreBundle\Event\Resource\LoadResourceEvent;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Sidpt\BinderBundle\Entity\Binder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DocumentListener
{
    /** @var AuthorizationCheckerInterface */
    private $authorization;
    /* @var ObjectManager */
    private $om;
    /** @var PlatformConfigurationHandler */
    private $config;
    /** @var SerializerProvider */
    private $serializer;


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

    public function onLoad(LoadResourceEvent $event)
    {
        /** @var Binder $document */
        $document = $event->getResource();

        $event->setData([
            'clarodoc' => $this->serializer->serialize($document)
        ]);
        $event->stopPropagation();
    }

//    public function onCopy(CopyResourceEvent $event)
//    {
//        /** @var Binder $document */
//        $document = $event->getResource();
//
//        // copy the document only (not the widgets)
//    }
}
