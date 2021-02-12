<?php

/**
 *
 */

namespace Sidpt\BinderBundle\Entity;

use Claroline\AppBundle\Entity\Identifier\Id;
use Claroline\CoreBundle\Entity\Role;
use Doctrine\Common\Collections\ArrayCollection;

use Sidpt\BinderBundle\Entity\Document;
use Sidpt\BinderBundle\Entity\Binder;

use Claroline\AppBundle\Entity\Identifier\Uuid;

use Claroline\CoreBundle\Entity\Resource\AbstractResource;


use Doctrine\ORM\Mapping as ORM;

/**
 * A tab of a binder, that can hold a Document or another Binder
 *
 * @ORM\Entity()
 * @ORM\Table(name="sidpt__binder_tab")
 */
class BinderTab
{
    use Id;
    use UuId;

    const TYPE_BINDER = "binder";
    const TYPE_DOCUMENT = "document";
    const TYPE_UNDEFINED = "undefined";

    protected static $typeName = [
        self::TYPE_BINDER    => 'Binder',
        self::TYPE_DOCUMENT => 'Document',
        self::TYPE_UNDEFINED => 'Undefined'
    ];

    /**
     * TODO
     *
     * @return array<string>
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_BINDER,
            self::TYPE_DOCUMENT,
            self::TYPE_UNDEFINED
        ];
    }

    /**
     * Binder owning the tab
     *
     * @ORM\ManyToOne(
     *     targetEntity="Sidpt\BinderBundle\Entity\Binder",
     *     cascade={"persist", "remove"},
     *     inversedBy="binderTabs")
     * @ORM\JoinColumn(name="owner_id", nullable=false, onDelete="CASCADE")
     *
     * @var Binder
     */
    private $owner;

    /**
     * [$position description]
     *
     * @var integer
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    private $position = 0;


    /**
     * [TODO]
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @var string
     */
    private $type = self::TYPE_UNDEFINED;

    /**
     * Binder possibly associated to the tab
     *
     * @ORM\ManyToOne(targetEntity="Sidpt\BinderBundle\Entity\Binder")
     * @ORM\JoinColumn(name="binder_id",nullable=true)
     *
     * @var Binder
     */
    private $binder = null;

    /**
     * Document possibly associated to the tab
     *
     * @ORM\ManyToOne(targetEntity="Sidpt\BinderBundle\Entity\Document")
     * @ORM\JoinColumn(name="document_id",nullable=true)
     *
     * @var Binder
     */
    private $document = null;


    
    /**
     * Title of the tab
     * (if not defined, the title of the pointed resource is used)
     *
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $title;

    

    /**
     * Tab backgroundcolor
     *
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $backgroundColor = null;

    /**
     * Tab text color
     *
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $textColor = null;

    /**
     * Tab border color
     *
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $borderColor = null;

    /**
     * @ORM\Column(nullable=true)
     */
    private $icon;

    /**
     * @ORM\Column(type="boolean", name="is_visible")
     *
     * @var bool
     */
    private $visible = true;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     *
     * @var array
     */
    private $details;

    /**
     * @ORM\ManyToMany(targetEntity="Claroline\CoreBundle\Entity\Role")
     * @ORM\JoinTable(name="sidpt__binder_tab_roles")
     *
     * @var Role[]
     */
    private $roles;


    /**
     * Possible translations for the tab fields.
     *
     * Note that fields names are based on the serializer data sent or received
     * [ {path:'fieldname', locales:{en:'',fr:'' etc} } ...]
     *
     * @ORM\Column(type="json", nullable=true)
     *
     * @var array
     */
    private $translations;

    /**
     * Document constructor.
     */
    public function __construct()
    {
        $this->refreshUuid();
        $this->roles = new ArrayCollection();
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
        $translatableFields[] = 'title';
        return $translatableFields;
    }

    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }

    /**
     * Get owner.
     *
     * @return Binder
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set owner.
     *
     * @param Binder $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * Get backgroundcolor.
     *
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Set backgroundcolor.
     *
     * @param string $backgroundColor
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;
    }

    /**
     * Get bordercolor.
     *
     * @return string
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }

    /**
     * Set bordercolor.
     *
     * @param string $borderColor
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = $borderColor;
    }

    /**
     * Get textcolor.
     *
     * @return string
     */
    public function getTextColor()
    {
        return $this->textColor;
    }

    /**
     * Set textcolor.
     *
     * @param string $textColor
     */
    public function setTextColor($textColor)
    {
        $this->textColor = $textColor;
    }

    /**
     * TODO
     * @return Role[]|ArrayCollection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * TODO
     * @return Role[]|ArrayCollection
     */
    public function getRole($roleId)
    {
        $found = null;

        foreach ($this->roles as $role) {
            if ($role->getUuid() === $roleId) {
                $found = $role;
                break;
            }
        }

        return $found;
    }

    /**
     * TODO
     *
     * @param Role $role [description]
     */
    public function addRole(Role $role)
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }

    public function removeRole(Role $role)
    {
        $this->roles->removeElement($role);
    }

    public function emptyRoles()
    {
        $this->roles->clear();
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setType($type)
    {
        if (!in_array($type, BinderTab::getAvailableTypes())) {
            throw new \InvalidArgumentException("Invalid binder tab type");
        }
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function setDetails($details)
    {
        $this->details = $details;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setDocument(Document $document)
    {
        $this->document = $document;
        $this->setType(BinderTab::TYPE_DOCUMENT);
        $this->binder = null;
    }

    public function getDocument() : Document
    {
        return $this->document;
    }

    public function setBinder(Binder $binder)
    {
        $this->binder = $binder;
        $this->setType(BinderTab::TYPE_BINDER);
        $this->document = null;
    }

    public function getBinder() : Binder
    {
        return $this->binder;
    }

    public function getContent() : AbstractResource
    {
        if ($this->type === BinderTab::TYPE_BINDER) {
            return $this->getBinder();
        } else if ($this->type === BinderTab::TYPE_DOCUMENT) {
            return $this->getDocument();
        } else {
            return null;
        }
    }

    public function removeContent()
    {
        $this->document = null;
        $this->binder = null;
        $this->setType(BinderTab::TYPE_UNDEFINED);
    }
}
