<?php

namespace Sidpt\BinderBundle\Entity\Widget;

use Claroline\CoreBundle\Entity\Widget\Type\AbstractWidget;

use Claroline\AppBundle\Entity\Parameters\ListParameters;
use Doctrine\ORM\Mapping as ORM;

/**
 * ResourceSearchWidget.
 *
 * Permits to render an arbitrary list of data.
 *
 * @ORM\Entity()
 * @ORM\Table(name="sidpt_widget_resources_search")
 */
class ResourcesSearchWidget extends AbstractWidget
{
    use ListParameters;

    /**
     * Search form configuration
     * @ORM\Column(type="json")
     */
    private $searchFormConfiguration = [];

    public function getSearchFormConfiguration()
    {
        return $this->searchFormConfiguration;
    }

    public function setSearchFormConfiguration($searchFormConfiguration)
    {
        $this->searchFormConfiguration = $searchFormConfiguration;
    }

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var int
     */
    private $maxResults;

    public function getMaxResults()
    {
        return $this->maxResults;
    }

    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }
}
