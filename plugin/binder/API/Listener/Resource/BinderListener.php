<?php
/**
 *
 */

namespace Sidpt\BinderBundle\API\Listener\Resource;

use Claroline\CoreBundle\Event\ExportObjectEvent;
use Claroline\CoreBundle\Event\ImportObjectEvent;
use Claroline\CoreBundle\Event\Resource\CopyResourceEvent;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Entity\BinderTab;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\API\Serializer\ParametersSerializer;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Claroline\CoreBundle\Entity\Resource\Directory;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Event\Resource\LoadResourceEvent;
use Claroline\CoreBundle\Event\Resource\ResourceActionEvent;
use Claroline\CoreBundle\Manager\Resource\ResourceActionManager;
use Claroline\CoreBundle\Manager\Resource\RightsManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Security\Collection\ResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Claroline\CoreBundle\Listener\Resource\Types\DirectoryListener;

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

    /** @var Crud */
    private $crud;

    private $directoryListener;
    

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
        SerializerProvider $serializer,
        Crud $crud,
        DirectoryListener $directoryListener
    ) {
        $this->authorization = $authorization;
        $this->om = $om;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->crud = $crud;
        $this->directoryListener = $directoryListener;
    }

    /**
     * RESOURCE_LOAD redux action callback
     * TODO : remove content from the tab tree
     * instead :
     * Build the tab tree using the binder serialization
     * 
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
        $binderData = $this->serializer->serialize($binder);

        $sortedTabs = $binder->getBinderTabs()->toArray();
        usort(
            $sortedTabs,
            function (BinderTab $a, BinderTab $b) {
                return $a->getPosition() <=> $b->getPosition();
            }
        );
        $content = null;
        if (count($sortedTabs) > 0) {
            if ($sortedTabs[0]->getType() == BinderTab::TYPE_DOCUMENT) {
                $content = $this->serializer->serialize($sortedTabs[0]->getDocument());
                $resourceNode = $sortedTabs[0]->getDocument()->getResourceNode();
                $slug = $resourceNode ?
                    $resourceNode->getSlug() :
                    $sortedTabs[0]->getUuId();
                $content['slug'] = $slug;
            }
        }
        $binderData['displayedDocument'] = $content;

        $event->setData($binderData);
        $event->stopPropagation();
    }

    /**
     * Adds a new resource inside a directory.
     */
    public function onAdd(ResourceActionEvent $event)
    {
        $this->directoryListener->onAdd($event);
    }
}
