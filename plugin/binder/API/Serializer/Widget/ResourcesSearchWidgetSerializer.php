<?php

namespace Sidpt\BinderBundle\API\Serializer\Widget;

use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Sidpt\BinderBundle\Entity\Widget\ResourcesSearchWidget;
use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\FinderProvider;

// Entities to be translated
//

use Claroline\TagBundle\Entity\Tag;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;

/**
 * @todo : find a way to merge with directory serializer
 */
class ResourcesSearchWidgetSerializer
{
    use SerializerTrait;

    private $finder;

    public function __construct(FinderProvider $finder) {
      $this->finder = $finder;
    }

    public function getClass()
    {
        return ResourcesSearchWidget::class;
    }

    public function getName()
    {
        return 'resources_search_widget';
    }


    public function serialize(ResourcesSearchWidget $widget, array $options = []): array
    {
        // TODO
        // When serializing filters, retrieve entities translated names
        // using their ids and recompute the filters content
        // for now, only tags are sent back with their name
        $filters = $widget->getFilters();
        $translatedFilters = array();
        foreach ($filters as $index => $filter) {
          $property = $filter['property'];
          $value = $filter['value'];
          switch ($property) {
            case 'tags':
              $tagsFilters = is_array($value) ? $value : [$value];
              $tagsIds = array_map(function ($tag){
                return $tag['id'];
              }, $tagsFilters);
              $tags = $this->finder->search(
                Tag::class,
                ['uuid' => $tagsIds],
                [Options::TRANSLATED]
              )['data'];
              $translatedFilters[] = [
                'property' => 'tags',
                'value' => array_map(function ($tag){
                  return [
                    'id' => $tag['id'],
                    'name' => $tag['name'],
                  ];
                }, $tags),
                'locked' => $filter['locked'] ?? 0
              ];

              break;

            default: // copy filter
              $translatedFilters[] = $filter;
              // code...
              break;
          }
        }

        // throw new \Exception(print_r([
        //    'filters' => $filters,
        //    'translated' => $translatedFilters
        // ]));

        return [
            'searchFormConfiguration' => $widget->getSearchFormConfiguration(),
            'maxResults' => $widget->getMaxResults(),

            'actions' => $widget->hasActions(),
            'count' => $widget->hasCount(),

            // display feature
            'display' => $widget->getDisplay(),
            'availableDisplays' => $widget->getAvailableDisplays(),

            // sort feature
            'sorting' => $widget->getSortBy(),
            'availableSort' => $widget->getAvailableSort(),

            // filter feature
            'searchMode' => $widget->getSearchMode(),
            'filters' => $translatedFilters,
            'availableFilters' => $widget->getAvailableFilters(),

            // pagination feature
            'paginated' => $widget->isPaginated(),
            'pageSize' => $widget->getPageSize(),
            'availablePageSizes' => $widget->getAvailablePageSizes(),

            // table config
            'columns' => $widget->getDisplayedColumns(),
            'availableColumns' => $widget->getAvailableColumns(),

            'columnsCustomization' => $widget->getColumnsCustomization(),

            // grid config
            'card' => [
                'display' => $widget->getCard(),
            ],
        ];
    }

    public function deserialize($data, ResourcesSearchWidget $widget, array $options = []): ResourcesSearchWidget
    {
        $this->sipe('searchFormConfiguration', 'setSearchFormConfiguration', $data, $widget);
        $this->sipe('maxResults', 'setMaxResults', $data, $widget);

        $this->sipe('count', 'setCount', $data, $widget);
        $this->sipe('actions', 'setActions', $data, $widget);

        // display feature
        $this->sipe('display', 'setDisplay', $data, $widget);
        $this->sipe('availableDisplays', 'setAvailableDisplays', $data, $widget);

        // sort feature
        $this->sipe('sorting', 'setSortBy', $data, $widget);
        $this->sipe('availableSort', 'setAvailableSort', $data, $widget);

        // filter feature
        $this->sipe('searchMode', 'setSearchMode', $data, $widget);
        $this->sipe('filters', 'setFilters', $data, $widget);
        $this->sipe('availableFilters', 'setAvailableFilters', $data, $widget);

        // pagination feature
        $this->sipe('paginated', 'setPaginated', $data, $widget);
        $this->sipe('pageSize', 'setPageSize', $data, $widget);
        $this->sipe('availablePageSizes', 'setAvailablePageSizes', $data, $widget);

        // table config
        $this->sipe('columns', 'setDisplayedColumns', $data, $widget);
        $this->sipe('availableColumns', 'setAvailableColumns', $data, $widget);

        $this->sipe('columnsCustomization', 'setColumnsCustomization', $data, $widget);

        // grid config
        $this->sipe('card.display', 'setCard', $data, $widget);

        return $widget;
    }
}
