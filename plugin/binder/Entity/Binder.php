<?php


namespace Sidpt\BinderBundle\Entity;

use Sidpt\BinderBundle\Entity\BinderTab;
use Doctrine\Common\Collections\ArrayCollection;

use Claroline\AppBundle\Entity\Identifier\Uuid;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

use Claroline\AppBundle\Entity\Parameters\ListParameters;

/**
 * Binder with tabs
 *
 * @ORM\Entity()
 * @ORM\Table(name="sidpt__binder")
 *
 */
class Binder extends AbstractResource
{
    use Uuid;

    use ListParameters;

    /**
     * [desc]
     *
     * @ORM\OneToMany(
     *     targetEntity="Sidpt\BinderBundle\Entity\BinderTab",
     *     mappedBy="owner",
     *     cascade={"persist", "remove"})
     *
     * @var BinderTab[]|ArrayCollection
     */
    protected $binderTabs;


    /**
     * When true, activate the tab-based navigation
     *
     * @ORM\Column(type="boolean", name="display_tabs")
     * @var [type]
     */
    private $displayTabs = false;

    
    /**
     * Document constructor.
     */
    public function __construct()
    {
        $this->refreshUuid();
        $this->binderTabs = new ArrayCollection();


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
     * [getBinderTabs description]
     *
     * @return [type] [description]
     */
    public function getBinderTabs()
    {
        return $this->binderTabs;
    }

    /**
     * @param string $tabId
     *
     * @return BinderTab|null
     */
    public function getBinderTab($tabId)
    {
        $found = null;

        foreach ($this->binderTabs as $tab) {
            if ($tab->getId() === $tabId) {
                $found = $tab;
                break;
            }
        }

        return $found;
    }

    /**
     * [addBinderTab description]
     *
     * @param BinderTab $tab [description]
     *
     * @return [type]         [description]
     */
    public function addBinderTab(BinderTab $tab): Binder
    {
        if (!$this->binderTabs->contains($tab)) {
            $this->binderTabs->add($tab);
        }
        return $this;
    }

    /**
     * [removeBinderTab description]
     *
     * @param BinderTab $tab [description]
     *
     * @return [type]         [description]
     */
    public function removeBinderTab(BinderTab $tab): Binder
    {
        if ($this->binderTabs->contains($tab)) {
            $this->binderTabs->removeElement($tab);
        }
        return $this;
    }
}
