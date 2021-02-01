<?php
/**
 *
 */
namespace Sidpt\BinderBundle\Serializer;

use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;

use Claroline\CoreBundle\Entity\Resource\ResourceNode;

use Claroline\CoreBundle\API\Serializer\User\RoleSerializer;
use Claroline\CoreBundle\API\Serializer\Resource\ResourceNodeSerializer;

use Sidpt\BinderBundle\Entity\Document;
use Sidpt\BinderBundle\Serializer\DocumentSerializer;

use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Entity\BinderTab;

// logging for debug
use Claroline\AppBundle\Log\LoggableTrait;
use Psr\Log\LoggerAwareInterface;

/**
 *
 */
class BinderSerializer
{
    use LoggableTrait;

    use SerializerTrait;

    /**
     * [$om description]
     *
     * @var [type]
     */
    private $om;
    
    /**
     * [$documentSerializer description]
     *
     * @var [type]
     */
    private $documentSerializer;

    /**
     * [$resourceNodeSerializer description]
     *
     * @var [type]
     */
    private $resourceNodeSerializer;


    /**
     * [$documentSerializer description]
     *
     * @var [type]
     */
    private $roleSerializer;

    /**
     * DocumentSerializer constructor.
     *
     * @param ObjectManager       $om                  desc
     * @param DocumentSerializer  $documentSerializer  desc
     * @param RoleSerializer  $documentSerializer  desc
     * 
     */
    public function __construct(
        ObjectManager $om,
        ResourceNodeSerializer $resourceNodeSerializer,
        DocumentSerializer $documentSerializer,
        RoleSerializer $roleSerializer
    ) {
        $this->om = $om;
        $this->documentSerializer = $documentSerializer;
        $this->resourceNodeSerializer = $resourceNodeSerializer;
        $this->roleSerializer = $roleSerializer;
    }

    /**
     * [getName description]
     *
     * @return [type] [description]
     */
    public function getName()
    {
        return 'binder';
    }

    /**
     * [getClass description]
     *
     * @return [type] [description]
     */
    public function getClass()
    {
        return Binder::class;
    }

    /**
     * [getSchema description]
     *
     * @return string
     */
    public function getSchema()
    {
        return '~/sidpt/binder-bundle/plugin/binder/binder.json';
    }

    /**
     * [serialize description]
     *
     * @param Document $document [description]
     * @param array    $options  [description]
     *
     * @return [type]             [description]
     */
    public function serializeTab(BinderTab $tab, array $options = []): array
    {
 
        $content = null;
        $resourceNode = null ;

        if ($tab->getType() === BinderTab::TYPE_BINDER) {
            $content = $this->serialize($tab->getBinder(), $options);
            $resourceNode = $tab->getBinder()->getResourceNode();
        } else if ($tab->getType() === BinderTab::TYPE_DOCUMENT) {
            $content = $this->documentSerializer
                ->serialize(
                    $tab->getDocument(),
                    $options
                );
            $resourceNode = $tab->getDocument()->getResourceNode();
        }

        

        $title = "Empty";
        if ($tab->getTitle()) {
            $title = $tab->getTitle();
        } else if ($resourceNode) {
            $title = $resourceNode->getName();
        }
        
        $data = [
            'id' => $tab->getUuid(),
            'title' => $title,
            'slug' => $resourceNode ?
                $resourceNode->getSlug() :
                $tab->getUuId(),
            'metadata' => [
                'position' => $tab->getPosition() ?: 0,
                'backgroundColor' => $tab->getBackgroundColor(),
                'borderColor' => $tab->getBorderColor(),
                'textColor' => $tab->getTextColor(),
                'icon' => $tab->getIcon(),
                'details'=> $tab->getDetails(),
                'type' => $tab->getType(),
                'visible' => $tab->isVisible(),
                'roles' => array_map(
                    function ($role) use ($options) {
                        return $this->roleSerializer
                            ->serialize($role, $options);
                    },
                    $tab->getRoles()->toArray()
                )
            ],
            'resourceNode' => $resourceNode ?
                $this->resourceNodeSerializer->serialize($resourceNode) :
                null,
            'content' => $content
        ];

        return $data;
    }

    /**
     * [deserialize description]
     *
     * @param array         $data     [description]
     * @param Document|null $document [description]
     * @param array         $options  [description]
     *
     * @return [type]                  [description]
     */
    public function deserializeTab(
        array $data,
        BinderTab $tab = null,
        array $options = []
    ): BinderTab {
        if (empty($tab)) {
            $tab = new BinderTab();
        }
        

        $this->sipe('title', 'setTitle', $data, $tab);


        $metadata = $data['metadata'];
        $this->sipe('position', 'setPosition', $metadata, $tab);
        $this->sipe('backgroundColor', 'setBackgroundColor', $metadata, $tab);
        $this->sipe('borderColor', 'setBorderColor', $metadata, $tab);
        $this->sipe('textColor', 'setTextColor', $metadata, $tab);
        $this->sipe('icon', 'setIcon', $metadata, $tab);
        $this->sipe('details', 'setDetails', $metadata, $tab);
        $this->sipe('visible', 'setVisible', $metadata, $tab);

        if (isset($metadata['roles'])) {
            $currentRoles = $tab->getRoles()->toArray();
            $roleIds = [];
            foreach ($metadata['roles'] as $roleData) {
                $role = $tab->getRole($roleData['id']);
                if (empty($role)) {
                    $role = $this->om->getRepository(Role::class)
                        ->findOneBy(['uuid' => $roleData['id']]);
                    $tab->addRole($role);
                }
                $roleIds[] = $role->getUuid();
            }
            foreach ($currentRoles as $currentRole) {
                if (!in_array($currentRole->getUuid(), $roleIds)) {
                    $tab->removeRole($currentRole);
                }
            }
        }
        if (isset($data['resourceNode'])) {
            $newResourceNode = $data['resourceNode'];
            $type = $newResourceNode['meta']['type'];
            $resource = $this->om->getRepository(ResourceNode::class)
                ->findOneBy(['uuid' => $newResourceNode['id']]);
            if ($type === 'sidpt_binder') {
                $binder = $this->om->getRepository(Binder::class)
                    ->findOneBy(['resourceNode' => $resource->getId()]);
                $tab->setBinder($binder);
            } else if ($type === 'sidpt_document') {
                $document = $this->om->getRepository(Document::class)
                    ->findOneBy(['resourceNode' => $resource->getId()]);
                $tab->setDocument($document);
            } else {
                $tab->removeContent();
            }
        } else {
            $tab->removeContent();
        }
        return $tab;
    }

    /**
     * [serialize description]
     *
     * @param Document $document [description]
     * @param array    $options  [description]
     *
     * @return [type]             [description]
     */
    public function serialize(Binder $binder, array $options = []): array
    {
        $sortedTabs = $binder->getBinderTabs()->toArray();
        usort(
            $sortedTabs,
            function (BinderTab $a, BinderTab $b) {
                return $a->getPosition() <=> $b->getPosition();
            }
        );

        $data = [
            'id' => $binder->getUuid(),
            'title' => $binder->getResourceNode()->getName(),
            'tabs' => array_map(
                function (BinderTab $tab) use ($options) {
                    return $this->serializeTab($tab, $options);
                },
                $sortedTabs

            )
        ];

        return $data;
    }

    /**
     * [deserialize description]
     *
     * @param array         $data     [description]
     * @param Document|null $document [description]
     * @param array         $options  [description]
     *
     * @return [type]                  [description]
     */
    public function deserialize(
        array $data,
        Binder $binder = null,
        array $options = []
    ): Binder {

        

        if (empty($binder)) {
            $binder = new Binder();
        }
        
        if (isset($data['tabs'])) {
            $currentTabs = $binder->getBinderTabs()->toArray();
            $tabsIds = [];

            // update containers
            foreach ($data['tabs'] as $position => $tabsData) {
                $binderTab = isset($tabsData['id']) ?
                    $binder->getBinderTab($tabsData['id']) :
                    null;

                if (empty($binderTab)) {
                    $binderTab = new binderTab();
                    $binderTab->setOwner($binder);
                    $binder->addBinderTab($binderTab);
                }
                $this->deserializeTab(
                    $tabsData,
                    $binderTab,
                    $options
                );
                $binderTab->setPosition($position);
                $tabsIds[] = $binderTab->getId();
            }

            // removes containers which no longer exists
            foreach ($currentTabs as $currentTab) {
                if (!in_array($currentTab->getId(), $tabsIds)) {
                    $binder->removeBinderTab($currentTab);
                    $this->om->remove($currentTab);
                }
            }
        }
        return $binder;
    }
}
