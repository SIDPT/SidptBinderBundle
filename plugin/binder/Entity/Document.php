<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidpt\BinderBundle\Entity;

use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Doctrine\Common\Collections\ArrayCollection;

use Claroline\AppBundle\Entity\Identifier\Uuid;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(name="sidpt__document")
 */
class Document extends AbstractResource
{
    use Uuid;
    
    /**
     * @ORM\Column(name="long_title", nullable=true, type="text")
     */
    private $longTitle = '';


    /**
     * @ORM\Column(name="center_title", type="boolean")
     */
    private $centerTitle = false;

    /**
     * @ORM\ManyToMany(
     *      targetEntity="Claroline\CoreBundle\Entity\Widget\WidgetContainer",
     *      cascade={"persist","remove"}
     * )
     * @ORM\JoinTable(
     *      name="sidpt__document_widgets",
     *      joinColumns={
     *          @ORM\JoinColumn(name="document_id", referencedColumnName="id")},
     *          inverseJoinColumns={
     *             @ORM\JoinColumn(name="widget_container_id", referencedColumnName="id", unique=true)}
     *      )
     *
     * @var WidgetContainer[]|ArrayCollection
     */
    private $widgetContainers;

    /**
     * Document constructor.
     */
    public function __construct()
    {
        $this->refreshUuid();
        $this->widgetContainers = new ArrayCollection();
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

}
