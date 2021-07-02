<?php


namespace Sidpt\BinderBundle\Entity;

use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Doctrine\Common\Collections\ArrayCollection;

use Claroline\AppBundle\Entity\Identifier\Uuid;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Claroline\AppBundle\Entity\Parameters\ListParameters;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(name="sidpt__document")
 */
class Document extends AbstractResource
{
    use Uuid;
    use ListParameters;
    
    /**
     * @ORM\Column(name="long_title", nullable=true, type="text")
     */
    private $longTitle = '';


    /**
     * @ORM\Column(name="center_title", type="boolean")
     */
    private $centerTitle = false;


    /**
     * ResourceNode holding the tree of
     * required/recommended resource to have read before
     *
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Resource\ResourceNode"
     * )
     * @ORM\JoinColumn(
     *     name="requirements_node_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL"
     * )
     *
     * @var ResourceNode
     */
    private $requiredResourceNodeTreeRoot = null;

    /**
     * @ORM\Column(name="overview", type="boolean")
     */
    private $showOverview = false;

    /**
     * @ORM\Column(name="widgets_pagination", type="boolean")
     */
    private $widgetsPagination = false;

    /**
     * Widgets of a document
     *
     *
     * @ORM\ManyToMany(
     *      targetEntity="Claroline\CoreBundle\Entity\Widget\WidgetContainer",
     *      cascade={"persist","remove"})
     * @ORM\JoinTable(
     *      name="sidpt__document_widgets",
     *      joinColumns={
     *          @ORM\JoinColumn(name="document_id", referencedColumnName="id")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(
     *              name="widget_container_id",
     *              referencedColumnName="id",
     *              unique=true,
     *              onDelete="CASCADE"
     *         )
     *      }
     *  )
     *
     * @var WidgetContainer[]|ArrayCollection
     */
    private $widgetContainers;

    

    /**
     * Translations of document level fields
     * (like document sections names, long title, current resource name to display etc)
     *
     * May be replaced by versionned document later on but it remains to be tested
     *
     * Note that fields names are based on the serializer data sent or received
     *
     * example :
     * [{  path:"longTitle",
     *     locales:{
     *         en:'',
     *         fr:''
     *      }
     *  } ...
     * }]
     *
     *
     * @ORM\Column(type="json", nullable=true)
     *
     * @var json object
     */
    private $translations;

    /**
     * Document constructor.
     */
    public function __construct()
    {
        $this->refreshUuid();
        $this->widgetContainers = new ArrayCollection();

        // C/C from directory
        // set some list configuration defaults
        // can be done later in the resource.directory.create event
        $this->count = true;
        $this->card = ['icon', 'flags', 'subtitle', 'description', 'footer'];

        $this->availableColumns = ['name', 'published', 'resourceType'];
        $this->displayedColumns = ['name', 'published', 'resourceType'];

        $this->filterable = true;
        $this->searchMode = 'unified';
        $this->availableFilters = ['name', 'published', 'resourceType'];

        $this->sortable = true;
        $this->sortBy = 'name';
        $this->availableSort = ['name', 'resourceType'];
    }

    /**
     * @return json_array
     */
    public function getTranslations()
    {
        if (empty($this->translations)) {
            $tempArray = [];
            foreach ($this->getTranslatableFields() as $value) {
                $tempArray[] = [
                    "path" => $value,
                    "locales" => [
                        'en' => ''
                    ]
                ];
            }
            $this->translations = $tempArray;
        }
        return $this->translations;
    }

    public function getTranslatableFields()
    {
        $translatableFields = array();
        $translatableFields[] = 'resourceName';
        $translatableFields[] = 'longTitle';
        
        foreach ($this->getWidgetContainers()->toArray() as $index => $widgetContainer) {
            $translatableFields[] = "widgets[".$index."].name";
        }
        return $translatableFields;
    }

    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }

    /**
     * @return WidgetContainer[]|ArrayCollection
     */
    public function getWidgetContainers()
    {
        return $this->widgetContainers;
    }

    /**
     * @param string $containerId
     *
     * @return WidgetContainer|null
     */
    public function getWidgetContainer($containerId)
    {
        $found = null;

        foreach ($this->widgetContainers as $container) {
            if ($container->getUuid() === $containerId) {
                $found = $container;
                break;
            }
        }

        return $found;
    }

    /**
     * @param WidgetContainer
     */
    public function addWidgetContainer(WidgetContainer $widgetContainer)
    {
        if (!$this->widgetContainers->contains($widgetContainer)) {
            $this->widgetContainers->add($widgetContainer);
        }
    }

    /**
     * @param  WidgetContainer
     */
    public function removeWidgetContainer(WidgetContainer $widgetContainer)
    {
        if ($this->widgetContainers->contains($widgetContainer)) {
            $this->widgetContainers->removeElement($widgetContainer);
        }
    }

    /**
     * @return [type]
     */
    public function getLongTitle()
    {
        return $this->longTitle;
    }

    /**
     * @param [type]
     */
    public function setLongTitle($longTitle)
    {
        $this->longTitle = $longTitle;
    }

    /**
     * @return boolean
     */
    public function isCenterTitle()
    {
        return $this->centerTitle;
    }

    /**
     * @param [type]
     */
    public function setCenterTitle($centerTitle)
    {
        $this->centerTitle = $centerTitle;
    }

    /**
     * [getLocale description]
     * @return [type] [description]
     */
    public function getLocale()
    {
        return $this->locale;
    }

    
    
    /**
     * [getPreviousVersion description]
     * @return [type] [description]
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * [setrequiredResourceNodeTreeRoot description]
     * @param [type] $resourceNode [description]
     */
    public function setRequiredResourceNodeTreeRoot($resourceNode = null)
    {
        $this->requiredResourceNodeTreeRoot = $resourceNode;
    }
    
    /**
     * [getrequiredResourceNodeTreeRoot description]
     * @return [type] [description]
     */
    public function getRequiredResourceNodeTreeRoot()
    {
        return $this->requiredResourceNodeTreeRoot;
    }


    /**
     * [setShowOverview description]
     * @return [type] [description]
     */
    public function setShowOverview(bool $showOverview)
    {
        $this->showOverview = $showOverview;
    }

    /**
     * [getShowOverview description]
     * @return [type] [description]
     */
    public function getShowOverview()
    {
        return $this->showOverview;
    }

     /**
     * [getShowOverview description]
     * @return [type] [description]
     */
    public function getWidgetsPagination()
    {
        return $this->widgetsPagination;
    }

    /**
     * [setwidgetsPagination description]
     * @return [type] [description]
     */
    public function setWidgetsPagination(bool $widgetsPagination)
    {
        $this->widgetsPagination = $widgetsPagination;
    }


    public function __toString()
    {
        $display = "{";
        $display .= "\"id\":\"{$this->getUuid()}\",";
        $display .= "\"longtitle\":\"{$this->getLongTitle()}\",";
        $display .= "\"widgets\":[";
        foreach ($this->getWidgetContainers() as $widgetContainer) {
            $display .= "\"{$widgetContainer->getUuid()}\"";
        }
        $display .= "]";
        $display .= "}";
        return $display;
    }

    public function __clone()
    {
        if ($this->getId()) {
            $this->setId(null);
            $this->resourceNode = clone $this->resourceNode;
            $containersClone = new ArrayCollection();
            foreach ($this->widgetContainers as $container) {
                $newContainer = clone $container;
                $containersClone->add($newContainer);
            }
            $this->widgetContainers = $containersClone;
        }
    }
}
