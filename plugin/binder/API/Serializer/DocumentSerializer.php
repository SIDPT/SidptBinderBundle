<?php
/**
 *
 */
namespace Sidpt\BinderBundle\API\Serializer;

use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;

use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\API\Serializer\Widget\WidgetContainerSerializer;

use Sidpt\BinderBundle\Entity\Document;

// logging for debug
use Claroline\AppBundle\Log\LoggableTrait;
use Psr\Log\LoggerAwareInterface;

/**
 *
 */
class DocumentSerializer
{
    //use LoggableTrait;

    use SerializerTrait;

    /**
     * [$om description]
     *
     * @var [type]
     */
    private $om;
    
    /**
     * [$widgetContainerSerializer description]
     *
     * @var [type]
     */
    private $widgetContainerSerializer;

    /**
     * DocumentSerializer constructor.
     *
     * @param ObjectManager             $om                        desc
     * @param WidgetContainerSerializer $widgetContainerSerializer desc
     */
    public function __construct(
        ObjectManager $om,
        WidgetContainerSerializer $widgetContainerSerializer
    ) {
        $this->om = $om;
        $this->widgetContainerSerializer = $widgetContainerSerializer;
    }

    /**
     * [getName description]
     *
     * @return [type] [description]
     */
    public function getName()
    {
        return 'clarodoc';
        // or document, not sure if it is the resource codename in javascript
        // or the php classname that is needed
    }

    /**
     * [getClass description]
     *
     * @return [type] [description]
     */
    public function getClass()
    {
        return Document::class;
    }

    /**
     * [getSchema description]
     *
     * @return string
     */
    public function getSchema()
    {
        return '~/sidpt/binder-bundle/plugin/binder/document.json';
    }

    /**
     * [serialize description]
     *
     * @param Document $document [description]
     * @param array    $options  [description]
     *
     * @return [type]             [description]
     */
    public function serialize(Document $document, array $options = []): array
    {

        $savedContainers = $document->getWidgetContainers()->toArray();
        $containers = [];

        foreach ($savedContainers as $container) {
            //temporary
            $widgetContainerConfig = $container->getWidgetContainerConfigs()[0];
            if ($widgetContainerConfig) {
                if (!array_key_exists(
                    $widgetContainerConfig->getPosition(),
                    $containers
                )
                ) {
                    $containers[$widgetContainerConfig->getPosition()] = $container;
                } else {
                    $containers[] = $container;
                }
            }
        }

        ksort($containers);
        $containers = array_values($containers);

        // TODO check for translations based on user local
        $resourceName = $document->getResourceNode()->getName();
        $longTitle = $document->getLongTitle();

        foreach ($document->getTranslations() as $translation) {
            switch ($translation["path"]) {
                case 'resourceName':
                    # code...
                    break;
                case 'longTitle':
                    # code...
                    break;
                
                default:
                    break;
            }
        }

        $data = [
            'id'=>$document->getUuid(),
            'clarodoc' => [
                'id' => $document->getUuid(),
                'resourceName' => $resourceName,
                'longTitle' => $longTitle,
                'centerTitle' => $document->isCenterTitle(),
                'widgets' => array_map(
                    function ($container) use ($options) {
                        return $this->widgetContainerSerializer
                            ->serialize($container, $options);
                    },
                    $containers
                ),
                'translations' => $document->getTranslations()
            ],
            'directory' => [
                'id' => $document->getId(),
                'list' => [
                    'actions' => $document->hasActions(),
                    'count' => $document->hasCount(),
                    // display feature
                    'display' => $document->getDisplay(),
                    'availableDisplays' => $document->getAvailableDisplays(),

                    // sort feature
                    'sorting' => $document->getSortBy(),
                    'availableSort' => $document->getAvailableSort(),

                    // filter feature
                    'searchMode' => $document->getSearchMode(),
                    'filters' => $document->getFilters(),
                    'availableFilters' => $document->getAvailableFilters(),

                    // pagination feature
                    'paginated' => $document->isPaginated(),
                    'pageSize' => $document->getPageSize(),
                    'availablePageSizes' => $document->getAvailablePageSizes(),

                    // table config
                    'columns' => $document->getDisplayedColumns(),
                    'availableColumns' => $document->getAvailableColumns(),

                    // grid config
                    'card' => [
                        'display' => $document->getCard(),
                        'mapping' => [], // TODO
                    ],
                ]
            ]
        ];


        return $data;
    }

    public function deserializeWidgets(
        array $widgetsData,
        Document $document,
        array $options = []
    ) {
        $currentContainers = $document->getWidgetContainers()->toArray();
        $containerIds = [];

        // update containers
        foreach ($widgetsData as $position => $widget) {
            if (isset($widget['id'])) {
                $widgetContainer = $document->getWidgetContainer(
                    $widget['id']
                );
            }

            if (empty($widgetContainer)) {
                $widgetContainer = new WidgetContainer();
                $document->addWidgetContainer($widgetContainer);
            }

            $this->widgetContainerSerializer->deserialize(
                $widget,
                $widgetContainer,
                $options
            );
            $widgetContainerConfig = $widgetContainer
                ->getWidgetContainerConfigs()[0];
            $widgetContainerConfig->setPosition($position);
            $containerIds[] = $widgetContainer->getUuid();
        }

        // removes containers which no longer exists
        foreach ($currentContainers as $currentContainer) {
            if (!in_array($currentContainer->getUuid(), $containerIds)) {
                $document->removeWidgetContainer($currentContainer);
                $this->om->remove($currentContainer);
            }
        }
        return $document;
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
        Document $document = null,
        array $options = []
    ): Document {
        if (empty($document)) {
            $document = new Document();
        }


        $this->sipe('clarodoc.longTitle', 'setLongTitle', $data, $document);
        $this->sipe('clarodoc.centerTitle', 'setCenterTitle', $data, $document);
        $this->sipe('clarodoc.translations', 'setTranslations', $data, $document);

        if (isset($data['clarodoc']['widgets'])) {
            $this->deserializeWidgets($data['clarodoc']['widgets'], $document, $options);
        }

        $this->sipe('directory.list.count', 'setCount', $data, $document);
        $this->sipe('directory.list.actions', 'setActions', $data, $document);

        // display feature
        $this->sipe('directory.list.display', 'setDisplay', $data, $document);
        $this->sipe('directory.list.availableDisplays', 'setAvailableDisplays', $data, $document);

        // sort feature
        $this->sipe('directory.list.sorting', 'setSortBy', $data, $document);
        $this->sipe('directory.list.availableSort', 'setAvailableSort', $data, $document);

        // filter feature
        $this->sipe('directory.list.searchMode', 'setSearchMode', $data, $document);
        $this->sipe('directory.list.filters', 'setFilters', $data, $document);
        $this->sipe('directory.list.availableFilters', 'setAvailableFilters', $data, $document);

        // pagination feature
        $this->sipe('directory.list.paginated', 'setPaginated', $data, $document);
        $this->sipe('directory.list.pageSize', 'setPageSize', $data, $document);
        $this->sipe('directory.list.availablePageSizes', 'setAvailablePageSizes', $data, $document);

        // table config
        $this->sipe('directory.list.columns', 'setDisplayedColumns', $data, $document);
        $this->sipe('directory.list.availableColumns', 'setAvailableColumns', $data, $document);

        // grid config
        $this->sipe('directory.list.card.display', 'setCard', $data, $document);
        return $document;
    }
}
