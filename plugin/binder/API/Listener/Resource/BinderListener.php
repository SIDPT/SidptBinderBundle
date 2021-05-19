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
    /** @var ResourceManager */
    private $resourceManager;
    /** @var ResourceActionManager */
    private $actionManager;
    /** @var RightsManager */
    private $rightsManager;
    

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
        ResourceManager $resourceManager,
        ResourceActionManager $actionManager,
        RightsManager $rightsManager
    ) {
        $this->authorization = $authorization;
        $this->om = $om;
        $this->config = $config;
        $this->serializer = $serializer;

        $this->crud = $crud;
        $this->resourceManager = $resourceManager;
        $this->rightsManager = $rightsManager;
        $this->actionManager = $actionManager;
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
     * From the directory listener : add a resource in a binder
     */
    public function onAdd(ResourceActionEvent $event)
    {
        $data = $event->getData();
        $parent = $event->getResourceNode();

        $add = $this->actionManager->get($parent, 'add');

        // checks if the current user can add
        $collection = new ResourceCollection([$parent], ['type' => $data['resourceNode']['meta']['type']]);
        if (!$this->actionManager->hasPermission($add, $collection)) {
            throw new AccessDeniedException($collection->getErrorsForDisplay());
        }

        $options = $event->getOptions();

        // create the resource node

        /** @var ResourceNode $resourceNode */
        $resourceNode = $this->crud->create(ResourceNode::class, $data['resourceNode'], $options);
        $resourceNode->setParent($parent);
        $resourceNode->setWorkspace($parent->getWorkspace());

        // initialize custom resource Entity
        $resourceClass = $resourceNode->getResourceType()->getClass();

        /** @var AbstractResource $resource */
        $resource = $this->crud->create($resourceClass, !empty($data['resource']) ? $data['resource'] : [], $options);
        $resource->setResourceNode($resourceNode);

        // maybe do it in the serializer (if it can be done without intermediate flush)
        if (!empty($data['resourceNode']['rights'])) {
            foreach ($data['resourceNode']['rights'] as $rights) {
                /** @var Role $role */
                $role = $this->om->getRepository(Role::class)->findOneBy(['name' => $rights['name']]);

                $creation = [];
                if (!empty($rights['permissions']['create']) && $resource instanceof Directory) {
                    // only forward creation rights to resource which can handle it (only directories atm)
                    $creation = $rights['permissions']['create'];
                }
                $this->rightsManager->editPerms($rights['permissions'], $role, $resourceNode, false, $creation);
            }
        } else {
            // todo : initialize default rights
        }

        $this->om->persist($resource);
        $this->om->persist($resourceNode);

        $this->om->flush();

        // todo : dispatch get/load action instead
        $event->setResponse(
            new JsonResponse(
                [
                    'resourceNode' => $this->serializer->serialize($resourceNode),
                    'resource' => $this->serializer->serialize($resource),
                ],
                201
            )
        );
    }
}
