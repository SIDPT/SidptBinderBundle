<?php


namespace Sidpt\BinderBundle\Entity;

use Sidpt\BinderBundle\Entity\BinderTab;
use Doctrine\Common\Collections\ArrayCollection;

use Claroline\AppBundle\Entity\Identifier\Uuid;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

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
     * Document constructor.
     */
    public function __construct()
    {
        $this->refreshUuid();
        $this->binderTabs = new ArrayCollection();
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
