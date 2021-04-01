<?php
/**
 *
 */
namespace Sidpt\BinderBundle\API\Serializer;

use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;

use Claroline\CoreBundle\Entity\Resource\ResourceNode;

use Claroline\CoreBundle\API\Serializer\User\RoleSerializer;
use Claroline\CoreBundle\API\Serializer\Resource\ResourceNodeSerializer;

use Sidpt\BinderBundle\Entity\Document;
use Sidpt\BinderBundle\API\Serializer\DocumentSerializer;

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

        $slug = $resourceNode ?
                $resourceNode->getSlug() :
                $tab->getUuId();

        if (isset($options["slug_prefix"])) {
            $slug = $options["slug_prefix"]."/".$slug;
        }
        $options["slug_prefix"] = $slug;

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
            'slug' => $slug,
            'display' => [
                'visible' => $tab->isVisible(),
                'position' => $tab->getPosition() ?: 0,
                'backgroundColor' => $tab->getBackgroundColor(),
                'borderColor' => $tab->getBorderColor(),
                'textColor' => $tab->getTextColor(),
                'icon' => $tab->getIcon()
            ],
            'metadata' => [
                'details'=> $tab->getDetails(),
                'type' => $tab->getType(),
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

        $display = $data['display'];
        $this->sipe('visible', 'setVisible', $display, $tab);
        $this->sipe('position', 'setPosition', $display, $tab);
        $this->sipe('backgroundColor', 'setBackgroundColor', $display, $tab);
        $this->sipe('borderColor', 'setBorderColor', $display, $tab);
        $this->sipe('textColor', 'setTextColor', $display, $tab);
        $this->sipe('icon', 'setIcon', $display, $tab);

        $metadata = $data['metadata'];
        $this->sipe('details', 'setDetails', $metadata, $tab);
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

        // serialize tabs up to a a certain depth to avoid
        // possible infinite loop
        
        if (isset($options["depth"])) {
            $options["depth"] = $options["depth"] + 1;
        } else {
            $options["depth"] = 0;
        }
        $depth = $options["depth"];
        
        // For now i limit a single binder to a maximum of 6 computed levels
        $data = [
            'binder' => [
                'id' => $binder->getUuid(),
                'title' => $binder->getResourceNode()->getName(),
                'tabs' =>  $depth < 6 ?
                    array_map(
                        function (BinderTab $tab) use ($options) {
                                return $this->serializeTab($tab, $options);
                        },
                        $sortedTabs
                    ) : []
            ],
            'directory' => [
                'id' => $binder->getId(),
                'list' => [
                    'actions' => $binder->hasActions(),
                    'count' => $binder->hasCount(),
                    // display feature
                    'display' => $binder->getDisplay(),
                    'availableDisplays' => $binder->getAvailableDisplays(),

                    // sort feature
                    'sorting' => $binder->getSortBy(),
                    'availableSort' => $binder->getAvailableSort(),

                    // filter feature
                    'searchMode' => $binder->getSearchMode(),
                    'filters' => $binder->getFilters(),
                    'availableFilters' => $binder->getAvailableFilters(),

                    // pagination feature
                    'paginated' => $binder->isPaginated(),
                    'pageSize' => $binder->getPageSize(),
                    'availablePageSizes' => $binder->getAvailablePageSizes(),

                    // table config
                    'columns' => $binder->getDisplayedColumns(),
                    'availableColumns' => $binder->getAvailableColumns(),

                    // grid config
                    'card' => [
                        'display' => $binder->getCard(),
                        'mapping' => [], // TODO
                    ],
                ],
            ]
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
                    $binderTab = new BinderTab();
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

        $this->sipe('directory.list.count', 'setCount', $data, $binder);
        $this->sipe('directory.list.actions', 'setActions', $data, $binder);

        // display feature
        $this->sipe('directory.list.display', 'setDisplay', $data, $binder);
        $this->sipe('directory.list.availableDisplays', 'setAvailableDisplays', $data, $binder);

        // sort feature
        $this->sipe('directory.list.sorting', 'setSortBy', $data, $binder);
        $this->sipe('directory.list.availableSort', 'setAvailableSort', $data, $binder);

        // filter feature
        $this->sipe('directory.list.searchMode', 'setSearchMode', $data, $binder);
        $this->sipe('directory.list.filters', 'setFilters', $data, $binder);
        $this->sipe('directory.list.availableFilters', 'setAvailableFilters', $data, $binder);

        // pagination feature
        $this->sipe('directory.list.paginated', 'setPaginated', $data, $binder);
        $this->sipe('directory.list.pageSize', 'setPageSize', $data, $binder);
        $this->sipe('directory.list.availablePageSizes', 'setAvailablePageSizes', $data, $binder);

        // table config
        $this->sipe('directory.list.columns', 'setDisplayedColumns', $data, $binder);
        $this->sipe('directory.list.availableColumns', 'setAvailableColumns', $data, $binder);

        // grid config
        $this->sipe('directory.list.card.display', 'setCard', $data, $binder);


        return $binder;
    }
}
