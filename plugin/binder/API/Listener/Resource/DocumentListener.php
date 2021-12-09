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

use Claroline\CoreBundle\Listener\Resource\Types\DirectoryListener;

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
        DirectoryListener $directoryListener
    ) {
        $this->authorization = $authorization;
        $this->om = $om;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->crud = $crud;
        $this->directoryListener = $directoryListener;
    }

    public function onLoad(LoadResourceEvent $event)
    {
        /** @var Binder $document */
        $document = $event->getResource();
        $documentData['raw'] = $this->serializer->serialize($document);
        $documentData['translated'] = $this->serializer->serialize($document, ['translated']);
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
     * Adds a new resource inside a directory.
     */
    public function onAdd(ResourceActionEvent $event)
    {
        $this->directoryListener->onAdd($event);
    }


}
