<?php

namespace Sidpt\BinderBundle\API\Listener\Resource;

use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\Options;

use Claroline\CoreBundle\Event\ExportObjectEvent;
use Claroline\CoreBundle\Event\ImportObjectEvent;
use Claroline\CoreBundle\Event\Resource\CopyResourceEvent;
use Claroline\CoreBundle\Event\Resource\LoadResourceEvent;
use Claroline\CoreBundle\Event\Resource\DeleteResourceEvent;
use Claroline\CoreBundle\Event\Resource\ResourceActionEvent;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Sidpt\BinderBundle\Entity\Binder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;

use Claroline\CoreBundle\API\Serializer\ParametersSerializer;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Claroline\CoreBundle\Entity\Resource\Directory;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Manager\Resource\ResourceActionManager;
use Claroline\CoreBundle\Manager\Resource\RightsManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Security\Collection\ResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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

    private $crud;

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

    public function onLoad(LoadResourceEvent $event)
    {
        /** @var Binder $document */
        $document = $event->getResource();
        $documentData = $this->serializer->serialize($document);
        $event->setData($documentData);
        $event->stopPropagation();
    }

    public function onCopy(CopyResourceEvent $event)
    {
        /** @var Binder $document */
        $document = $event->getResource();
        $documentCopy = $event->getCopy();

        // Get the document node
        $node = $document->getResourceNode();
        $nodeCopy = $documentCopy->getResourceNode();
        
        // For each child of the node
        foreach ($node->getChildren() as $child) {
            //  Create a copy of the node
            $user = $child->getCreator();
            // Create a node copy
            // note : according the resource node crud,
            //      a copy of the resource is also created
            $newNode = $this->crud->copy(
                $child,
                [Options::IGNORE_RIGHTS, Crud::NO_PERMISSIONS],
                ['user' => $user, 'parent' => $nodeCopy]
            );
            $this->om->persist($newNode);
            //  if the document copy has a widget pointing to the node,
            //  Change the pointer
        }

        $event->setData($this->serializer->serialize($documentCopy));
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
